<?php
include 'auth.php';
include 'db.php';

/* ── filters ─────────────────────────────────────────────────────────── */
$fIP     = trim($_GET['ip']     ?? '');
$fPC     = trim($_GET['pc']     ?? '');
$fStatus = trim($_GET['status'] ?? '');
$fDate   = trim($_GET['date']   ?? '');
$tab     = $_GET['tab'] ?? 'network';   // 'network' | 'scan'
$page    = max(1, intval($_GET['pg'] ?? 1));
$perPage = 15;

/* ── counters ─────────────────────────────────────────────────────────── */
// $hw_count      = $conn->query("SELECT COUNT(DISTINCT computer_name) t FROM hardware_status")->fetch_assoc()['t'] ?? 0;
$hw_res = $conn->query("SELECT COUNT(DISTINCT computer_name) t FROM hardware_master");
$hw_count = ($hw_res ? $hw_res->fetch_assoc()['t'] : 0) ?? 0;
// Network tab counters (lab_network_status — latest per IP)
$conn_count    = $conn->query("
    SELECT COUNT(*) t FROM lab_network_status n1
    WHERE n1.status='CONNECTED'
      AND n1.id=(SELECT MAX(n2.id) FROM lab_network_status n2 WHERE n2.ip=n1.ip)
")->fetch_assoc()['t'] ?? 0;
$notconn_count = $conn->query("
    SELECT COUNT(*) t FROM lab_network_status n1
    WHERE n1.status='NOT CONNECTED'
      AND n1.id=(SELECT MAX(n2.id) FROM lab_network_status n2 WHERE n2.ip=n1.ip)
")->fetch_assoc()['t'] ?? 0;

// Scan tab counters (pc_status_table — latest per pc_id)
$scan_total   = $conn->query("SELECT COUNT(DISTINCT pc_id) t FROM pc_status_table")->fetch_assoc()['t'] ?? 0;
$scan_online  = $conn->query("
    SELECT COUNT(DISTINCT t1.pc_id) t FROM pc_status_table t1
    INNER JOIN (SELECT pc_id, MAX(id) mid FROM pc_status_table GROUP BY pc_id) x
      ON t1.pc_id=x.pc_id AND t1.id=x.mid
    WHERE t1.status LIKE '%online%' OR t1.status LIKE '%connected%' OR t1.status LIKE '%active%'
")->fetch_assoc()['t'] ?? 0;
$scan_offline = $scan_total - $scan_online;
$lastScan     = $conn->query("SELECT MAX(last_seen) t FROM pc_status_table")->fetch_assoc()['t'] ?? 'Never';

/* ── network tab query ───────────────────────────────────────────────── */
$netSQL = "SELECT * FROM lab_network_status n1 WHERE 1=1";
if($fIP)     $netSQL .= " AND n1.ip LIKE '%".$conn->real_escape_string($fIP)."%'";
if($fPC)     $netSQL .= " AND n1.computer_name LIKE '%".$conn->real_escape_string($fPC)."%'";
if($fDate)   $netSQL .= " AND DATE(n1.date_time)='".$conn->real_escape_string($fDate)."'";
if($fStatus) $netSQL .= " AND n1.status='".$conn->real_escape_string($fStatus)."'";
$netSQL .= " AND n1.id=(SELECT MAX(n2.id) FROM lab_network_status n2 WHERE n2.ip=n1.ip";
if($fDate)   $netSQL .= " AND DATE(n2.date_time)='".$conn->real_escape_string($fDate)."'";
$netSQL .= ") ORDER BY n1.ip ASC";

/* ── scan tab query ──────────────────────────────────────────────────── */
$scanSQL = "
    SELECT t.* FROM pc_status_table t
    INNER JOIN (SELECT pc_id, MAX(id) AS max_id FROM pc_status_table GROUP BY pc_id) x
      ON t.pc_id=x.pc_id AND t.id=x.max_id WHERE 1=1
";
if($fIP)     $scanSQL .= " AND t.ip_address LIKE '%".$conn->real_escape_string($fIP)."%'";
if($fPC)     $scanSQL .= " AND t.pc_name LIKE '%".$conn->real_escape_string($fPC)."%'";
if($fStatus) $scanSQL .= " AND t.status LIKE '%".$conn->real_escape_string($fStatus)."%'";
$scanSQL .= " ORDER BY t.id DESC";

/* ── run active tab query ─────────────────────────────────────────────── */
$allRows = [];
if($tab === 'network') {
    $r = $conn->query($netSQL);
    if($r) while($row=$r->fetch_assoc()) $allRows[]=$row;
} else {
    $r = $conn->query($scanSQL);
    if($r) while($row=$r->fetch_assoc()) $allRows[]=$row;
}
$total = count($allRows);
$pages = max(1, ceil($total/$perPage));
$page  = min($page, $pages);
$rows  = array_slice($allRows, ($page-1)*$perPage, $perPage);

$_GET['view'] = 'network';
include 'header.php';
?>

<!-- ── STATS ROW ─────────────────────────────────────────────────────── -->
<div class="stats-row">
  <!-- These 4 always show -->
  <div class="stat-card">
    <div class="stat-accent blue"></div>
    <div class="stat-icon blue"><i class="ti ti-devices"></i></div>
    <div class="stat-label">Total PCs</div>
    <div class="stat-value" style="color:#6366f1"><?= $hw_count ?></div>
    <div class="stat-sub">registered devices</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent green"></div>
    <div class="stat-icon green"><i class="ti ti-wifi"></i></div>
    <div class="stat-label">Connected</div>
    <div class="stat-value" style="color:#059669" id="stat_connected"><?= $conn_count ?></div>
    <div class="stat-sub">online right now</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent red"></div>
    <div class="stat-icon red"><i class="ti ti-wifi-off"></i></div>
    <div class="stat-label">Not Connected</div>
    <div class="stat-value" style="color:#dc2626" id="stat_notconn"><?= $notconn_count ?></div>
    <div class="stat-sub">unreachable</div>
  </div>
  <!-- Scan summary — updates live during scan -->
  <div class="stat-card">
    <div class="stat-accent amber"></div>
    <div class="stat-icon amber"><i class="ti ti-radar"></i></div>
    <div class="stat-label">Last Scan</div>
    <div class="stat-value" style="color:#d97706;font-size:11px;margin-top:6px" id="stat_lastscan"><?= htmlspecialchars($lastScan) ?></div>
    <div class="stat-sub" id="stat_scaninfo">scan result</div>
  </div>
</div>

<!-- ── SCAN PROGRESS BAR (hidden until scan starts) ────────────────── -->
<div id="scan_progress_bar" style="display:none;margin-bottom:12px;">
  <div style="background:#1e1b4b;border-radius:10px;padding:12px 16px;color:#fff;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
      <span style="font-size:12px;font-weight:600;">
        <i class="ti ti-radar" style="margin-right:4px"></i>
        Scanning: <span id="cur_ip" style="color:#a5b4fc;font-family:monospace">—</span>
      </span>
      <span style="font-size:11px;">
        <span id="done_n" style="color:#a5b4fc;font-weight:700">0</span>/<span id="tot_n">0</span>
        &nbsp;&nbsp;
        <span style="color:#4ade80">✓ <b id="conn_n">0</b> Connected</span>
        &nbsp;&nbsp;
        <span style="color:#f87171">✗ <b id="disc_n">0</b> Not Connected</span>
      </span>
    </div>
    <div style="background:rgba(255,255,255,.15);border-radius:99px;height:8px;overflow:hidden">
      <div id="prog_bar" style="height:100%;width:0%;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:99px;transition:width .15s"></div>
    </div>
  </div>
</div>

<!-- ── MAIN PANEL ────────────────────────────────────────────────────── -->
<div class="panel">
  <div class="panel-head">
    <!-- TABS -->
    <div style="display:flex;gap:0;align-items:center">
      <a href="?tab=network&ip=<?=urlencode($fIP)?>&pc=<?=urlencode($fPC)?>&status=<?=urlencode($fStatus)?>&date=<?=urlencode($fDate)?>"
         style="padding:6px 18px;font-size:12px;font-weight:600;border-radius:6px 6px 0 0;text-decoration:none;
                <?= $tab==='network' ? 'background:#6366f1;color:#fff' : 'background:#f1f5f9;color:#64748b' ?>">
        <i class="ti ti-topology-star" style="font-size:12px"></i>&nbsp;Network View
        <span style="background:<?= $tab==='network'?'rgba(255,255,255,.25)':'#e2e8f0'?>;color:<?= $tab==='network'?'#fff':'#64748b'?>;border-radius:99px;padding:1px 7px;font-size:10px;margin-left:4px"><?= $conn_count + $notconn_count ?></span>
      </a>
      <a href="?tab=scan&ip=<?=urlencode($fIP)?>&pc=<?=urlencode($fPC)?>&status=<?=urlencode($fStatus)?>"
         style="padding:6px 18px;font-size:12px;font-weight:600;border-radius:6px 6px 0 0;text-decoration:none;margin-left:4px;
                <?= $tab==='scan' ? 'background:#6366f1;color:#fff' : 'background:#f1f5f9;color:#64748b' ?>">
        <i class="ti ti-radar" style="font-size:12px"></i>&nbsp;PC Scanner
        <span style="background:<?= $tab==='scan'?'rgba(255,255,255,.25)':'#e2e8f0'?>;color:<?= $tab==='scan'?'#fff':'#64748b'?>;border-radius:99px;padding:1px 7px;font-size:10px;margin-left:4px"><?= $scan_total ?></span>
      </a>
    </div>

    <!-- Right side buttons -->
    <div style="display:flex;gap:8px;align-items:center">
      <?php if($tab === 'scan'): ?>
        <?php foreach(['100.2.1','100.2.2','100.2.6'] as $sn): ?>
        <label style="display:flex;align-items:center;gap:4px;font-size:11px;cursor:pointer;color:#64748b">
          <input type="checkbox" class="subnet-chk" value="<?=$sn?>" checked style="accent-color:#6366f1">
          <?=$sn?>.x
        </label>
        <?php endforeach; ?>
        <button id="btnScan" onclick="startScan()" class="filter-btn">
          <i class="ti ti-player-play" style="font-size:12px"></i>&nbsp;Start Scan
        </button>
        <button id="btnStop" onclick="stopScan()" class="filter-btn" style="background:#ef4444;display:none">
          <i class="ti ti-player-stop" style="font-size:12px"></i>&nbsp;Stop
        </button>
      <?php endif; ?>
      <a href="?tab=<?=$tab?>&<?=http_build_query(['ip'=>$fIP,'pc'=>$fPC,'status'=>$fStatus,'date'=>$fDate])?>&export=1"
         class="filter-btn red"><i class="ti ti-download" style="font-size:12px"></i>&nbsp;Export</a>
      <div class="pager">
        <span class="pg-info">Page <?=$page?>/<?=$pages?> · <?=$total?></span>
        <?php $bq=http_build_query(['tab'=>$tab,'ip'=>$fIP,'pc'=>$fPC,'status'=>$fStatus,'date'=>$fDate]); ?>
        <a href="?<?=$bq?>&pg=<?=$page-1?>" class="pg-btn" <?=$page<=1?'style="pointer-events:none;opacity:.35"':''?>>‹</a>
        <a href="?<?=$bq?>&pg=<?=$page+1?>" class="pg-btn" <?=$page>=$pages?'style="pointer-events:none;opacity:.35"':''?>>›</a>
      </div>
    </div>
  </div>

  <!-- FILTER ROW -->
  <form method="GET" class="filter-row">
    <input type="hidden" name="tab" value="<?=$tab?>">
    <input name="ip" placeholder="Filter IP..." value="<?=htmlspecialchars($fIP)?>">
    <input name="pc" placeholder="Filter PC name..." value="<?=htmlspecialchars($fPC)?>">
    <?php if($tab==='network'): ?>
    <input type="date" name="date" value="<?=htmlspecialchars($fDate)?>">
    <?php endif; ?>
    <select name="status">
      <option value="">All Status</option>
      <?php if($tab==='network'): ?>
        <option value="CONNECTED" <?=$fStatus==='CONNECTED'?'selected':''?>>Connected</option>
        <option value="NOT CONNECTED" <?=$fStatus==='NOT CONNECTED'?'selected':''?>>Not Connected</option>
      <?php else: ?>
        <option value="online"    <?=$fStatus==='online'   ?'selected':''?>>Online</option>
        <option value="offline"   <?=$fStatus==='offline'  ?'selected':''?>>Offline</option>
        <option value="connected" <?=$fStatus==='connected'?'selected':''?>>Connected</option>
      <?php endif; ?>
    </select>
    <button type="submit" class="filter-btn"><i class="ti ti-search" style="font-size:12px"></i>&nbsp;Search</button>
    <a href="?tab=<?=$tab?>" class="filter-btn" style="background:#64748b"><i class="ti ti-x" style="font-size:12px"></i>&nbsp;Clear</a>
  </form>

  <!-- ══ NETWORK TAB TABLE ══ -->
  <?php if($tab === 'network'): ?>
  <table class="tbl">
    <thead><tr>
      <th>IP Address</th><th>PC Name</th><th>Status</th><th>Date / Time</th><th>Remote Access</th>
    </tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--color-text-tertiary)">
        <i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No records found
      </td></tr>
    <?php else: foreach($rows as $r): ?>
      <tr>
        <td class="mono"><?=htmlspecialchars($r['ip'])?></td>
        <td style="font-weight:500">
          <span style="cursor:pointer;color:#6366f1"
            onclick="openVNC('<?=htmlspecialchars($r['ip'])?>','<?=htmlspecialchars($r['computer_name']??'')?>')"
            title="Click to open VNC">
            <?=htmlspecialchars($r['computer_name']??'—')?>
          </span>
        </td>
        <td>
          <?php $st=strtoupper($r['status']??''); ?>
          <?php if($st==='CONNECTED'): ?>
            <span class="pill p-conn"><span class="dot dg"></span>Connected</span>
          <?php else: ?>
            <span class="pill p-disc"><span class="dot dr"></span>Not Connected</span>
          <?php endif; ?>
        </td>
        <td class="mono" style="font-size:10px;color:var(--color-text-tertiary)"><?=htmlspecialchars($r['date_time']??'—')?></td>
        <td>
          <button onclick="openVNC('<?=htmlspecialchars($r['ip'])?>','<?=htmlspecialchars($r['computer_name']??'')?>')"
            style="background:#6366f1;color:#fff;border:none;padding:4px 12px;border-radius:5px;cursor:pointer;font-size:12px;font-weight:600;margin-right:4px">
            🖥 VNC
          </button>
          <button onclick="downloadRDP('<?=htmlspecialchars($r['ip'])?>','<?=htmlspecialchars($r['computer_name']??'')?>')"
            style="background:transparent;color:#6366f1;border:1px solid #6366f1;padding:4px 10px;border-radius:5px;cursor:pointer;font-size:12px">
            RDP
          </button>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- ══ SCAN TAB ══ -->
  <?php else: ?>

  <!-- Live scan — small preview during scan -->
  <div id="scan_live" style="display:none;max-height:110px;overflow-y:auto;border-bottom:.5px solid #e2e8f0;">
    <table class="tbl">
      <thead><tr><th>IP</th><th>Status</th><th>PC Name</th><th>Time</th></tr></thead>
      <tbody id="scan_body"></tbody>
    </table>
  </div>

  <!-- SCAN RESULT PANEL — shows after scan complete -->
  <div id="scan_result_panel" style="display:none;border:.5px solid #e2e8f0;border-radius:8px;margin-bottom:12px;overflow:hidden;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f1f5f9;border-bottom:.5px solid #e2e8f0;">
      <div style="font-size:12px;font-weight:600;color:#1e1b4b;">
        <i class="ti ti-circle-check" style="color:#059669;margin-right:4px"></i>
        Scan Complete &mdash;
        <span id="res_conn_count" style="color:#059669;font-weight:700">0</span> Connected &nbsp;|&nbsp;
        <span id="res_disc_count" style="color:#dc2626;font-weight:700">0</span> Not Connected &nbsp;|&nbsp;
        <span style="color:#64748b" id="res_time"></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <button onclick="exportScanResult()" class="filter-btn" style="background:#16a34a;font-size:11px;padding:4px 10px;">
          <i class="ti ti-file-spreadsheet" style="font-size:11px"></i>&nbsp;Export Excel
        </button>
        <button onclick="document.getElementById('scan_result_panel').style.display='none'"
          style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:22px;line-height:1;padding:0 4px">&times;</button>
      </div>
    </div>
    <div style="padding:8px 14px;border-bottom:.5px solid #e2e8f0;display:flex;gap:8px;align-items:center;background:#fafafa;">
      <input id="res_filter" placeholder="&#128269; Filter IP or PC name..."
        oninput="filterResults(this.value)"
        style="padding:5px 10px;border:.5px solid #e2e8f0;border-radius:6px;font-size:11px;width:230px;outline:none;">
      <span style="font-size:11px;color:#64748b;">Showing: <b id="res_showing">0</b> connected PCs</span>
    </div>
    <div style="max-height:300px;overflow-y:auto;">
      <table class="tbl" id="res_table">
        <thead><tr>
          <th style="width:36px">#</th>
          <th>IP Address</th>
          <th>PC Name</th>
          <th style="text-align:center">Status</th>
          <th>Scanned At</th>
          <th style="text-align:center">Remote</th>
        </tr></thead>
        <tbody id="res_body"></tbody>
      </table>
    </div>
  </div>

  <!-- Last Scan DB Table — sirf connected PCs dikhao -->
  <?php
  $lastScanRows = $conn->query("
      SELECT ip, computer_name, date_time
      FROM lab_network_status n1
      WHERE status='CONNECTED'
        AND n1.id=(SELECT MAX(n2.id) FROM lab_network_status n2 WHERE n2.ip=n1.ip)
      ORDER BY n1.ip ASC
  ");
  $lastScanData = [];
  if($lastScanRows) while($lr=$lastScanRows->fetch_assoc()) $lastScanData[]=$lr;
  ?>
  <?php if(empty($lastScanData)): ?>
    <div style="text-align:center;padding:32px;color:#94a3b8;">
      <i class="ti ti-radar" style="font-size:28px;display:block;margin-bottom:8px"></i>
      <div style="font-size:12px">Abhi tak koi scan nahi hua ya koi PC connected nahi mila.</div>
      <div style="font-size:11px;margin-top:4px;color:#cbd5e1">Start Scan button dabao upar.</div>
    </div>
  <?php else: ?>
    <div style="padding:8px 14px;background:#f1f5f9;border-bottom:.5px solid #e2e8f0;font-size:11px;color:#64748b;">
      <i class="ti ti-database" style="margin-right:4px"></i>
      Last scan se <b style="color:#059669"><?=count($lastScanData)?> connected PCs</b> — DB se live data
    </div>
    <table class="tbl">
      <thead><tr>
        <th style="width:36px">#</th>
        <th>IP Address</th>
        <th>PC Name</th>
        <th style="text-align:center">Status</th>
        <th>Last Seen</th>
        <th style="text-align:center">Remote</th>
      </tr></thead>
      <tbody>
      <?php foreach($lastScanData as $idx=>$lr): ?>
        <tr>
          <td style="color:#94a3b8;text-align:center"><?=$idx+1?></td>
          <td class="mono" style="color:#6366f1;font-weight:600"><?=htmlspecialchars($lr['ip'])?></td>
          <td style="font-weight:500"><?=htmlspecialchars($lr['computer_name']??'—')?></td>
          <td style="text-align:center">
            <span class="pill p-conn"><span class="dot dg"></span>Connected</span>
          </td>
          <td class="mono" style="font-size:10px;color:#94a3b8"><?=htmlspecialchars($lr['date_time']??'—')?></td>
          <td style="text-align:center">
            <button onclick="openVNC('<?=htmlspecialchars($lr['ip'])?>','<?=htmlspecialchars($lr['computer_name']??'')?>')"
              style="background:#6366f1;color:#fff;border:none;padding:3px 10px;border-radius:5px;cursor:pointer;font-size:11px;font-weight:600;margin-right:3px">
              🖥 VNC
            </button>
            <button onclick="downloadRDP('<?=htmlspecialchars($lr['ip'])?>','<?=htmlspecialchars($lr['computer_name']??'')?>')"
              style="background:transparent;color:#6366f1;border:1px solid #6366f1;padding:3px 8px;border-radius:5px;cursor:pointer;font-size:11px">
              RDP
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  <?php endif; ?>

</div><!-- panel -->

<script>
/* ── VNC / RDP ─────────────────────────────────────────────────────── */
function openVNC(ip, pcName) {
    var url = 'vnc_setup/vnc.html?host=100.2.1.24&port=6080'
            + '&target_ip=' + encodeURIComponent(ip)
            + '&pc_name='   + encodeURIComponent(pcName||ip);
    window.open(url,'_blank','width=1060,height=720,resizable=yes');
}
function downloadRDP(ip, pcName) {
    window.location.href = 'vnc_setup/generate_rdp.php?ip='+encodeURIComponent(ip)+'&name='+encodeURIComponent(pcName||ip);
}

/* ── SCANNER ────────────────────────────────────────────────────────── */
let scanning=false, stopFlag=false;
let ipQueue=[], doneCount=0, connCount=0, discCount=0;
let connectedList = []; // stores all connected PCs for result panel

function buildQueue(){
    ipQueue=[];
    document.querySelectorAll('.subnet-chk:checked').forEach(c=>{
        for(let i=1;i<=254;i++) ipQueue.push(c.value+'.'+i);
    });
    document.getElementById('tot_n').textContent = ipQueue.length;
}
document.querySelectorAll && document.querySelectorAll('.subnet-chk').forEach(c=>c.addEventListener('change',buildQueue));
buildQueue();

async function scanBatch(ips){
    return Promise.allSettled(ips.map(ip =>
        fetch('scan_runner.php?ip='+encodeURIComponent(ip), {signal: AbortSignal.timeout(600)})
            .then(r=>r.json())
            .catch(()=>({ip, status:'NOT CONNECTED', name:'—'}))
    ));
}

async function startScan(){
    if(scanning) return;
    buildQueue();
    if(!ipQueue.length){ alert('Select at least one subnet.'); return; }

    scanning=true; stopFlag=false;
    doneCount=connCount=discCount=0;
    connectedList=[];

    document.getElementById('btnScan').style.display='none';
    document.getElementById('btnStop').style.display='';
    document.getElementById('scan_progress_bar').style.display='';
    document.getElementById('scan_live').style.display='';
    document.getElementById('scan_result_panel').style.display='none';
    document.getElementById('scan_body').innerHTML='';
    document.getElementById('res_body').innerHTML='';
    document.getElementById('prog_bar').style.width='0%';
    document.getElementById('done_n').textContent='0';
    document.getElementById('conn_n').textContent='0';
    document.getElementById('disc_n').textContent='0';

    const total = ipQueue.length;
    const BATCH = 20;

    for(let i=0; i<ipQueue.length && !stopFlag; i+=BATCH){
        const batch   = ipQueue.slice(i, i+BATCH);
        const results = await scanBatch(batch);

        results.forEach(res=>{
            if(stopFlag) return;
            const data = res.status==='fulfilled' ? res.value : {status:'NOT CONNECTED', name:'—'};
            doneCount++;

            const isConn = data.status==='CONNECTED';
            if(isConn) connCount++; else discCount++;

            document.getElementById('prog_bar').style.width = Math.round((doneCount/total)*100)+'%';
            document.getElementById('done_n').textContent   = doneCount;
            document.getElementById('conn_n').textContent   = connCount;
            document.getElementById('disc_n').textContent   = discCount;
            document.getElementById('cur_ip').textContent   = data.ip||'—';
            document.getElementById('stat_connected').textContent = connCount;
            document.getElementById('stat_notconn').textContent   = discCount;
            document.getElementById('stat_scaninfo').textContent  = doneCount+'/'+total+' scanned';

            if(isConn){
                const t = new Date().toLocaleTimeString();
                connectedList.push({ip: data.ip, name: data.name||'—', time: t});

                // small live preview
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="mono" style="color:#6366f1">${data.ip}</td>
                    <td><span class="pill p-conn"><span class="dot dg"></span>Connected</span></td>
                    <td style="font-weight:500">${data.name||'—'}</td>
                    <td class="mono" style="font-size:10px">${t}</td>`;
                document.getElementById('scan_body').prepend(tr);
            }
        });
        await new Promise(r=>setTimeout(r,5));
    }

    scanning=false;
    document.getElementById('btnScan').style.display='';
    document.getElementById('btnStop').style.display='none';
    document.getElementById('cur_ip').textContent='—';

    const scanTime = new Date().toLocaleTimeString();
    document.getElementById('stat_lastscan').textContent = scanTime;
    document.getElementById('stat_scaninfo').textContent = connCount+' connected / '+discCount+' not connected';

    // Show result panel
    showResultPanel(scanTime);
}

function showResultPanel(scanTime){
    document.getElementById('res_conn_count').textContent = connectedList.length;
    document.getElementById('res_disc_count').textContent = discCount;
    document.getElementById('res_time').textContent       = scanTime;
    document.getElementById('res_showing').textContent    = connectedList.length;
    document.getElementById('res_filter').value           = '';

    renderResultRows(connectedList);

    document.getElementById('scan_live').style.display='none';
    document.getElementById('scan_result_panel').style.display='';

    // scroll into view
    document.getElementById('scan_result_panel').scrollIntoView({behavior:'smooth', block:'start'});
}

function renderResultRows(list){
    const tbody = document.getElementById('res_body');
    tbody.innerHTML = '';
    list.forEach((pc, idx)=>{
        const tr = document.createElement('tr');
        tr.setAttribute('data-ip', pc.ip);
        tr.setAttribute('data-name', (pc.name||'—').toLowerCase());
        tr.innerHTML = `
            <td style="color:#94a3b8;text-align:center">${idx+1}</td>
            <td class="mono" style="color:#6366f1;font-weight:600">${pc.ip}</td>
            <td style="font-weight:500">${pc.name||'—'}</td>
            <td style="text-align:center"><span class="pill p-conn"><span class="dot dg"></span>Connected</span></td>
            <td class="mono" style="font-size:10px;color:#94a3b8">${pc.time}</td>
            <td style="text-align:center">
              <button onclick="openVNC('${pc.ip}','${pc.name||''}\`)"
                style="background:#6366f1;color:#fff;border:none;padding:3px 10px;border-radius:5px;cursor:pointer;font-size:11px;font-weight:600;margin-right:3px">
                🖥 VNC
              </button>
              <button onclick="downloadRDP('${pc.ip}','${pc.name||''}\`)"
                style="background:transparent;color:#6366f1;border:1px solid #6366f1;padding:3px 8px;border-radius:5px;cursor:pointer;font-size:11px">
                RDP
              </button>
            </td>`;
        tbody.appendChild(tr);
    });
    document.getElementById('res_showing').textContent = list.length;
}

function filterResults(q){
    q = q.toLowerCase().trim();
    const filtered = connectedList.filter(pc=>
        pc.ip.includes(q) || (pc.name||'—').toLowerCase().includes(q)
    );
    renderResultRows(filtered);
}

function exportScanResult(){
    if(!connectedList.length){ alert('No connected PCs to export.'); return; }
    const scanTime = document.getElementById('res_time').textContent;
    let html = '<html><head><meta charset="UTF-8"></head><body>';
    html += '<h2 style="font-family:Arial">NCR-CET IT Lab — Scan Result</h2>';
    html += '<p style="font-family:Arial;font-size:11pt">Scan Time: '+scanTime+' &nbsp;|&nbsp; Connected: '+connectedList.length+' &nbsp;|&nbsp; Not Connected: '+discCount+'</p>';
    html += '<table border="1" cellpadding="6" cellspacing="0" style="font-family:Arial;font-size:10pt;border-collapse:collapse">';
    html += '<tr style="background:#1E1B4B;color:#fff"><th>#</th><th>IP Address</th><th>PC Name</th><th>Status</th><th>Scanned At</th></tr>';
    connectedList.forEach((pc,i)=>{
        html += `<tr style="background:${i%2===0?'#ffffff':'#f8fafc'}">
            <td style="text-align:center">${i+1}</td>
            <td style="color:#6366F1;font-weight:bold">${pc.ip}</td>
            <td style="font-weight:bold">${pc.name||'—'}</td>
            <td style="color:#059669;font-weight:bold">Connected</td>
            <td>${pc.time}</td></tr>`;
    });
    html += '</table></body></html>';
    const blob = new Blob([html], {type:'application/vnd.ms-excel'});
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = 'scan_result_'+new Date().toISOString().slice(0,10)+'.xls';
    a.click();
}

function stopScan(){
    stopFlag=true;
    const scanTime = new Date().toLocaleTimeString();
    document.getElementById('stat_lastscan').textContent = scanTime;
    document.getElementById('stat_scaninfo').textContent = connCount+' connected / '+discCount+' not connected';
    if(connectedList.length > 0) showResultPanel(scanTime);
}
</script>

<?php include 'footer.php'; ?>