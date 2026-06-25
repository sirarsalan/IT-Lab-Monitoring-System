<?php
include 'auth.php';
include 'db.php';

// ── Auto-generate alerts ──────────────────────────────────────────────────────
// 1. Warranty expiring in 30 days
$warr = $conn->query("SELECT id,computer_name,warranty_expiry FROM asset_management WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
if ($warr) while ($r = $warr->fetch_assoc()) {
    $title = $conn->real_escape_string("Warranty expiring: {$r['computer_name']}");
    $msg   = $conn->real_escape_string("Warranty expires on {$r['warranty_expiry']}");
    $conn->query("INSERT IGNORE INTO system_alerts (alert_type,reference_id,reference_table,title,message,severity)
        SELECT 'Warranty Expiry',{$r['id']},'asset_management','$title','$msg','Warning'
        WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE reference_id={$r['id']} AND reference_table='asset_management' AND alert_type='Warranty Expiry' AND DATE(created_at)=CURDATE())");
}
// 2. Maintenance overdue
$maint = $conn->query("SELECT id,computer_name,scheduled_date FROM maintenance_schedule WHERE status='Pending' AND scheduled_date < CURDATE()");
if ($maint) while ($r = $maint->fetch_assoc()) {
    $title = $conn->real_escape_string("Maintenance overdue: {$r['computer_name']}");
    $msg   = $conn->real_escape_string("Was scheduled on {$r['scheduled_date']}");
    $conn->query("INSERT IGNORE INTO system_alerts (alert_type,reference_id,reference_table,title,message,severity)
        SELECT 'Maintenance Due',{$r['id']},'maintenance_schedule','$title','$msg','Critical'
        WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE reference_id={$r['id']} AND reference_table='maintenance_schedule' AND DATE(created_at)=CURDATE())");
}
// 3. License expiring
$lic = $conn->query("SELECT id,software_name,expiry_date FROM software_licenses WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
if ($lic) while ($r = $lic->fetch_assoc()) {
    $title = $conn->real_escape_string("License expiring: {$r['software_name']}");
    $msg   = $conn->real_escape_string("Expires on {$r['expiry_date']}");
    $conn->query("INSERT IGNORE INTO system_alerts (alert_type,reference_id,reference_table,title,message,severity)
        SELECT 'License Expiry',{$r['id']},'software_licenses','$title','$msg','Warning'
        WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE reference_id={$r['id']} AND reference_table='software_licenses' AND DATE(created_at)=CURDATE())");
}
// 4. Print quota exceeded
$pq = $conn->query("SELECT user_id,username,pages_used,quota_limit FROM print_quotas WHERE pages_used >= quota_limit");
if ($pq) while ($r = $pq->fetch_assoc()) {
    $title = $conn->real_escape_string("Print quota exceeded: {$r['username']}");
    $msg   = $conn->real_escape_string("{$r['pages_used']} / {$r['quota_limit']} pages used");
    $conn->query("INSERT IGNORE INTO system_alerts (alert_type,reference_id,reference_table,title,message,severity)
        SELECT 'Quota Exceeded',{$r['user_id']},'print_quotas','$title','$msg','Warning'
        WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE reference_id={$r['user_id']} AND reference_table='print_quotas' AND DATE(created_at)=CURDATE())");
}

// ── Mark read ─────────────────────────────────────────────────────────────────
if (isset($_POST['mark_read'])) {
    $conn->query("UPDATE system_alerts SET is_read=1 WHERE id=".intval($_POST['alert_id']));
}
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE system_alerts SET is_read=1");
}
if (isset($_POST['delete_alert'])) {
    $conn->query("DELETE FROM system_alerts WHERE id=".intval($_POST['alert_id']));
}

$fType = trim($_GET['type'] ?? '');
$fRead = $_GET['read'] ?? '';
$sql = "SELECT * FROM system_alerts WHERE 1=1";
if ($fType) $sql .= " AND alert_type='".$conn->real_escape_string($fType)."'";
if ($fRead === '0') $sql .= " AND is_read=0";
if ($fRead === '1') $sql .= " AND is_read=1";
$sql .= " ORDER BY created_at DESC";

$alerts = [];
$res = $conn->query($sql);
if ($res) while ($r = $res->fetch_assoc()) $alerts[] = $r;

$total    = count($alerts);
$unread   = count(array_filter($alerts, function($a){ return !$a['is_read']; }));
$critical = count(array_filter($alerts, function($a){ return $a['severity']==='Critical' && !$a['is_read']; }));
$warnings = count(array_filter($alerts, function($a){ return $a['severity']==='Warning' && !$a['is_read']; }));

$_GET['view'] = 'alerts';
include 'header.php';
?>
<style>
.alert-box{padding:10px 16px;border-radius:8px;font-size:12px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.sev-Critical{border-left:4px solid #dc2626;background:#fff5f5;}
.sev-Warning {border-left:4px solid #d97706;background:#fffbeb;}
.sev-Info    {border-left:4px solid #6366f1;background:#f5f3ff;}
.read-row    {opacity:.5;}
</style>

<div class="stats-row">
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-bell"></i></div><div class="stat-label">Total Alerts</div><div class="stat-value" style="color:#6366f1"><?=$total?></div><div class="stat-sub">all alerts</div></div>
  <div class="stat-card"><div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-bell-ringing"></i></div><div class="stat-label">Unread</div><div class="stat-value" style="color:#dc2626"><?=$unread?></div><div class="stat-sub">needs attention</div></div>
  <div class="stat-card"><div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-alert-octagon"></i></div><div class="stat-label">Critical</div><div class="stat-value" style="color:#dc2626"><?=$critical?></div><div class="stat-sub">urgent!</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-alert-triangle"></i></div><div class="stat-label">Warnings</div><div class="stat-value" style="color:#d97706"><?=$warnings?></div><div class="stat-sub">unread warnings</div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-bell"></i>Alerts & Notifications <span class="panel-badge"><?=$unread?> unread</span></div>
    <div style="display:flex;gap:8px">
      <form method="POST" style="display:inline"><input type="hidden" name="mark_all_read" value="1"><button type="submit" class="filter-btn" style="background:#64748b"><i class="ti ti-checks"></i>&nbsp;Mark All Read</button></form>
    </div>
  </div>

  <form method="GET" class="filter-row">
    <select name="type">
      <option value="">All Types</option>
      <?php foreach(['Warranty Expiry','Maintenance Due','Backup Overdue','License Expiry','Quota Exceeded','Other'] as $t): ?>
      <option <?=$fType===$t?'selected':''?>><?=$t?></option>
      <?php endforeach; ?>
    </select>
    <select name="read">
      <option value="">All</option>
      <option value="0" <?=$fRead==='0'?'selected':''?>>Unread</option>
      <option value="1" <?=$fRead==='1'?'selected':''?>>Read</option>
    </select>
    <button type="submit" class="filter-btn"><i class="ti ti-search"></i> Filter</button>
    <a href="alerts.php" class="filter-btn" style="background:#64748b"><i class="ti ti-x"></i> Clear</a>
  </form>

  <div style="padding:8px 16px">
  <?php if(empty($alerts)): ?>
    <div style="text-align:center;padding:40px;color:#94a3b8"><i class="ti ti-bell-off" style="font-size:32px;display:block;margin-bottom:8px"></i>No alerts found. System looks healthy! ✓</div>
  <?php else: foreach($alerts as $a): ?>
  <div class="sev-<?=$a['severity']?> <?=$a['is_read']?'read-row':''?>" style="padding:12px 14px;border-radius:8px;margin-bottom:8px;display:flex;align-items:center;gap:12px">
    <div style="flex-shrink:0;font-size:20px">
      <?= $a['severity']==='Critical'?'🔴':($a['severity']==='Warning'?'🟡':'🔵') ?>
    </div>
    <div style="flex:1">
      <div style="font-weight:600;font-size:13px;color:#0f172a"><?=htmlspecialchars($a['title'])?></div>
      <div style="font-size:11px;color:#64748b;margin-top:2px"><?=htmlspecialchars($a['message'])?></div>
      <div style="font-size:10px;color:#94a3b8;margin-top:3px">
        <span class="pill p-gray" style="font-size:10px"><?=$a['alert_type']?></span>
        &nbsp;<?=date('d M Y H:i', strtotime($a['created_at']))?>
      </div>
    </div>
    <div style="display:flex;gap:6px;flex-shrink:0">
      <?php if(!$a['is_read']): ?>
      <form method="POST"><input type="hidden" name="alert_id" value="<?=$a['id']?>"><input type="hidden" name="mark_read" value="1">
        <button type="submit" class="action-btn" title="Mark Read"><i class="ti ti-check"></i></button>
      </form>
      <?php endif; ?>
      <form method="POST"><input type="hidden" name="alert_id" value="<?=$a['id']?>"><input type="hidden" name="delete_alert" value="1">
        <button type="submit" class="action-btn btn-del" title="Delete" onclick="return confirm('Delete this alert?')"><i class="ti ti-trash"></i></button>
      </form>
    </div>
  </div>
  <?php endforeach; endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>