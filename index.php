<?php
include 'auth.php';
include 'db.php';

// ─── helper functions ───────────────────────────────────────────────────────
function ramToGB($ram){
    $ram = strtoupper(trim($ram));
    if(strpos($ram,'MB')!==false) return floatval($ram)/1024;
    if(strpos($ram,'GB')!==false) return floatval($ram);
    return floatval($ram);
}
function getCategory($cpu,$ram){
    $c=strtoupper($cpu); $r=ramToGB($ram);
    if((strpos($c,'I3')!==false||strpos($c,'I5')!==false||strpos($c,'I7')!==false||strpos($c,'I9')!==false)&&$r>=8) return 'A';
    if(strpos($c,'I3')!==false||strpos($c,'I5')!==false||strpos($c,'I7')!==false||strpos($c,'I9')!==false) return 'B';
    if((strpos($c,'DUO')!==false||strpos($c,'PENTIUM')!==false)&&$r>=4) return 'B';
    return 'C';
}
function osBadge($os){
    if(strpos($os,'11')!==false) return '<span class="os-badge win11">Win 11</span>';
    if(strpos($os,'10')!==false) return '<span class="os-badge win10">Win 10</span>';
    return '<span class="os-badge win7">Win 7</span>';
}
function mbBadge($mb){
    $m=strtoupper($mb);
    if(strpos($m,'LENOVO')!==false) return '<span class="brand-lenovo">Lenovo</span>';
    if(strpos($m,'HP')!==false||strpos($m,'HEWLETT')!==false) return '<span class="brand-hp">HP</span>';
    return '<span class="brand-dell">Dell</span>';
}
function stPill($s){
    $sl=strtolower($s);
    if($sl==='connected') return '<span class="pill p-conn"><span class="dot dg"></span>Connected</span>';
    if(strpos($sl,'not')!==false) return '<span class="pill p-disc"><span class="dot dr"></span>Not Connected</span>';
    return '<span class="pill p-unk"><span class="dot" style="background:#9ca3af"></span>Unknown</span>';
}

// ─── get/filter params ───────────────────────────────────────────────────────
$view     = $_GET['view']     ?? 'network';
$fIP      = trim($_GET['ip']  ?? '');
$fPC      = trim($_GET['pc']  ?? '');
$fDate    = trim($_GET['date'] ?? '');
$fStatus  = trim($_GET['status'] ?? '');
$fFloor   = trim($_GET['floor']  ?? '');
$fCat     = trim($_GET['category'] ?? '');
$page     = max(1, intval($_GET['pg'] ?? 1));
$perPage  = 15;

// ─── build SQL function ──────────────────────────────────────────────────────
function buildSQL($conn,$view,$fIP,$fPC,$fDate,$fStatus,$fFloor,$fCat){
    if($view==='network'){
        $sql="SELECT * FROM lab_network_status n1 WHERE 1=1";
        if($fIP) $sql.=" AND n1.ip LIKE '%".$conn->real_escape_string($fIP)."%'";
        if($fPC) $sql.=" AND n1.computer_name LIKE '%".$conn->real_escape_string($fPC)."%'";
        if($fDate) $sql.=" AND DATE(n1.date_time)='".$conn->real_escape_string($fDate)."'";
        if($fStatus) $sql.=" AND n1.status='".$conn->real_escape_string($fStatus)."'";
        $sql.=" AND n1.id=(SELECT MAX(n2.id) FROM lab_network_status n2 WHERE n2.ip=n1.ip";
        if($fDate) $sql.=" AND DATE(n2.date_time)='".$conn->real_escape_string($fDate)."'";
        $sql.=") ORDER BY n1.ip ASC";

    } elseif($view==='software'){
        $sql="SELECT * FROM software_status WHERE 1=1";
        if($fIP) $sql.=" AND ip_address LIKE '%".$conn->real_escape_string($fIP)."%'";
        if($fPC) $sql.=" AND computer_name LIKE '%".$conn->real_escape_string($fPC)."%'";
        $sql.=" ORDER BY computer_name ASC";

    } else {
        // ── hardware view — uses hardware_master (no duplicates, always latest) ──
        $sql="SELECT * FROM hardware_master h1 WHERE 1=1";
        if($fIP) $sql.=" AND h1.ip_address LIKE '%".$conn->real_escape_string($fIP)."%'";
        if($fPC) $sql.=" AND h1.computer_name LIKE '%".$conn->real_escape_string($fPC)."%'";
        if($fDate) $sql.=" AND DATE(h1.date_time)='".$conn->real_escape_string($fDate)."'";
        if($fCat){
            $all=$conn->query("SELECT id,cpu_name,ram_total,category_manual FROM hardware_master");
            $ids=[];
            while($r=$all->fetch_assoc()){
                $effective = !empty($r['category_manual']) ? $r['category_manual'] : getCategory($r['cpu_name'],$r['ram_total']);
                if($effective===$fCat) $ids[]=$r['id'];
            }
            $sql.= count($ids) ? " AND h1.id IN(".implode(',',$ids).")" : " AND 1=0";
        }
        $sql.=" ORDER BY h1.computer_name ASC";
        // Note: hardware_master already has one row per motherboard_serial (no subquery needed)
    }
    return $sql;
}

// ─── export check ───────────────────────────────────────────────────────────
if(isset($_GET['export']) && $_GET['export']==='1'){
    header("Content-Type:application/vnd.ms-excel");
    header("Content-Disposition:attachment;filename=it_report_{$view}.xls");
    header("Pragma:no-cache"); header("Expires:0");
    $expSql = buildSQL($conn,$view,$fIP,$fPC,$fDate,$fStatus,$fFloor,$fCat);
    $er = $conn->query($expSql);
    echo "<table border='1'>";
    if($view==='network'){
        echo "<tr><th>IP</th><th>PC</th><th>Status</th><th>Date</th></tr>";
        while($r=$er->fetch_assoc()) echo "<tr><td>{$r['ip']}</td><td>{$r['computer_name']}</td><td>{$r['status']}</td><td>{$r['date_time']}</td></tr>";
    } elseif($view==='software'){
        echo "<tr><th>PC</th><th>IP</th><th>Software</th><th>Version</th><th>Publisher</th><th>Install Date</th></tr>";
        while($r=$er->fetch_assoc()) echo "<tr><td>{$r['computer_name']}</td><td>{$r['ip_address']}</td><td>{$r['software_name']}</td><td>{$r['version']}</td><td>{$r['publisher']}</td><td>{$r['install_date']}</td></tr>";
    } else {
        echo "<tr><th>IP</th><th>PC</th><th>Brand</th><th>Category</th><th>OS</th><th>RAM</th><th>CPU</th><th>MB Serial</th><th>Disk</th><th>Remarks</th><th>Date of Purchase</th></tr>";
        while($r=$er->fetch_assoc()){
            $cat = !empty($r['category_manual']) ? $r['category_manual'] : getCategory($r['cpu_name'],$r['ram_total']);
            echo "<tr><td>{$r['ip_address']}</td><td>{$r['computer_name']}</td><td>{$r['brand_name']}</td><td>{$cat}</td><td>{$r['os_name']}</td><td>{$r['ram_total']}</td><td>{$r['cpu_name']}</td><td>{$r['motherboard_serial']}</td><td>{$r['hard_disk']}</td><td>{$r['remarks']}</td><td>{$r['date_of_purchase']}</td></tr>";
        }
    }
    echo "</table>"; exit;
}

// ─── counters ────────────────────────────────────────────────────────────────
// hardware_master use karo — duplicate free count
$hw_res        = $conn->query("SELECT COUNT(DISTINCT computer_name) t FROM hardware_master");
$hw_count      = ($hw_res ? $hw_res->fetch_assoc()['t'] : 0) ?? 0;

$conn_res      = $conn->query("SELECT COUNT(DISTINCT ip) t FROM lab_network_status WHERE status='connected'");
$conn_count    = ($conn_res ? $conn_res->fetch_assoc()['t'] : 0) ?? 0;

$notconn_res   = $conn->query("SELECT COUNT(DISTINCT ip) t FROM lab_network_status WHERE status='not connected'");
$notconn_count = ($notconn_res ? $notconn_res->fetch_assoc()['t'] : 0) ?? 0;

$sw_res        = $conn->query("SELECT COUNT(*) t FROM software_status");
$sw_count      = ($sw_res ? $sw_res->fetch_assoc()['t'] : 0) ?? 0;

$unk_count     = max(0, $hw_count - $conn_count - $notconn_count);

// ─── location/rooms redirect ─────────────────────────────────────────────────
if($view==='location'){ header("Location: pclocation.php"); exit; }
if($view==='rooms'){    header("Location: room_management.php"); exit; }
if($view==='network'){  header("Location: network_monitor.php"); exit; }

// ─── run main query ──────────────────────────────────────────────────────────
$allRows = []; $total = 0; $pages = 1; $rows = [];
if(in_array($view,['network','hardware','software'])){
    $sql    = buildSQL($conn,$view,$fIP,$fPC,$fDate,$fStatus,$fFloor,$fCat);
    $result = $conn->query($sql);
    if($result) while($r=$result->fetch_assoc()) $allRows[]=$r;
    $total  = count($allRows);
    $pages  = max(1, ceil($total/$perPage));
    $page   = min($page,$pages);
    $rows   = array_slice($allRows,($page-1)*$perPage,$perPage);
}

// logs data
$logs_count = 0; $logsRows = [];
if($view==='logs'){
    $lRes = $conn->query("SELECT * FROM search_logs ORDER BY id DESC");
    if($lRes) while($r=$lRes->fetch_assoc()) $logsRows[]=$r;
    $logs_count = count($logsRows);
    $total=$logs_count; $pages=max(1,ceil($total/$perPage));
    $page=min($page,$pages);
    $rows=array_slice($logsRows,($page-1)*$perPage,$perPage);
}

// users data
$usersRows = [];
if($view==='users'){
    $uRes = $conn->query("SELECT * FROM users ORDER BY id ASC");
    if($uRes) while($r=$uRes->fetch_assoc()) $usersRows[]=$r;
}

include 'header.php';
?>

<?php /* ========== LOGS VIEW ========== */ ?>
<?php if($view==='logs'): ?>
<div class="stats-row">
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-clipboard-list"></i></div><div class="stat-label">Total Logs</div><div class="stat-value" style="color:#6366f1"><?=$logs_count?></div><div class="stat-sub">search_logs table</div></div>
  <?php $catAcount=0; foreach($logsRows as $l){ if(($l['category']??'')==='A') $catAcount++; } ?>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-tag"></i></div><div class="stat-label">Category A</div><div class="stat-value" style="color:#d97706"><?=$catAcount?></div><div class="stat-sub">hardware views</div></div>
  <div class="stat-card"><div class="stat-accent green"></div><div class="stat-icon green"><i class="ti ti-calendar"></i></div><div class="stat-label">Page</div><div class="stat-value" style="color:#059669;font-size:18px"><?=$page?>/<?=$pages?></div><div class="stat-sub">pagination</div></div>
  <div class="stat-card"><div class="stat-accent purple"></div><div class="stat-icon purple"><i class="ti ti-eye"></i></div><div class="stat-label">Showing</div><div class="stat-value" style="color:#7c3aed"><?=count($rows)?></div><div class="stat-sub">current page</div></div>
</div>
<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-history"></i>Search Logs <span class="panel-badge"><?=$logs_count?> total</span></div>
    <div class="pager">
      <span class="pg-info">Page <?=$page?>/<?=$pages?></span>
      <a href="?view=logs&pg=<?=$page-1?>" class="pg-btn" <?=$page<=1?'style="pointer-events:none;opacity:.35"':''?>>‹</a>
      <a href="?view=logs&pg=<?=$page+1?>" class="pg-btn" <?=$page>=$pages?'style="pointer-events:none;opacity:.35"':''?>>›</a>
    </div>
  </div>
  <table class="tbl">
    <thead><tr><th>ID</th><th>User ID</th><th>IP</th><th>PC</th><th>Category</th><th>View Type</th><th>Created At</th></tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--color-text-tertiary)"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No logs found</td></tr>
    <?php else: foreach($rows as $r): ?>
    <tr>
      <td style="color:var(--color-text-tertiary)"><?=$r['id']?></td>
      <td><?=htmlspecialchars($r['user_id']??'—')?></td>
      <td class="mono"><?=htmlspecialchars($r['ip_address']??$r['ip']??'—')?></td>
      <td><?=htmlspecialchars($r['pc_name']??$r['pc']??'—')?></td>
      <td><span class="pill p-blue"><?=htmlspecialchars($r['category']??'—')?></span></td>
      <td><span class="pill p-teal"><?=htmlspecialchars($r['view_type']??$r['view']??'—')?></span></td>
      <td class="mono" style="font-size:10px"><?=htmlspecialchars($r['created_at']??'—')?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; exit; endif; ?>

<?php /* ========== USERS VIEW ========== */ ?>
<?php if($view==='users'): ?>
<div class="two-col">
  <div class="panel">
    <div class="panel-head"><div class="panel-title"><i class="ti ti-users"></i>Users <span class="panel-badge">users table</span></div></div>
    <table class="tbl">
      <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Category</th><th>Created</th></tr></thead>
      <tbody>
      <?php if(empty($usersRows)): ?>
        <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--color-text-tertiary)">No users found</td></tr>
      <?php else: foreach($usersRows as $u): ?>
      <tr>
        <td style="color:var(--color-text-tertiary)"><?=$u['id']?></td>
        <td>
          <div style="display:flex;align-items:center;gap:7px">
            <div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:500"><?=strtoupper(substr($u['username'],0,2))?></div>
            <span style="font-weight:500"><?=htmlspecialchars($u['username'])?></span>
          </div>
        </td>
        <td><span class="pill p-blue"><?=htmlspecialchars($u['role']??'user')?></span></td>
        <td><span class="pill p-gray"><?=htmlspecialchars($u['category']??'—')?></span></td>
        <td class="mono" style="font-size:10px"><?=htmlspecialchars($u['created_at']??'—')?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <div class="panel-head"><div class="panel-title"><i class="ti ti-table-column"></i>Table Schema — users</div></div>
    <table class="tbl">
      <thead><tr><th>Field</th><th>Type</th><th>Notes</th></tr></thead>
      <tbody>
        <?php foreach([['id','int(11)','PK, AUTO_INCREMENT'],['username','varchar(100)','UNIQUE'],['password','varchar(255)','hashed'],['role','enum(admin,user)','default: user'],['category','varchar(10)','e.g. A'],['created_at','timestamp','DEFAULT CURRENT_TIMESTAMP']] as $f): ?>
        <tr>
          <td class="mono" style="font-weight:500;color:#6366f1"><?=$f[0]?></td>
          <td class="mono" style="color:var(--color-text-secondary)"><?=$f[1]?></td>
          <td style="font-size:10px;color:var(--color-text-tertiary)"><?=$f[2]?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'footer.php'; exit; endif; ?>

<?php /* ========== STATS ROW ========== */ ?>
<div class="stats-row">
<?php if($view==='network'): ?>
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-devices"></i></div><div class="stat-label">Total PCs</div><div class="stat-value" style="color:#6366f1"><?=$hw_count?></div><div class="stat-sub">registered devices</div></div>
  <div class="stat-card"><div class="stat-accent green"></div><div class="stat-icon green"><i class="ti ti-wifi"></i></div><div class="stat-label">Connected</div><div class="stat-value" style="color:#059669"><?=$conn_count?></div><div class="stat-sub">online right now</div></div>
  <div class="stat-card"><div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-wifi-off"></i></div><div class="stat-label">Not Connected</div><div class="stat-value" style="color:#dc2626"><?=$notconn_count?></div><div class="stat-sub">unreachable</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-help"></i></div><div class="stat-label">Unknown</div><div class="stat-value" style="color:#d97706"><?=$unk_count?></div><div class="stat-sub">no status data</div></div>
<?php elseif($view==='hardware'): ?>
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-cpu"></i></div><div class="stat-label">Total Hardware</div><div class="stat-value" style="color:#6366f1"><?=$hw_count?></div><div class="stat-sub">hardware_master table</div></div>
  <div class="stat-card"><div class="stat-accent green"></div><div class="stat-icon green"><i class="ti ti-wifi"></i></div><div class="stat-label">Connected</div><div class="stat-value" style="color:#059669"><?=$conn_count?></div><div class="stat-sub">currently online</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-tag"></i></div><div class="stat-label">Showing</div><div class="stat-value" style="color:#d97706"><?=$total?></div><div class="stat-sub">filtered results</div></div>
  <div class="stat-card"><div class="stat-accent purple"></div><div class="stat-icon purple"><i class="ti ti-list"></i></div><div class="stat-label">Page</div><div class="stat-value" style="color:#7c3aed;font-size:18px"><?=$page?>/<?=$pages?></div><div class="stat-sub">pagination</div></div>
<?php elseif($view==='software'): ?>
  <div class="stat-card"><div class="stat-accent teal"></div><div class="stat-icon teal"><i class="ti ti-package"></i></div><div class="stat-label">Total Software</div><div class="stat-value" style="color:#0d9488"><?=$sw_count?></div><div class="stat-sub">software_status table</div></div>
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-devices"></i></div><div class="stat-label">PCs</div><div class="stat-value" style="color:#6366f1"><?=$hw_count?></div><div class="stat-sub">hardware registered</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-filter"></i></div><div class="stat-label">Showing</div><div class="stat-value" style="color:#d97706"><?=$total?></div><div class="stat-sub">filtered results</div></div>
  <div class="stat-card"><div class="stat-accent purple"></div><div class="stat-icon purple"><i class="ti ti-list"></i></div><div class="stat-label">Page</div><div class="stat-value" style="color:#7c3aed;font-size:18px"><?=$page?>/<?=$pages?></div><div class="stat-sub">pagination</div></div>
<?php endif; ?>
</div>

<?php /* ========== MAIN PANEL ========== */ ?>
<div class="panel">

  <?php /* ── PANEL HEAD ── */ ?>
  <div class="panel-head">
    <div class="panel-title">
      <?php if($view==='network'): ?><i class="ti ti-topology-star"></i>Network Devices <span class="panel-badge"><?=$total?> results</span>
      <?php elseif($view==='hardware'): ?><i class="ti ti-cpu"></i>Hardware Inventory <span class="panel-badge"><?=$total?> results</span>
      <?php elseif($view==='software'): ?><i class="ti ti-package"></i>Software Inventory <span class="panel-badge"><?=$total?> results</span>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <?php if($view==='hardware'): ?>
      <!-- BULK SYNC BUTTON -->
      <button id="btnBulkSync" onclick="bulkSync()"
        style="display:flex;align-items:center;gap:5px;padding:6px 14px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;box-shadow:0 2px 8px rgba(99,102,241,.3)">
        <i class="ti ti-refresh" style="font-size:13px"></i> Sync to Master
      </button>
      <?php endif; ?>
      <div class="pager">
        <span class="pg-info">Page <?=$page?> of <?=$pages?> · <?=$total?> records</span>
        <?php $baseQ=http_build_query(['view'=>$view,'ip'=>$fIP,'pc'=>$fPC,'date'=>$fDate,'status'=>$fStatus,'category'=>$fCat]); ?>
        <a href="?<?=$baseQ?>&pg=<?=$page-1?>" class="pg-btn" <?=$page<=1?'style="pointer-events:none;opacity:.35"':''?>>‹</a>
        <a href="?<?=$baseQ?>&pg=<?=$page+1?>" class="pg-btn" <?=$page>=$pages?'style="pointer-events:none;opacity:.35"':''?>>›</a>
      </div>
    </div>
  </div>

  <?php /* ── FILTER ROW ── */ ?>
  <form method="GET" class="filter-row">
    <input type="hidden" name="view" value="<?=htmlspecialchars($view)?>">
    <input name="ip" placeholder="Filter IP..." value="<?=htmlspecialchars($fIP)?>">
    <input name="pc" placeholder="Filter PC name..." value="<?=htmlspecialchars($fPC)?>">
    <?php if($view!=='software'): ?>
    <input type="date" name="date" value="<?=htmlspecialchars($fDate)?>">
    <?php endif; ?>
    <?php if($view==='network'): ?>
    <select name="status">
      <option value="">All Status</option>
      <option value="connected" <?=$fStatus==='connected'?'selected':''?>>Connected</option>
      <option value="not connected" <?=$fStatus==='not connected'?'selected':''?>>Not Connected</option>
    </select>
    <?php elseif($view==='hardware'): ?>
    <select name="category">
      <option value="">All Categories</option>
      <option value="A" <?=$fCat==='A'?'selected':''?>>Category A</option>
      <option value="B" <?=$fCat==='B'?'selected':''?>>Category B</option>
      <option value="C" <?=$fCat==='C'?'selected':''?>>Category C</option>
    </select>
    <?php endif; ?>
    <button type="submit" class="filter-btn"><i class="ti ti-search" style="font-size:13px"></i>Search</button>
    <a href="?view=<?=htmlspecialchars($view)?>&export=1&ip=<?=urlencode($fIP)?>&pc=<?=urlencode($fPC)?>&date=<?=urlencode($fDate)?>&status=<?=urlencode($fStatus)?>&category=<?=urlencode($fCat)?>" class="filter-btn red"><i class="ti ti-download" style="font-size:13px"></i>Export</a>
  </form>

  <?php /* ── TABLE ── */ ?>
  <?php if($view==='network'): ?>
  <table class="tbl">
    <thead><tr>
      <th>IP Address</th><th>PC Name</th><th>Status</th><th>Date / Time</th><th>Remote Access</th>
    </tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--color-text-tertiary)"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No records found</td></tr>
    <?php else: foreach($rows as $r): ?>
      <tr>
        <td class="mono"><?=htmlspecialchars($r['ip'])?></td>
        <td style="font-weight:500">
          <span style="cursor:pointer;color:var(--color-primary,#6366f1)" onclick="openVNC('<?=htmlspecialchars($r['ip'])?>','<?=htmlspecialchars($r['computer_name']??'')?>')" title="Click to open VNC">
            <?=htmlspecialchars($r['computer_name']??'—')?>
          </span>
        </td>
        <td><?=stPill($r['status']??'')?></td>
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

  <?php elseif($view==='hardware'): ?>
  <div style="overflow-x:auto">
  <table class="tbl" style="min-width:1600px">
    <thead><tr>
      <th>PC Name</th><th>IP</th><th>Brand</th><th>Category</th>
      <th>OS Name</th><th>OS Version</th>
      <th>RAM Total</th><th>RAM Free</th>
      <th>CPU Name</th><th>CPU Manufacturer</th><th>CPU Serial</th><th>CPU Speed</th><th>CPU Cores</th><th>Logical CPUs</th><th>Architecture</th>
      <th>MB Manufacturer</th><th>MB Serial</th>
      <th>Disk</th><th>User</th>
      <th style="min-width:90px">Category</th>
      <th style="min-width:150px">Remarks</th>
      <th style="min-width:130px">Date of Purchase</th>
      <th style="min-width:80px">Update</th>
    </tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="23" style="text-align:center;padding:24px;color:var(--color-text-tertiary)"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No records found</td></tr>
    <?php else: foreach($rows as $r):
      $autoCat = getCategory($r['cpu_name']??'',$r['ram_total']??'');
      $manCat  = $r['category_manual'] ?? '';
      $showCat = $manCat ?: $autoCat;
      $catPill = $showCat==='A'?'p-blue':($showCat==='B'?'p-teal':'p-amber');
    ?>
      <tr id="row-<?=$r['id']?>">
        <td style="font-weight:500;white-space:nowrap"><?=htmlspecialchars($r['computer_name']??'—')?></td>
        <td class="mono"><?=htmlspecialchars($r['ip_address']??'—')?></td>
        <td><?=mbBadge($r['brand_name']??$r['motherboard_manufacturer']??'')?></td>
        <td><span class="pill <?=$catPill?>" id="catpill-<?=$r['id']?>"><?=$showCat?></span></td>
        <td><?=osBadge($r['os_name']??'')?></td>
        <td class="mono" style="font-size:10px"><?=htmlspecialchars($r['os_version']??'—')?></td>
        <td class="mono"><?=htmlspecialchars($r['ram_total']??'—')?></td>
        <td class="mono"><?=htmlspecialchars($r['ram_free']??'—')?></td>
        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($r['cpu_name']??'')?>"><?=htmlspecialchars($r['cpu_name']??'—')?></td>
        <td><?=htmlspecialchars($r['cpu_manufacturer']??'—')?></td>
        <td class="mono" style="font-size:9px"><?=htmlspecialchars($r['cpu_serial_number']??'—')?></td>
        <td class="mono"><?=htmlspecialchars($r['cpu_speed']??'—')?></td>
        <td style="text-align:center"><?=htmlspecialchars($r['cpu_cores']??'—')?></td>
        <td style="text-align:center"><?=htmlspecialchars($r['logical_processors']??'—')?></td>
        <td><span class="pill p-gray"><?=htmlspecialchars($r['architecture']??'—')?></span></td>
        <td><?=htmlspecialchars($r['motherboard_manufacturer']??'—')?></td>
        <td class="mono" style="font-size:9px"><?=htmlspecialchars($r['motherboard_serial']??'—')?></td>
        <td class="mono" style="font-size:10px"><?=htmlspecialchars($r['hard_disk']??'—')?></td>
        <td style="font-size:10px"><?=htmlspecialchars($r['logged_user']??'—')?></td>

        <!-- ── Category Manual ── -->
        <td>
          <select id="cat-<?=$r['id']?>" style="height:26px;padding:0 4px;font-size:11px;border:1px solid #d1d5db;border-radius:5px;background:#fff;width:70px">
            <option value="" <?=$manCat===''?'selected':''?>>Auto</option>
            <option value="A" <?=$manCat==='A'?'selected':''?>>A</option>
            <option value="B" <?=$manCat==='B'?'selected':''?>>B</option>
            <option value="C" <?=$manCat==='C'?'selected':''?>>C</option>
          </select>
        </td>

        <!-- ── Remarks ── -->
        <td>
          <input type="text" id="rem-<?=$r['id']?>"
            value="<?=htmlspecialchars($r['remarks']??'')?>"
            placeholder="Add remark..."
            style="width:140px;height:26px;padding:0 6px;font-size:11px;border:1px solid #d1d5db;border-radius:5px">
        </td>

        <!-- ── Date of Purchase ── -->
        <td>
          <input type="date" id="dop-<?=$r['id']?>"
            value="<?=htmlspecialchars($r['date_of_purchase']??'')?>"
            style="width:120px;height:26px;padding:0 4px;font-size:11px;border:1px solid #d1d5db;border-radius:5px">
        </td>

        <!-- ── Update + Sync Buttons ── -->
        <td style="white-space:nowrap">
          <button id="upd-<?=$r['id']?>" onclick="updateHW(<?=$r['id']?>)"
            style="padding:4px 10px;font-size:11px;border:1px solid #6366f1;border-radius:5px;background:#fff;color:#6366f1;cursor:pointer;margin-bottom:3px;display:block;width:100%">
            💾 Save
          </button>
          <button id="sync-<?=$r['id']?>" onclick="singleSync(<?=$r['id']?>)"
            style="padding:4px 10px;font-size:11px;border:1px solid #8b5cf6;border-radius:5px;background:#fff;color:#8b5cf6;cursor:pointer;display:block;width:100%">
            🔄 Sync
          </button>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>

  <?php elseif($view==='software'): ?>
  <table class="tbl">
    <thead><tr>
      <th>PC Name</th><th>IP</th><th>Software Name</th><th>Version</th><th>Publisher</th><th>Install Date</th>
    </tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--color-text-tertiary)"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No records found</td></tr>
    <?php else: foreach($rows as $r): ?>
      <tr>
        <td style="font-weight:500"><?=htmlspecialchars($r['computer_name']??'—')?></td>
        <td class="mono"><?=htmlspecialchars($r['ip_address']??'—')?></td>
        <td><?=htmlspecialchars($r['software_name']??'—')?></td>
        <td class="mono"><?=htmlspecialchars($r['version']??'—')?></td>
        <td style="font-size:10px;color:var(--color-text-secondary)"><?=htmlspecialchars($r['publisher']??'—')?></td>
        <td class="mono" style="font-size:10px"><?=htmlspecialchars($r['install_date']??'—')?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  <?php endif; ?>

</div><!-- panel -->

<script>
// ── Bulk Sync — Sab PCs master mein ────────────────────────
function bulkSync() {
    var btn = document.getElementById('btnBulkSync');
    btn.innerHTML = '<i class="ti ti-loader" style="font-size:13px"></i> Syncing...';
    btn.disabled  = true;

    fetch('sync_master.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'mode=bulk'
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.ok){
            btn.innerHTML = '✅ ' + data.msg;
            btn.style.background = 'linear-gradient(135deg,#059669,#10b981)';
            setTimeout(function(){
                btn.innerHTML = '<i class="ti ti-refresh" style="font-size:13px"></i> Sync to Master';
                btn.style.background = 'linear-gradient(135deg,#6366f1,#8b5cf6)';
                btn.disabled = false;
            }, 3000);
        } else {
            btn.innerHTML = '❌ Error';
            btn.style.background = '#ef4444';
            btn.disabled = false;
            alert('Error: ' + data.msg);
        }
    })
    .catch(function(){
        btn.innerHTML = '❌ Error';
        btn.disabled = false;
    });
}

// ── Single PC Sync ──────────────────────────────────────────
function singleSync(id) {
    var btn = document.getElementById('sync-' + id);
    btn.textContent = '⏳';
    btn.disabled    = true;

    fetch('sync_master.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'mode=single&id=' + id
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.ok){
            btn.textContent      = '✅ Synced';
            btn.style.color      = '#059669';
            btn.style.borderColor= '#059669';
            setTimeout(function(){
                btn.textContent      = '🔄 Sync';
                btn.style.color      = '#8b5cf6';
                btn.style.borderColor= '#8b5cf6';
                btn.disabled = false;
            }, 2000);
        } else {
            btn.textContent = '❌';
            btn.disabled    = false;
            alert('Error: ' + data.msg);
        }
    })
    .catch(function(){
        btn.textContent = '❌';
        btn.disabled    = false;
    });
}


// NOW updates hardware_master (via update_hardware.php)
function updateHW(id) {
    var btn  = document.getElementById('upd-' + id);
    var cat  = document.getElementById('cat-' + id).value;
    var rem  = document.getElementById('rem-' + id).value;
    var dop  = document.getElementById('dop-' + id).value;

    btn.textContent = '...';
    btn.disabled    = true;

    var fd = new FormData();
    fd.append('id',               id);
    fd.append('category_manual',  cat);
    fd.append('remarks',          rem);
    fd.append('date_of_purchase', dop);

    fetch('update_hardware.php', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.success){
            btn.textContent = '✓ Saved';
            btn.style.background   = '#dcfce7';
            btn.style.color        = '#15803d';
            btn.style.borderColor  = '#15803d';
            var pill = document.getElementById('catpill-' + id);
            if(pill){
                var shown = cat || pill.textContent.trim();
                pill.textContent = cat || shown;
                pill.className = 'pill ' + (cat==='A'?'p-blue': cat==='B'?'p-teal':'p-amber');
            }
            setTimeout(function(){
                btn.textContent = 'Save';
                btn.style.background  = '#fff';
                btn.style.color       = '#6366f1';
                btn.style.borderColor = '#6366f1';
                btn.disabled = false;
            }, 2000);
        } else {
            btn.textContent = 'Error';
            btn.style.background  = '#fee2e2';
            btn.style.color       = '#dc2626';
            btn.style.borderColor = '#dc2626';
            btn.disabled = false;
            alert('Error: ' + data.message);
        }
    })
    .catch(function(){
        btn.textContent = 'Error';
        btn.disabled = false;
    });
}

// ── VNC Connect ──────────────────────────────────────────
function openVNC(ip, pcName) {
    var serverIP  = '100.2.1.24';
    var proxyPort = 6080;
    var url = 'vnc_setup/vnc.html'
            + '?host='      + serverIP
            + '&port='      + proxyPort
            + '&target_ip=' + encodeURIComponent(ip)
            + '&pc_name='   + encodeURIComponent(pcName || ip);
    window.open(url, '_blank', 'width=1060,height=720,resizable=yes,scrollbars=yes');
}

// ── RDP File Download ────────────────────────────────────
function downloadRDP(ip, pcName) {
    window.location.href = 'vnc_setup/generate_rdp.php'
        + '?ip='   + encodeURIComponent(ip)
        + '&name=' + encodeURIComponent(pcName || ip);
}
</script>

<?php include 'footer.php'; ?>