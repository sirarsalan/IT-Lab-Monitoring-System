<?php
// header.php — NCR-CET IT Lab Modern Dashboard
$view = $_GET['view'] ?? 'network';
$allowedViews = ['network','hardware','software','location','rooms','logs','users','changes',
                 'assets','licenses','backup','alerts','active_users','reports','print_quota'];
if(!in_array($view, $allowedViews)) $view = 'network';

$titles = [
  'network'      => 'Network Monitor',
  'hardware'     => 'Hardware Inventory',
  'software'     => 'Software Inventory',
  'location'     => 'PC Location',
  'rooms'        => 'Room Management',
  'logs'         => 'Search Logs',
  'users'        => 'Users',
  'changes'      => 'Inventory Changes',
  'assets'       => 'Asset Management',
  'licenses'     => 'Software Licenses',
  'backup'       => 'Backup Tracker',
  'alerts'       => 'Alerts & Notifications',
  'active_users' => 'Active Users',
  'reports'      => 'Reports & Analytics',
  'print_quota'  => 'Print Quota',
];
$pageTitle = $titles[$view] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NCR-CET IT Lab — <?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
:root{
  --color-background-primary:#ffffff;
  --color-background-secondary:#f8fafc;
  --color-background-tertiary:#f1f5f9;
  --color-text-primary:#0f172a;
  --color-text-secondary:#64748b;
  --color-text-tertiary:#94a3b8;
  --color-border-tertiary:#e2e8f0;
  --border-radius-lg:12px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',sans-serif;background:var(--color-background-tertiary);color:var(--color-text-primary);}
.app{display:flex;height:100vh;min-height:700px;}
/* SIDEBAR */
.sidebar{width:220px;background:#1e1b4b;display:flex;flex-direction:column;flex-shrink:0;position:relative;overflow:hidden;}
.sidebar::before{content:'';position:absolute;top:-60px;right:-40px;width:160px;height:160px;background:rgba(99,102,241,.15);border-radius:50%;pointer-events:none;}
.sidebar::after{content:'';position:absolute;bottom:-40px;left:-30px;width:120px;height:120px;background:rgba(139,92,246,.1);border-radius:50%;pointer-events:none;}
.sb-logo{padding:20px 16px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.08);}
.sb-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0;}
.sb-logo-text{color:#fff;font-size:13px;font-weight:500;line-height:1.3;}
.sb-logo-sub{color:#a5b4fc;font-size:10px;}
.sb-nav{padding:12px 0;flex:1;overflow-y:auto;}
.sb-group{padding:6px 0 2px;}
.sb-group-label{font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:#4338ca;padding:4px 16px 3px;font-weight:500;}
.sb-item{display:flex;align-items:center;gap:9px;padding:8px 16px;cursor:pointer;color:#a5b4fc;font-size:12px;transition:all .15s;margin:1px 8px;border-radius:8px;position:relative;text-decoration:none;}
.sb-item:hover{background:rgba(99,102,241,.15);color:#e0e7ff;}
.sb-item.active{background:linear-gradient(90deg,rgba(99,102,241,.3),rgba(99,102,241,.1));color:#fff;}
.sb-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:60%;background:#6366f1;border-radius:0 2px 2px 0;}
.sb-item i{font-size:16px;flex-shrink:0;}
.sb-badge{margin-left:auto;background:rgba(99,102,241,.3);color:#a5b4fc;font-size:9px;padding:2px 6px;border-radius:99px;}
.sb-footer{padding:12px 16px;border-top:1px solid rgba(255,255,255,.07);}
.sb-user{display:flex;align-items:center;gap:8px;}
.sb-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:500;flex-shrink:0;}
.sb-uname{color:#e0e7ff;font-size:12px;font-weight:500;}
.sb-urole{color:#6366f1;font-size:10px;}
/* MAIN */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.topbar{background:var(--color-background-primary);border-bottom:.5px solid var(--color-border-tertiary);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.tb-left{display:flex;align-items:center;gap:12px;}
.tb-title{font-size:15px;font-weight:500;}
.tb-breadcrumb{font-size:11px;color:var(--color-text-tertiary);display:flex;align-items:center;gap:4px;}
.tb-right{display:flex;align-items:center;gap:10px;}
.tb-clock{font-size:11px;color:var(--color-text-secondary);font-family:monospace;background:var(--color-background-secondary);padding:4px 10px;border-radius:99px;border:.5px solid var(--color-border-tertiary);}
.live-dot{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:500;color:#059669;background:#d1fae5;padding:4px 10px;border-radius:99px;}
.live-dot span{width:6px;height:6px;background:#10b981;border-radius:50%;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.content{flex:1;overflow-y:auto;padding:16px 20px;}
/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;}
.stat-card{background:var(--color-background-primary);border:.5px solid var(--color-border-tertiary);border-radius:var(--border-radius-lg);padding:14px 16px;position:relative;overflow:hidden;}
.stat-accent{position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--border-radius-lg) var(--border-radius-lg) 0 0;}
.stat-accent.blue{background:linear-gradient(90deg,#6366f1,#8b5cf6);}
.stat-accent.green{background:linear-gradient(90deg,#10b981,#34d399);}
.stat-accent.red{background:linear-gradient(90deg,#ef4444,#f87171);}
.stat-accent.amber{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.stat-accent.purple{background:linear-gradient(90deg,#8b5cf6,#a78bfa);}
.stat-accent.teal{background:linear-gradient(90deg,#0d9488,#2dd4bf);}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:10px;}
.stat-icon.blue{background:#ede9fe;color:#6366f1;}
.stat-icon.green{background:#d1fae5;color:#059669;}
.stat-icon.red{background:#fee2e2;color:#dc2626;}
.stat-icon.amber{background:#fef3c7;color:#d97706;}
.stat-icon.purple{background:#ede9fe;color:#7c3aed;}
.stat-icon.teal{background:#ccfbf1;color:#0d9488;}
.stat-label{font-size:11px;color:var(--color-text-secondary);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;}
.stat-value{font-size:24px;font-weight:500;line-height:1;}
.stat-sub{font-size:10px;color:var(--color-text-tertiary);margin-top:4px;}
/* PANEL */
.panel{background:var(--color-background-primary);border:.5px solid var(--color-border-tertiary);border-radius:var(--border-radius-lg);overflow:hidden;margin-bottom:14px;}
.panel-head{padding:12px 16px;border-bottom:.5px solid var(--color-border-tertiary);display:flex;align-items:center;justify-content:space-between;}
.panel-title{font-size:13px;font-weight:500;display:flex;align-items:center;gap:7px;}
.panel-title i{font-size:16px;color:#6366f1;}
.panel-badge{font-size:10px;background:#ede9fe;color:#6366f1;padding:2px 8px;border-radius:99px;font-weight:500;}
/* FILTER */
.filter-row{display:flex;gap:8px;padding:10px 16px;background:var(--color-background-secondary);border-bottom:.5px solid var(--color-border-tertiary);flex-wrap:wrap;}
.filter-row input,.filter-row select{font-size:11px;padding:6px 10px;border:.5px solid var(--color-border-tertiary);border-radius:8px;background:var(--color-background-primary);color:var(--color-text-primary);flex:1;min-width:90px;}
.filter-row input:focus,.filter-row select:focus{outline:none;border-color:#6366f1;}
.filter-btn{font-size:11px;padding:6px 14px;background:#6366f1;color:#fff;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap;font-weight:500;text-decoration:none;}
.filter-btn:hover{background:#4f46e5;}
.filter-btn.red{background:#ef4444;}
.filter-btn.red:hover{background:#dc2626;}
/* TABLE */
.tbl{width:100%;border-collapse:collapse;font-size:11px;}
.tbl th{background:var(--color-background-secondary);padding:8px 12px;text-align:left;font-weight:500;font-size:10px;color:var(--color-text-secondary);border-bottom:.5px solid var(--color-border-tertiary);text-transform:uppercase;letter-spacing:.05em;}
.tbl td{padding:9px 12px;border-bottom:.5px solid var(--color-border-tertiary);vertical-align:middle;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.tbl tr:last-child td{border-bottom:none;}
.tbl tr:hover td{background:var(--color-background-secondary);}
/* PILLS */
.pill{display:inline-flex;align-items:center;gap:4px;font-size:10px;padding:3px 8px;border-radius:99px;font-weight:500;white-space:nowrap;}
.p-conn{background:#d1fae5;color:#065f46;}
.p-disc{background:#fee2e2;color:#991b1b;}
.p-unk{background:#f3f4f6;color:#6b7280;border:.5px solid #e5e7eb;}
.p-blue{background:#ede9fe;color:#4338ca;}
.p-teal{background:#ccfbf1;color:#0f766e;}
.p-amber{background:#fef3c7;color:#92400e;}
.p-gray{background:var(--color-background-secondary);color:var(--color-text-secondary);border:.5px solid var(--color-border-tertiary);}
.p-green{background:#d1fae5;color:#065f46;}
.p-red{background:#fee2e2;color:#991b1b;}
.dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
.dg{background:#10b981;}.dr{background:#ef4444;}.da{background:#f59e0b;}.db{background:#6366f1;}
.mono{font-family:monospace;font-size:10px;}
/* OS BADGES */
.os-badge{font-size:9px;padding:2px 6px;border-radius:4px;font-weight:500;}
.win11{background:#dbeafe;color:#1e40af;}
.win10{background:#d1fae5;color:#065f46;}
.win7{background:#fef3c7;color:#92400e;}
.brand-lenovo{background:#fee2e2;color:#991b1b;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:500;}
.brand-hp{background:#ede9fe;color:#5b21b6;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:500;}
.brand-dell{background:#d1fae5;color:#065f46;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:500;}
/* ACTION BTNS */
.action-btn{width:28px;height:28px;border-radius:8px;border:.5px solid var(--color-border-tertiary);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--color-text-secondary);font-size:14px;transition:background .15s,color .15s,border-color .15s;}
.action-btn:hover{background:#ede9fe;color:#6366f1;border-color:#a5b4fc;}
.action-btn[style*="dc2626"]:hover{background:#fee2e2;color:#dc2626 !important;border-color:#fca5a5;}
/* PAGER */
.pager{display:flex;align-items:center;gap:6px;}
.pg-btn{font-size:11px;padding:4px 10px;border:.5px solid var(--color-border-tertiary);border-radius:8px;cursor:pointer;background:transparent;color:var(--color-text-primary);}
.pg-btn:hover:not([disabled]){background:#ede9fe;color:#6366f1;border-color:#a5b4fc;}
.pg-btn[disabled]{opacity:.35;cursor:default;}
.pg-info{font-size:11px;color:var(--color-text-tertiary);}
/* GRID LAYOUTS */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
/* POPUP */
.popup{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center;}
.popup.show{display:flex;}
.popup-box{background:#fff;width:440px;padding:24px;border-radius:var(--border-radius-lg);box-shadow:0 20px 60px rgba(0,0,0,.15);max-height:90vh;overflow-y:auto;}
.popup-box h3{font-size:14px;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;color:var(--color-text-primary);}
.popup-box h3 i{color:#6366f1;font-size:18px;}
.form-group{margin-bottom:10px;}
.form-group label{display:block;font-size:11px;font-weight:500;color:var(--color-text-secondary);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;}
.form-group input,.form-group select{width:100%;padding:8px 10px;border:.5px solid var(--color-border-tertiary);border-radius:8px;font-size:12px;color:var(--color-text-primary);background:var(--color-background-secondary);}
.form-group input:focus,.form-group select:focus{outline:none;border-color:#6366f1;background:#fff;}
.form-group input[readonly]{background:var(--color-background-tertiary);color:var(--color-text-tertiary);}
.popup-actions{display:flex;gap:8px;margin-top:16px;}
.btn-save{flex:1;padding:9px;background:#6366f1;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:500;}
.btn-save:hover{background:#4f46e5;}
.btn-close{padding:9px 16px;background:transparent;color:var(--color-text-secondary);border:.5px solid var(--color-border-tertiary);border-radius:8px;cursor:pointer;font-size:12px;}
.btn-close:hover{background:var(--color-background-secondary);}
</style>
</head>
<body>
<div class="app">
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-icon"><i class="ti ti-server-2"></i></div>
      <div>
        <div class="sb-logo-text">NCR-CET IT Lab</div>
        <div class="sb-logo-sub">network_db system</div>
      </div>
    </div>
    <div class="sb-nav">
      <div class="sb-group">
        <div class="sb-group-label">Monitor</div>
        <a href="index.php?view=network" class="sb-item <?= $view=='network'?'active':'' ?>">
          <i class="ti ti-topology-star"></i>Network
        </a>
        <a href="sync_pc_status.php" class="sb-item <?= basename($_SERVER['PHP_SELF'])=='sync_pc_status.php'?'active':'' ?>">
          <i class="ti ti-radar"></i>PC Sync Status
        </a>
        <a href="index.php?view=hardware" class="sb-item <?= $view=='hardware'?'active':'' ?>">
          <i class="ti ti-cpu"></i>Hardware
        </a>
        <a href="index.php?view=software" class="sb-item <?= $view=='software'?'active':'' ?>">
          <i class="ti ti-package"></i>Software
        </a>
      </div>
      <div class="sb-group">
        <div class="sb-group-label">Manage</div>
        <a href="index.php?view=location" class="sb-item <?= $view=='location'?'active':'' ?>">
          <i class="ti ti-map-pin"></i>PC Location
        </a>
        <a href="room_management.php" class="sb-item <?= $view=='rooms'?'active':'' ?>">
          <i class="ti ti-building"></i>Room Management
        </a>
      </div>
      <div class="sb-group">
        <div class="sb-group-label">System</div>
        <a href="changes.php" class="sb-item <?= $view=='changes'?'active':'' ?>">
          <i class="ti ti-bell"></i>Changes
          <?php
            if(isset($conn)){
              $uc=$conn->query("SELECT COUNT(*) AS c FROM inventory_changes WHERE acknowledged=0");
              $uc=$uc?($uc->fetch_assoc()['c']??0):0;
              if($uc>0) echo "<span class='sb-badge' style='background:#ef4444;color:#fff'>$uc</span>";
            }
          ?>
        </a>
        <a href="index.php?view=logs" class="sb-item <?= $view=='logs'?'active':'' ?>">
          <i class="ti ti-history"></i>Search Logs
        </a>
        <a href="index.php?view=users" class="sb-item <?= $view=='users'?'active':'' ?>">
          <i class="ti ti-users"></i>Users
        </a>
        <a href="active_users.php" class="sb-item <?= $view=='active_users'?'active':'' ?>">
          <i class="ti ti-activity"></i>Active Users
        </a>
      </div>
      <div class="sb-group">
        <div class="sb-group-label">IT Assets</div>
        <a href="asset_management.php" class="sb-item <?= $view=='assets'?'active':'' ?>">
          <i class="ti ti-box"></i>Assets & Maintenance
        </a>
        <a href="software_licenses.php" class="sb-item <?= $view=='licenses'?'active':'' ?>">
          <i class="ti ti-certificate"></i>Software Licenses
        </a>
        <a href="backup_tracker.php" class="sb-item <?= $view=='backup'?'active':'' ?>">
          <i class="ti ti-database"></i>Backup Tracker
        </a>
        <a href="print_quota.php" class="sb-item <?= $view=='print_quota'?'active':'' ?>">
          <i class="ti ti-printer"></i>Print Quota
        </a>
      </div>
      <div class="sb-group">
        <div class="sb-group-label">Analytics</div>
        <a href="alerts.php" class="sb-item <?= $view=='alerts'?'active':'' ?>">
          <i class="ti ti-bell-ringing"></i>Alerts
          <?php
            if(isset($conn)){
              $unread=$conn->query("SELECT COUNT(*) c FROM system_alerts WHERE is_read=0");
              $unread=$unread?($unread->fetch_assoc()['c']??0):0;
              if($unread>0) echo "<span class='sb-badge' style='background:#ef4444;color:#fff'>$unread</span>";
            }
          ?>
        </a>
        <a href="reports.php" class="sb-item <?= $view=='reports'?'active':'' ?>">
          <i class="ti ti-chart-bar"></i>Reports & Analytics
        </a>
      </div>
    </div>
    <div class="sb-footer">
      <div class="sb-user">
        <div class="sb-avatar"><?= strtoupper(substr($_SESSION['username']??'AD',0,2)) ?></div>
        <div>
          <div class="sb-uname"><?= htmlspecialchars($_SESSION['username']??'admin') ?></div>
          <div class="sb-urole"><?= htmlspecialchars($_SESSION['role']??'Administrator') ?></div>
        </div>
        <a href="logout.php" style="margin-left:auto;color:#6366f1;font-size:16px;" title="Logout">
          <i class="ti ti-logout"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- MAIN -->
  <div class="main">
    <div class="topbar">
      <div class="tb-left">
        <div class="tb-title"><?= htmlspecialchars($pageTitle) ?></div>
        <div class="tb-breadcrumb">
          <i class="ti ti-home" style="font-size:12px"></i>
          <i class="ti ti-chevron-right" style="font-size:11px"></i>
          <span><?= htmlspecialchars($pageTitle) ?></span>
        </div>
      </div>
      <div class="tb-right">
        <div class="tb-clock" id="clock">--:--:--</div>
        <div class="live-dot"><span></span>Live</div>
      </div>
    </div>
    <div class="content">