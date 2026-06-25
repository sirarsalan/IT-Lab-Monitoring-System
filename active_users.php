<?php
include 'auth.php';
include 'db.php';

$fUser = trim($_GET['user'] ?? '');
$fDate = trim($_GET['date'] ?? '');

// Active users right now
$activeNow = [];
$ar = $conn->query("SELECT * FROM active_users WHERE status='active' ORDER BY last_activity DESC");
if ($ar) while ($r = $ar->fetch_assoc()) $activeNow[] = $r;

// History
$sql = "SELECT * FROM user_history WHERE 1=1";
if ($fUser) $sql .= " AND username LIKE '%".$conn->real_escape_string($fUser)."%'";
if ($fDate) $sql .= " AND DATE(created_at)='".$conn->real_escape_string($fDate)."'";
$sql .= " ORDER BY id DESC LIMIT 200";
$history = [];
$hr = $conn->query($sql);
if ($hr) while ($r = $hr->fetch_assoc()) $history[] = $r;

$totalActive  = count($activeNow);
$totalHistory = count($history);
$uniqueUsers  = count(array_unique(array_column($history, 'username')));
$today = date('Y-m-d');
$todayLogins = 0;
foreach($history as $h){
    if(date('Y-m-d', strtotime($h['created_at'] ?? '')) === $today) $todayLogins++;
}
$_GET['view'] = 'active_users';
include 'header.php';
?>
<div class="stats-row">
  <div class="stat-card"><div class="stat-accent green"></div><div class="stat-icon green"><i class="ti ti-users"></i></div><div class="stat-label">Active Now</div><div class="stat-value" style="color:#059669"><?=$totalActive?></div><div class="stat-sub">online users</div></div>
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-login"></i></div><div class="stat-label">Today Logins</div><div class="stat-value" style="color:#6366f1"><?=$todayLogins?></div><div class="stat-sub">login actions today</div></div>
  <div class="stat-card"><div class="stat-accent purple"></div><div class="stat-icon purple"><i class="ti ti-user-check"></i></div><div class="stat-label">Unique Users</div><div class="stat-value" style="color:#7c3aed"><?=$uniqueUsers?></div><div class="stat-sub">in current filter</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-history"></i></div><div class="stat-label">Total Logs</div><div class="stat-value" style="color:#d97706"><?=$totalHistory?></div><div class="stat-sub">activity records</div></div>
</div>

<!-- Active Now -->
<div class="panel" style="margin-bottom:16px">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-activity" style="color:#059669"></i>Currently Active <span class="panel-badge"><?=$totalActive?> online</span></div>
  </div>
  <table class="tbl">
    <thead><tr><th>Username</th><th>IP Address</th><th>Login Time</th><th>Last Activity</th><th>Status</th></tr></thead>
    <tbody>
    <?php if(empty($activeNow)): ?>
      <tr><td colspan="5" style="text-align:center;padding:24px;color:#94a3b8">No active users right now.</td></tr>
    <?php else: foreach($activeNow as $u): ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:600"><?=strtoupper(substr($u['username'],0,2))?></div>
          <span style="font-weight:500"><?=htmlspecialchars($u['username'])?></span>
        </div>
      </td>
      <td class="mono"><?=htmlspecialchars($u['ip_address']??'—')?></td>
      <td class="mono" style="font-size:10px"><?=htmlspecialchars($u['login_time']??'—')?></td>
      <td class="mono" style="font-size:10px"><?=htmlspecialchars($u['last_activity']??'—')?></td>
      <td><span class="pill p-conn"><span class="dot dg"></span>Active</span></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Activity History -->
<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-history"></i>Activity History <span class="panel-badge"><?=$totalHistory?> records</span></div>
  </div>
  <form method="GET" class="filter-row">
    <input name="user" placeholder="Filter username..." value="<?=htmlspecialchars($fUser)?>">
    <input type="date" name="date" value="<?=htmlspecialchars($fDate)?>">
    <button type="submit" class="filter-btn"><i class="ti ti-search"></i> Search</button>
    <a href="active_users.php" class="filter-btn" style="background:#64748b"><i class="ti ti-x"></i> Clear</a>
  </form>
  <table class="tbl">
    <thead><tr><th>Date/Time</th><th>Username</th><th>Action</th><th>IP Address</th></tr></thead>
    <tbody>
    <?php if(empty($history)): ?>
      <tr><td colspan="4" style="text-align:center;padding:24px;color:#94a3b8">No activity logs found.</td></tr>
    <?php else: foreach($history as $h): ?>
    <tr>
      <td class="mono" style="font-size:10px"><?=htmlspecialchars($h['created_at']??'—')?></td>
      <td>
        <div style="display:flex;align-items:center;gap:7px">
          <div style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:9px;font-weight:600"><?=strtoupper(substr($h['username']??'?',0,2))?></div>
          <?=htmlspecialchars($h['username']??'—')?>
        </div>
      </td>
      <td><span class="pill p-blue" style="font-size:10px"><?=htmlspecialchars($h['action']??'—')?></span></td>
      <td class="mono"><?=htmlspecialchars($h['ip_address']??'—')?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>