<?php
include 'auth.php';
include 'db.php';

/* ================= INPUT FILTER ================= */
$fIP     = trim($_GET['ip']     ?? '');
$fPC     = trim($_GET['pc']     ?? '');
$fStatus = trim($_GET['status'] ?? '');
$fRoom   = trim($_GET['room']   ?? '');

/* ================= STATS ================= */
$totalPCs   = $conn->query("SELECT COUNT(DISTINCT pc_id) t FROM pc_status_table")->fetch_assoc()['t'] ?? 0;
$onlinePCs  = $conn->query("SELECT COUNT(DISTINCT t1.pc_id) t FROM pc_status_table t1 INNER JOIN (SELECT pc_id, MAX(id) mid FROM pc_status_table GROUP BY pc_id) x ON t1.pc_id=x.pc_id AND t1.id=x.mid WHERE t1.status LIKE '%online%' OR t1.status LIKE '%connected%' OR t1.status LIKE '%active%'")->fetch_assoc()['t'] ?? 0;
$offlinePCs = $totalPCs - $onlinePCs;
$lastScan   = $conn->query("SELECT MAX(last_seen) t FROM pc_status_table")->fetch_assoc()['t'] ?? 'Never';

/* ================= MAIN QUERY ================= */
$sql = "
SELECT t.*
FROM pc_status_table t
INNER JOIN (
    SELECT pc_id, MAX(id) AS max_id
    FROM pc_status_table
    GROUP BY pc_id
) x ON t.pc_id = x.pc_id AND t.id = x.max_id
WHERE 1=1
";
if($fIP)     $sql .= " AND t.ip_address LIKE '%".$conn->real_escape_string($fIP)."%'";
if($fPC)     $sql .= " AND t.pc_name LIKE '%".$conn->real_escape_string($fPC)."%'";
if($fStatus) $sql .= " AND t.status LIKE '%".$conn->real_escape_string($fStatus)."%'";
if($fRoom)   $sql .= " AND t.room_name LIKE '%".$conn->real_escape_string($fRoom)."%'";
$sql .= " ORDER BY t.id DESC LIMIT 200";

$result = $conn->query($sql);
$rows = [];
if($result) while($r = $result->fetch_assoc()) $rows[] = $r;
$total = count($rows);

// Set view for sidebar active state
$_GET['view'] = 'network';
include 'header.php';
?>

<!-- STATS -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-accent blue"></div>
    <div class="stat-icon blue"><i class="ti ti-device-desktop"></i></div>
    <div class="stat-label">Total PCs</div>
    <div class="stat-value" style="color:#6366f1"><?= $totalPCs ?></div>
    <div class="stat-sub">Unique PC ID Records</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent green"></div>
    <div class="stat-icon green"><i class="ti ti-wifi"></i></div>
    <div class="stat-label">Online</div>
    <div class="stat-value" style="color:#059669"><?= $onlinePCs ?></div>
    <div class="stat-sub">Last Seen Status</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent red"></div>
    <div class="stat-icon red"><i class="ti ti-wifi-off"></i></div>
    <div class="stat-label">Offline</div>
    <div class="stat-value" style="color:#dc2626"><?= $offlinePCs ?></div>
    <div class="stat-sub">Last Seen Status</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent amber"></div>
    <div class="stat-icon amber"><i class="ti ti-clock"></i></div>
    <div class="stat-label">Last Seen</div>
    <div class="stat-value" style="color:#d97706;font-size:12px;margin-top:8px"><?= htmlspecialchars($lastScan) ?></div>
    <div class="stat-sub">lLtest Record Time</div>
  </div>
</div>

<!-- SCAN PANEL -->
<div class="panel" style="margin-bottom:16px;">
  <div class="panel-head">
    <div class="panel-title">
      <i class="ti ti-radar"></i>Network Scanner
      <span id="scan_badge" class="panel-badge">Idle</span>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <?php foreach(['100.2.1','100.2.2','100.2.6'] as $sn): ?>
      <label style="display:flex;align-items:center;gap:4px;font-size:11px;cursor:pointer;color:#64748b">
        <input type="checkbox" class="subnet-chk" value="<?=$sn?>" checked style="accent-color:#6366f1">
        <?=$sn?>.x
      </label>
      <?php endforeach; ?>
      <button id="btnScan" onclick="startScan()" class="filter-btn">
        <i class="ti ti-player-play"></i>&nbsp;Start Scan
      </button>
      <button id="btnStop" onclick="stopScan()" class="filter-btn" style="background:#ef4444;display:none">
        <i class="ti ti-player-stop"></i>&nbsp;Stop
      </button>
    </div>
  </div>

  <!-- Progress -->
  <div style="padding:10px 16px;background:#f8fafc;border-bottom:.5px solid #e2e8f0;">
    <div style="display:flex;justify-content:space-between;margin-bottom:5px">
      <span style="font-size:11px;color:#64748b">
        Scanning: <span id="cur_ip" style="color:#6366f1;font-family:monospace">—</span>
      </span>
      <span style="font-size:11px;color:#64748b">
        <span id="done_n">0</span>/<span id="tot_n">0</span>
        &nbsp;|&nbsp;
        <span style="color:#059669">Connected: <b id="conn_n">0</b></span>
        &nbsp;|&nbsp;
        <span style="color:#dc2626">Not: <b id="disc_n">0</b></span>
      </span>
    </div>
    <div style="background:#e2e8f0;border-radius:99px;height:6px;overflow:hidden">
      <div id="prog_bar" style="height:100%;width:0%;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:99px;transition:width .2s"></div>
    </div>
  </div>

  <!-- Scan live results -->
  <div id="scan_results" style="display:none;max-height:200px;overflow-y:auto;border-bottom:.5px solid #e2e8f0;">
    <table class="tbl">
      <thead><tr><th>IP</th><th>Status</th><th>PC Name</th><th>Time</th></tr></thead>
      <tbody id="scan_body"></tbody>
    </table>
  </div>
</div>

<!-- PC STATUS TABLE -->
<div class="panel">
  <div class="panel-head">
    <div class="panel-title">
      <i class="ti ti-device-desktop"></i>PC Status
      <span class="panel-badge"><?= $total ?> records</span>
    </div>
  </div>

  <form method="GET" class="filter-row">
    <input name="ip"   placeholder="Filter IP..."      value="<?= htmlspecialchars($fIP) ?>">
    <input name="pc"   placeholder="Filter PC name..." value="<?= htmlspecialchars($fPC) ?>">
    <input name="room" placeholder="Filter Room..."    value="<?= htmlspecialchars($fRoom) ?>">
    <select name="status">
      <option value="">All Status</option>
      <option value="online"    <?= $fStatus==='online'    ?'selected':'' ?>>Online</option>
      <option value="offline"   <?= $fStatus==='offline'   ?'selected':'' ?>>Offline</option>
      <option value="connected" <?= $fStatus==='connected' ?'selected':'' ?>>Connected</option>
    </select>
    <button type="submit" class="filter-btn">
      <i class="ti ti-search" style="font-size:13px"></i>Search
    </button>
    <a href="sync_pc_status.php" class="filter-btn" style="background:#64748b">
      <i class="ti ti-x" style="font-size:13px"></i>Clear
    </a>
  </form>

  <table class="tbl">
    <thead>
      <tr>
        <th>ID</th>
        <th>PC ID</th>
        <th>PC Name</th>
        <th>IP Address</th>
        <th>Room</th>
        <th>Room No</th>
        <th>Row</th>
        <th>Table</th>
        <th style="text-align:center">Status</th>
        <th>Last Seen</th>
      </tr>
    </thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr>
        <td colspan="10" style="text-align:center;padding:24px;color:#94a3b8">
          <i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>
          No records found
        </td>
      </tr>
    <?php else: foreach($rows as $r):
        $st    = strtolower($r['status'] ?? '');
        $isOn  = (strpos($st,'online')!==false) || (strpos($st,'connected')!==false) || (strpos($st,'active')!==false);
    ?>
      <tr>
        <td style="color:#94a3b8"><?= $r['id'] ?></td>
        <td class="mono" style="font-size:10px"><?= htmlspecialchars($r['pc_id']       ?? '') ?></td>
        <td style="font-weight:500">             <?= htmlspecialchars($r['pc_name']     ?? '') ?></td>
        <td class="mono">                        <?= htmlspecialchars($r['ip_address']  ?? '') ?></td>
        <td>                                     <?= htmlspecialchars($r['room_name']   ?? '') ?></td>
        <td>                                     <?= htmlspecialchars($r['room_number'] ?? '') ?></td>
        <td>                                     <?= htmlspecialchars($r['row_number']  ?? '') ?></td>
        <td>                                     <?= htmlspecialchars($r['table_number']?? '') ?></td>
        <td style="text-align:center">
          <?php if($isOn): ?>
            <span class="pill p-conn"><span class="dot dg"></span><?= htmlspecialchars($r['status']) ?></span>
          <?php else: ?>
            <span class="pill p-disc"><span class="dot dr"></span><?= htmlspecialchars($r['status']) ?></span>
          <?php endif; ?>
        </td>
        <td class="mono" style="font-size:10px"><?= htmlspecialchars($r['last_seen'] ?? '') ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
let scanning = false, stopFlag = false;
let ipQueue = [], doneCount = 0, connCount = 0, discCount = 0;

function buildQueue(){
  ipQueue = [];
  document.querySelectorAll('.subnet-chk:checked').forEach(c => {
    for(let i = 1; i <= 254; i++) ipQueue.push(c.value + '.' + i);
  });
  document.getElementById('tot_n').textContent = ipQueue.length;
}
document.querySelectorAll('.subnet-chk').forEach(c => c.addEventListener('change', buildQueue));
buildQueue();

async function startScan(){
  if(scanning) return;
  buildQueue();
  if(!ipQueue.length){ alert('Select at least one subnet.'); return; }

  scanning = true; stopFlag = false;
  doneCount = connCount = discCount = 0;

  document.getElementById('btnScan').style.display  = 'none';
  document.getElementById('btnStop').style.display  = '';
  document.getElementById('scan_badge').textContent = 'Scanning...';
  document.getElementById('scan_body').innerHTML    = '';
  document.getElementById('scan_results').style.display = '';
  document.getElementById('prog_bar').style.width   = '0%';

  const total = ipQueue.length;

  for(let i = 0; i < ipQueue.length; i++){
    if(stopFlag) break;
    const ip = ipQueue[i];
    document.getElementById('cur_ip').textContent = ip;

    try {
      const res  = await fetch('scan_runner.php?ip=' + encodeURIComponent(ip));
      const data = await res.json();
      doneCount++;
      document.getElementById('prog_bar').style.width  = Math.round((doneCount/total)*100) + '%';
      document.getElementById('done_n').textContent    = doneCount;

      const isConn = data.status === 'CONNECTED';
      if(isConn) connCount++; else discCount++;
      document.getElementById('conn_n').textContent = connCount;
      document.getElementById('disc_n').textContent = discCount;

      if(isConn){
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="mono" style="color:#6366f1">${data.ip}</td>
          <td><span class="pill p-conn"><span class="dot dg"></span>Connected</span></td>
          <td style="font-weight:500">${data.name}</td>
          <td class="mono" style="font-size:10px">${new Date().toLocaleTimeString()}</td>`;
        document.getElementById('scan_body').prepend(tr);
      }
    } catch(e){ doneCount++; }

    await new Promise(r => setTimeout(r, 80));
  }

  scanning = false;
  document.getElementById('btnScan').style.display  = '';
  document.getElementById('btnStop').style.display  = 'none';
  document.getElementById('cur_ip').textContent     = '—';
  document.getElementById('scan_badge').textContent = stopFlag ? 'Stopped' : 'Complete ✓';

  if(!stopFlag) setTimeout(() => location.reload(), 2000);
}

function stopScan(){
  stopFlag = true;
  document.getElementById('scan_badge').textContent = 'Stopping...';
}
</script>

<?php include 'footer.php'; ?>