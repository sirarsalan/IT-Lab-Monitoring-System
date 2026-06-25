<?php
include 'auth.php';
include 'db.php';

$tab = $_GET['tab'] ?? 'assets';
$msg = ''; $msgType = '';

// ══════════════════════════════════════════════════════════
// ASSET MANAGEMENT — ADD / EDIT / DELETE
// ══════════════════════════════════════════════════════════
if (isset($_POST['save_asset'])) {
    $id  = intval($_POST['asset_id'] ?? 0);
    $f   = [];
    foreach (['asset_tag','computer_name','motherboard_serial','ip_address','asset_type','brand','model',
              'vendor_name','vendor_contact','condition_status','location_floor','location_room','notes','added_by'] as $k)
        $f[$k] = "'".$conn->real_escape_string(trim($_POST[$k]??''))."'";
    $f['purchase_price']  = floatval($_POST['purchase_price']  ?? 0);
    $f['warranty_years']  = intval($_POST['warranty_years']    ?? 1);
    $f['purchase_date']   = $_POST['purchase_date']   ? "'".$conn->real_escape_string($_POST['purchase_date'])."'"   : 'NULL';
    $f['warranty_expiry'] = $_POST['warranty_expiry'] ? "'".$conn->real_escape_string($_POST['warranty_expiry'])."'" : 'NULL';
    $f['added_by']        = "'".$conn->real_escape_string($_SESSION['username']??'admin')."'";

    if ($id) {
        $sets = [];
        foreach ($f as $k => $v) $sets[] = "$k=$v";
        $conn->query("UPDATE asset_management SET ".implode(',',$sets)." WHERE id=$id");
        $msg = 'Asset updated!'; $msgType = 'success';
    } else {
        $cols = implode(',', array_keys($f));
        $vals = implode(',', array_values($f));
        $conn->query("INSERT INTO asset_management ($cols) VALUES ($vals)");
        $msg = 'Asset added!'; $msgType = 'success';
    }
}
if (isset($_POST['delete_asset'])) {
    $conn->query("DELETE FROM asset_management WHERE id=".intval($_POST['del_id']));
    $msg = 'Asset deleted.'; $msgType = 'warning';
}

// ══════════════════════════════════════════════════════════
// MAINTENANCE — ADD / UPDATE STATUS
// ══════════════════════════════════════════════════════════
if (isset($_POST['save_maintenance'])) {
    $id = intval($_POST['maint_id'] ?? 0);
    $f  = [];
    foreach (['computer_name','motherboard_serial','maintenance_type','status','performed_by','notes','created_by'] as $k)
        $f[$k] = "'".$conn->real_escape_string(trim($_POST[$k]??''))."'";
    $f['asset_id']      = intval($_POST['asset_id'] ?? 0);
    $f['cost']          = floatval($_POST['cost'] ?? 0);
    $f['scheduled_date']  = $_POST['scheduled_date']  ? "'".$_POST['scheduled_date']."'"  : 'NULL';
    $f['completed_date']  = $_POST['completed_date']  ? "'".$_POST['completed_date']."'"  : 'NULL';
    $f['next_due_date']   = $_POST['next_due_date']   ? "'".$_POST['next_due_date']."'"   : 'NULL';
    $f['created_by']      = "'".$conn->real_escape_string($_SESSION['username']??'admin')."'";

    if ($id) {
        $sets = [];
        foreach ($f as $k=>$v) $sets[] = "$k=$v";
        $conn->query("UPDATE maintenance_schedule SET ".implode(',',$sets)." WHERE id=$id");
        $msg = 'Maintenance updated!'; $msgType = 'success';
    } else {
        $cols = implode(',', array_keys($f));
        $vals = implode(',', array_values($f));
        $conn->query("INSERT INTO maintenance_schedule ($cols) VALUES ($vals)");
        $msg = 'Maintenance scheduled!'; $msgType = 'success';
    }
}

// ══════════════════════════════════════════════════════════
// PERIPHERALS — ADD / EDIT / DELETE
// ══════════════════════════════════════════════════════════
if (isset($_POST['save_peripheral'])) {
    $id = intval($_POST['peri_id'] ?? 0);
    $f  = [];
    foreach (['peripheral_type','brand','model','serial_number','asset_tag','ip_address',
              'location_floor','location_room','location_detail','status','vendor_name','notes'] as $k)
        $f[$k] = "'".$conn->real_escape_string(trim($_POST[$k]??''))."'";
    $f['purchase_date']     = $_POST['purchase_date']     ? "'".$_POST['purchase_date']."'"     : 'NULL';
    $f['warranty_expiry']   = $_POST['warranty_expiry']   ? "'".$_POST['warranty_expiry']."'"   : 'NULL';
    $f['last_service_date'] = $_POST['last_service_date'] ? "'".$_POST['last_service_date']."'" : 'NULL';
    $f['next_service_date'] = $_POST['next_service_date'] ? "'".$_POST['next_service_date']."'" : 'NULL';
    $f['added_by']          = "'".$conn->real_escape_string($_SESSION['username']??'admin')."'";

    if ($id) {
        $sets = [];
        foreach ($f as $k=>$v) $sets[] = "$k=$v";
        $conn->query("UPDATE peripherals SET ".implode(',',$sets)." WHERE id=$id");
        $msg = 'Peripheral updated!'; $msgType = 'success';
    } else {
        $cols = implode(',', array_keys($f));
        $vals = implode(',', array_values($f));
        $conn->query("INSERT INTO peripherals ($cols) VALUES ($vals)");
        $msg = 'Peripheral added!'; $msgType = 'success';
    }
}
if (isset($_POST['delete_peripheral'])) {
    $conn->query("DELETE FROM peripherals WHERE id=".intval($_POST['del_id']));
    $msg = 'Peripheral deleted.'; $msgType = 'warning';
}

// ══════════════════════════════════════════════════════════
// FETCH DATA
// ══════════════════════════════════════════════════════════

// PC list from hardware_status + pc_location (for auto-fill)
$pcList = [];
$pcRes = $conn->query("
    SELECT 
        h.computer_name, h.ip_address, h.motherboard_serial,
        h.brand_name, h.cpu_name, h.ram_total, h.os_name,
        COALESCE(p.floor_no,'') AS floor_no,
        COALESCE(p.room_name,'') AS room_name,
        COALESCE(p.room_no,'') AS room_no,
        COALESCE(p.row_no,'') AS row_no,
        COALESCE(p.table_no,'') AS table_no
    FROM hardware_status h
    LEFT JOIN pc_location p ON p.pc_name = h.computer_name
    WHERE h.id = (SELECT MAX(h2.id) FROM hardware_status h2 WHERE h2.computer_name = h.computer_name)
    ORDER BY h.computer_name ASC
");
if ($pcRes) while ($r = $pcRes->fetch_assoc()) $pcList[] = $r;

$assets = [];
$ar = $conn->query("SELECT * FROM asset_management ORDER BY created_at DESC");
if ($ar) while ($r = $ar->fetch_assoc()) $assets[] = $r;

$maintenances = [];
$mr = $conn->query("SELECT * FROM maintenance_schedule ORDER BY scheduled_date ASC");
if ($mr) while ($r = $mr->fetch_assoc()) $maintenances[] = $r;

$peripherals = [];
$pr = $conn->query("SELECT * FROM peripherals ORDER BY peripheral_type, brand ASC");
if ($pr) while ($r = $pr->fetch_assoc()) $peripherals[] = $r;

// Stats
$totalAssets     = count($assets);
$warrantyExpired = count(array_filter($assets, function($a){ return $a['warranty_expiry'] && $a['warranty_expiry'] < date('Y-m-d'); }));
$warrantyNear    = count(array_filter($assets, function($a){ return $a['warranty_expiry'] && $a['warranty_expiry'] >= date('Y-m-d') && $a['warranty_expiry'] <= date('Y-m-d', strtotime('+30 days')); }));
$maintPending    = count(array_filter($maintenances, function($m){ return $m['status'] === 'Pending'; }));
$maintOverdue    = count(array_filter($maintenances, function($m){ return $m['status'] === 'Pending' && $m['scheduled_date'] < date('Y-m-d'); }));
$totalPeripherals= count($peripherals);
$activePeripherals = count(array_filter($peripherals, function($p){ return $p['status'] === 'Active'; }));

$_GET['view'] = 'assets';
include 'header.php';
?>

<style>
.tab-bar { display:flex; gap:4px; margin-bottom:16px; background:var(--color-background-secondary,#f8fafc); padding:4px; border-radius:10px; width:fit-content; }
.tab-btn { padding:7px 18px; font-size:12px; font-weight:500; border:none; border-radius:7px; cursor:pointer; background:transparent; color:#64748b; transition:all .15s; }
.tab-btn.active { background:#fff; color:#6366f1; box-shadow:0 1px 4px rgba(0,0,0,.1); }
.status-good    { background:#dcfce7; color:#15803d; }
.status-fair    { background:#fef9c3; color:#854d0e; }
.status-poor    { background:#fee2e2; color:#991b1b; }
.status-disposed{ background:#f1f5f9; color:#64748b; }
.warn-expired   { background:#fee2e2 !important; }
.warn-near      { background:#fffbeb !important; }
.alert-box      { padding:10px 16px; border-radius:8px; font-size:12px; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.alert-success  { background:#dcfce7; color:#15803d; border:.5px solid #86efac; }
.alert-warning  { background:#fef9c3; color:#854d0e; border:.5px solid #fde047; }
</style>

<!-- STATS -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-box"></i></div>
    <div class="stat-label">Total Assets</div>
    <div class="stat-value" style="color:#6366f1"><?= $totalAssets ?></div>
    <div class="stat-sub">registered</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-shield-off"></i></div>
    <div class="stat-label">Warranty Expired</div>
    <div class="stat-value" style="color:#dc2626"><?= $warrantyExpired ?></div>
    <div class="stat-sub">needs attention</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-shield"></i></div>
    <div class="stat-label">Warranty Expiring</div>
    <div class="stat-value" style="color:#d97706"><?= $warrantyNear ?></div>
    <div class="stat-sub">within 30 days</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-tool"></i></div>
    <div class="stat-label">Maintenance Overdue</div>
    <div class="stat-value" style="color:#dc2626"><?= $maintOverdue ?></div>
    <div class="stat-sub">past schedule date</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent green"></div><div class="stat-icon green"><i class="ti ti-printer"></i></div>
    <div class="stat-label">Peripherals</div>
    <div class="stat-value" style="color:#059669"><?= $activePeripherals ?>/<?= $totalPeripherals ?></div>
    <div class="stat-sub">active devices</div>
  </div>
</div>

<?php if ($msg): ?>
<div class="alert-box alert-<?= $msgType ?>">
  <i class="ti ti-<?= $msgType==='success'?'circle-check':'alert-triangle' ?>"></i>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- TAB BAR -->
<div class="tab-bar">
  <button class="tab-btn <?= $tab==='assets'?'active':'' ?>" onclick="switchTab('assets')"><i class="ti ti-box"></i> Assets</button>
  <button class="tab-btn <?= $tab==='maintenance'?'active':'' ?>" onclick="switchTab('maintenance')"><i class="ti ti-tool"></i> Maintenance</button>
  <button class="tab-btn <?= $tab==='peripherals'?'active':'' ?>" onclick="switchTab('peripherals')"><i class="ti ti-printer"></i> Peripherals</button>
</div>

<!-- ══════════════════════ TAB: ASSETS ══════════════════════ -->
<div id="tab-assets" class="tab-content" style="display:<?= $tab==='assets'?'block':'none' ?>">
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title"><i class="ti ti-box"></i>Asset Management <span class="panel-badge"><?= $totalAssets ?> assets</span></div>
      <button onclick="document.getElementById('popupAsset').classList.add('show');resetAssetForm()" class="filter-btn">
        <i class="ti ti-plus"></i>&nbsp;Add Asset
      </button>
    </div>
    <div style="overflow-x:auto">
    <table class="tbl" style="min-width:1100px">
      <thead><tr>
        <th>Asset Tag</th><th>PC / Name</th><th>Type</th><th>Brand/Model</th>
        <th>Condition</th><th>Location</th><th>Purchase Date</th><th>Price</th>
        <th>Warranty Expiry</th><th>Vendor</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($assets)): ?>
        <tr><td colspan="11" style="text-align:center;padding:24px;color:#94a3b8"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No assets yet. Add one!</td></tr>
      <?php else: foreach($assets as $a):
        $wExpired = $a['warranty_expiry'] && $a['warranty_expiry'] < date('Y-m-d');
        $wNear    = $a['warranty_expiry'] && !$wExpired && $a['warranty_expiry'] <= date('Y-m-d', strtotime('+30 days'));
      ?>
      <tr class="<?= $wExpired?'warn-expired':($wNear?'warn-near':'') ?>">
        <td class="mono" style="font-size:11px;color:#6366f1"><?= htmlspecialchars($a['asset_tag']??'—') ?></td>
        <td style="font-weight:500"><?= htmlspecialchars($a['computer_name']??'—') ?></td>
        <td><span class="pill p-gray"><?= $a['asset_type'] ?></span></td>
        <td style="font-size:11px"><?= htmlspecialchars($a['brand']??'') ?> <?= htmlspecialchars($a['model']??'') ?></td>
        <td><span class="pill status-<?= strtolower($a['condition_status']) ?>"><?= $a['condition_status'] ?></span></td>
        <td style="font-size:11px"><?= htmlspecialchars($a['location_floor']??'') ?> / <?= htmlspecialchars($a['location_room']??'') ?></td>
        <td class="mono" style="font-size:11px"><?= $a['purchase_date'] ? date('d M Y', strtotime($a['purchase_date'])) : '—' ?></td>
        <td class="mono" style="font-size:11px"><?= $a['purchase_price'] ? 'Rs. '.number_format($a['purchase_price'],0) : '—' ?></td>
        <td style="font-size:11px;font-weight:<?= $wExpired||$wNear?'600':'400' ?>;color:<?= $wExpired?'#dc2626':($wNear?'#d97706':'inherit') ?>">
          <?= $a['warranty_expiry'] ? date('d M Y', strtotime($a['warranty_expiry'])) : '—' ?>
          <?= $wExpired?' ⚠ Expired':($wNear?' ⚠ Soon':'') ?>
        </td>
        <td style="font-size:11px"><?= htmlspecialchars($a['vendor_name']??'—') ?></td>
        <td style="white-space:nowrap">
          <button class="action-btn" onclick="editAsset(<?= htmlspecialchars(json_encode($a)) ?>)"><i class="ti ti-edit"></i></button>
          <button class="action-btn btn-del" onclick="delAsset(<?= $a['id'] ?>,'<?= addslashes($a['computer_name']??'') ?>')"><i class="ti ti-trash"></i></button>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ══════════════════════ TAB: MAINTENANCE ══════════════════════ -->
<div id="tab-maintenance" class="tab-content" style="display:<?= $tab==='maintenance'?'block':'none' ?>">
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title"><i class="ti ti-tool"></i>Maintenance Schedule
        <span class="panel-badge"><?= $maintPending ?> pending</span>
        <?php if($maintOverdue): ?><span class="panel-badge" style="background:#fee2e2;color:#dc2626"><?= $maintOverdue ?> overdue</span><?php endif; ?>
      </div>
      <button onclick="document.getElementById('popupMaint').classList.add('show');resetMaintForm()" class="filter-btn">
        <i class="ti ti-plus"></i>&nbsp;Schedule Maintenance
      </button>
    </div>
    <table class="tbl">
      <thead><tr>
        <th>PC Name</th><th>Type</th><th>Scheduled Date</th><th>Status</th>
        <th>Completed</th><th>Performed By</th><th>Cost</th><th>Next Due</th><th>Notes</th><th>Action</th>
      </tr></thead>
      <tbody>
      <?php if(empty($maintenances)): ?>
        <tr><td colspan="10" style="text-align:center;padding:24px;color:#94a3b8"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No maintenance records yet.</td></tr>
      <?php else: foreach($maintenances as $m):
        $overdue = $m['status']==='Pending' && $m['scheduled_date'] < date('Y-m-d');
        $stColor = ['Pending'=>'p-amber','In Progress'=>'p-blue','Completed'=>'p-conn','Skipped'=>'p-gray'][$m['status']] ?? 'p-gray';
      ?>
      <tr <?= $overdue?'style="background:#fff5f5"':'' ?>>
        <td style="font-weight:500"><?= htmlspecialchars($m['computer_name']??'—') ?></td>
        <td style="font-size:11px"><?= htmlspecialchars($m['maintenance_type']) ?></td>
        <td class="mono" style="font-size:11px;color:<?= $overdue?'#dc2626':'inherit' ?>">
          <?= $m['scheduled_date'] ? date('d M Y', strtotime($m['scheduled_date'])) : '—' ?>
          <?= $overdue?' ⚠':'' ?>
        </td>
        <td><span class="pill <?= $stColor ?>"><?= $m['status'] ?></span></td>
        <td class="mono" style="font-size:11px"><?= $m['completed_date'] ? date('d M Y', strtotime($m['completed_date'])) : '—' ?></td>
        <td style="font-size:11px"><?= htmlspecialchars($m['performed_by']??'—') ?></td>
        <td class="mono" style="font-size:11px"><?= $m['cost'] > 0 ? 'Rs. '.number_format($m['cost'],0) : '—' ?></td>
        <td class="mono" style="font-size:11px"><?= $m['next_due_date'] ? date('d M Y', strtotime($m['next_due_date'])) : '—' ?></td>
        <td style="font-size:11px;color:#64748b;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($m['notes']??'—') ?></td>
        <td><button class="action-btn" onclick="editMaint(<?= htmlspecialchars(json_encode($m)) ?>)"><i class="ti ti-edit"></i></button></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════ TAB: PERIPHERALS ══════════════════════ -->
<div id="tab-peripherals" class="tab-content" style="display:<?= $tab==='peripherals'?'block':'none' ?>">
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title"><i class="ti ti-printer"></i>Peripherals & Devices <span class="panel-badge"><?= $totalPeripherals ?> devices</span></div>
      <button onclick="document.getElementById('popupPeri').classList.add('show');resetPeriForm()" class="filter-btn">
        <i class="ti ti-plus"></i>&nbsp;Add Device
      </button>
    </div>
    <div style="overflow-x:auto">
    <table class="tbl" style="min-width:1000px">
      <thead><tr>
        <th>Type</th><th>Brand/Model</th><th>Serial No</th><th>IP</th>
        <th>Location</th><th>Status</th><th>Last Service</th><th>Next Service</th><th>Warranty</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($peripherals)): ?>
        <tr><td colspan="10" style="text-align:center;padding:24px;color:#94a3b8"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No peripherals yet. Add one!</td></tr>
      <?php else: foreach($peripherals as $p):
        $stColor = ['Active'=>'p-conn','Inactive'=>'p-gray','Under Repair'=>'p-amber','Disposed'=>'p-disc'][$p['status']] ?? 'p-gray';
        $wExp = $p['warranty_expiry'] && $p['warranty_expiry'] < date('Y-m-d');
      ?>
      <tr <?= $wExp?'style="background:#fff5f5"':'' ?>>
        <td><span class="pill p-blue"><?= $p['peripheral_type'] ?></span></td>
        <td style="font-weight:500"><?= htmlspecialchars($p['brand']??'') ?> <?= htmlspecialchars($p['model']??'') ?></td>
        <td class="mono" style="font-size:10px"><?= htmlspecialchars($p['serial_number']??'—') ?></td>
        <td class="mono"><?= htmlspecialchars($p['ip_address']??'—') ?></td>
        <td style="font-size:11px"><?= htmlspecialchars($p['location_floor']??'') ?> / <?= htmlspecialchars($p['location_room']??'') ?></td>
        <td><span class="pill <?= $stColor ?>"><?= $p['status'] ?></span></td>
        <td class="mono" style="font-size:11px"><?= $p['last_service_date'] ? date('d M Y', strtotime($p['last_service_date'])) : '—' ?></td>
        <td class="mono" style="font-size:11px"><?= $p['next_service_date'] ? date('d M Y', strtotime($p['next_service_date'])) : '—' ?></td>
        <td style="font-size:11px;color:<?= $wExp?'#dc2626':'inherit' ?>"><?= $p['warranty_expiry'] ? date('d M Y', strtotime($p['warranty_expiry'])) : '—' ?><?= $wExp?' ⚠':'' ?></td>
        <td style="white-space:nowrap">
          <button class="action-btn" onclick="editPeri(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="ti ti-edit"></i></button>
          <button class="action-btn btn-del" onclick="delPeri(<?= $p['id'] ?>,'<?= addslashes($p['brand']??'') ?>')"><i class="ti ti-trash"></i></button>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ══════ POPUP: ADD/EDIT ASSET ══════ -->
<div id="popupAsset" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:600px;max-height:90vh;overflow-y:auto">
    <h3><i class="ti ti-box"></i>&nbsp;<span id="assetPopupTitle">Add Asset</span></h3>
    <form method="POST">
      <input type="hidden" name="asset_id" id="asset_id">

      <!-- PC AUTO-FILL SECTION -->
      <div style="background:#f0f9ff;border:.5px solid #bae6fd;border-radius:8px;padding:12px;margin-bottom:12px;">
        <div style="font-size:10px;font-weight:600;color:#0369a1;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
          <i class="ti ti-search"></i> Select PC  
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <div class="form-group" style="margin:0">
            <label>Search PC</label>
            <input type="text" id="pc_search_input" placeholder="Select PC Name" oninput="filterPCList()" autocomplete="off">
          </div>
          <div class="form-group" style="margin:0">
            <label>PC Select *</label>
            <select id="pc_select_dropdown" onchange="autoFillFromPC()">
              <option value="">-- PC Select  --</option>
              <?php foreach($pcList as $pc): ?>
              <option value="<?=htmlspecialchars($pc['computer_name'])?>"
                data-ip="<?=htmlspecialchars($pc['ip_address']??'')?>"
                data-serial="<?=htmlspecialchars($pc['motherboard_serial']??'')?>"
                data-brand="<?=htmlspecialchars($pc['brand_name']??'')?>"
                data-cpu="<?=htmlspecialchars($pc['cpu_name']??'')?>"
                data-ram="<?=htmlspecialchars($pc['ram_total']??'')?>"
                data-os="<?=htmlspecialchars($pc['os_name']??'')?>"
                data-floor="<?=htmlspecialchars($pc['floor_no']??'')?>"
                data-room="<?=htmlspecialchars($pc['room_name']??'')?>"
                data-roomno="<?=htmlspecialchars($pc['room_no']??'')?>"
                data-rowno="<?=htmlspecialchars($pc['row_no']??'')?>"
                data-tableno="<?=htmlspecialchars($pc['table_no']??'')?>">
                <?=htmlspecialchars($pc['computer_name'])?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group"><label>Asset Tag</label><input name="asset_tag" id="f_asset_tag" placeholder="e.g. NCR-PC-001"></div>
        <div class="form-group"><label>Asset Type</label>
          <select name="asset_type" id="f_asset_type">
            <?php foreach(['PC','Laptop','Printer','Switch','Projector','UPS','Monitor','Other'] as $t) echo "<option>$t</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>PC / Computer Name</label><input name="computer_name" id="f_computer_name" placeholder="e.g. LAB-PC-01"></div>
        <div class="form-group"><label>IP Address</label><input name="ip_address" id="f_ip_address" placeholder="e.g. 192.168.1.10"></div>
        <div class="form-group"><label>Brand</label><input name="brand" id="f_brand" placeholder="e.g. Dell, HP, Lenovo"></div>
        <div class="form-group"><label>Model</label><input name="model" id="f_model" placeholder="e.g. OptiPlex 3080"></div>
        <div class="form-group"><label>MB Serial</label><input name="motherboard_serial" id="f_motherboard_serial" placeholder="Motherboard serial"></div>
        <div class="form-group"><label>Condition</label>
          <select name="condition_status" id="f_condition_status">
            <?php foreach(['Good','Fair','Poor','Disposed'] as $c) echo "<option>$c</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Purchase Date</label><input type="date" name="purchase_date" id="f_purchase_date"></div>
        <div class="form-group"><label>Purchase Price (Rs.)</label><input type="number" name="purchase_price" id="f_purchase_price" placeholder="0"></div>
        <div class="form-group"><label>Warranty Years</label><input type="number" name="warranty_years" id="f_warranty_years" min="0" max="10" value="1"></div>
        <div class="form-group"><label>Warranty Expiry</label><input type="date" name="warranty_expiry" id="f_warranty_expiry"></div>
        <div class="form-group"><label>Vendor Name</label><input name="vendor_name" id="f_vendor_name" placeholder="e.g. TechZone Karachi"></div>
        <div class="form-group"><label>Vendor Contact</label><input name="vendor_contact" id="f_vendor_contact" placeholder="Phone / Email"></div>
        <div class="form-group"><label>Floor</label>
          <select name="location_floor" id="f_location_floor">
            <option value="">Select Floor</option>
            <?php foreach(['Ground','First','Second','Third'] as $fl) echo "<option>$fl</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Room</label><input name="location_room" id="f_location_room" placeholder="e.g. Vlab"></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea name="notes" id="f_notes" rows="2" style="width:100%;resize:vertical"></textarea></div>
      <div class="popup-actions">
        <button type="submit" name="save_asset" class="btn-save">Save Asset</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupAsset').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>
<form method="POST" id="frmDelAsset"><input type="hidden" name="del_id" id="del_asset_id"><input type="hidden" name="delete_asset" value="1"></form>

<!-- ══════ POPUP: MAINTENANCE ══════ -->
<div id="popupMaint" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:520px">
    <h3><i class="ti ti-tool"></i>&nbsp;<span id="maintPopupTitle">Schedule Maintenance</span></h3>
    <form method="POST">
      <input type="hidden" name="maint_id" id="maint_id">
      <input type="hidden" name="asset_id" id="m_asset_id" value="0">

      <!-- PC SELECT -->
      <div style="background:#f0fdf4;border:.5px solid #bbf7d0;border-radius:8px;padding:10px;margin-bottom:12px;">
        <div style="font-size:10px;font-weight:600;color:#15803d;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
          <i class="ti ti-search"></i> PC Select
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <div class="form-group" style="margin:0">
            <label>Search PC</label>
            <input type="text" id="mpc_search" placeholder="PC name ..." oninput="filterMaintPC()" autocomplete="off">
          </div>
          <div class="form-group" style="margin:0">
            <label>PC Select</label>
            <select id="mpc_select" onchange="autoFillMaintPC()">
              <option value="">-- PC Select --</option>
              <?php foreach($pcList as $pc): ?>
              <option value="<?=htmlspecialchars($pc['computer_name'])?>"
                data-serial="<?=htmlspecialchars($pc['motherboard_serial']??'')?>">
                <?=htmlspecialchars($pc['computer_name'])?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group"><label>PC Name *</label><input name="computer_name" id="m_computer_name" required placeholder="e.g. LAB-PC-01"></div>
        <div class="form-group"><label>Maintenance Type *</label>
          <select name="maintenance_type" id="m_maintenance_type" required>
            <?php foreach(['Cleaning','RAM Upgrade','HDD Replacement','OS Reinstall','Virus Removal','Hardware Repair','Full Service','Other'] as $t) echo "<option>$t</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Scheduled Date *</label><input type="date" name="scheduled_date" id="m_scheduled_date" required></div>
        <div class="form-group"><label>Status</label>
          <select name="status" id="m_status">
            <?php foreach(['Pending','In Progress','Completed','Skipped'] as $s) echo "<option>$s</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Completed Date</label><input type="date" name="completed_date" id="m_completed_date"></div>
        <div class="form-group"><label>Next Due Date</label><input type="date" name="next_due_date" id="m_next_due_date"></div>
        <div class="form-group"><label>Performed By</label><input name="performed_by" id="m_performed_by" placeholder="Technician name"></div>
        <div class="form-group"><label>Cost (Rs.)</label><input type="number" name="cost" id="m_cost" placeholder="0"></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea name="notes" id="m_notes" rows="2" style="width:100%;resize:vertical"></textarea></div>
      <div class="popup-actions">
        <button type="submit" name="save_maintenance" class="btn-save">Save</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupMaint').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════ POPUP: PERIPHERAL ══════ -->
<div id="popupPeri" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:560px;max-height:90vh;overflow-y:auto">
    <h3><i class="ti ti-printer"></i>&nbsp;<span id="periPopupTitle">Add Peripheral</span></h3>
    <form method="POST">
      <input type="hidden" name="peri_id" id="peri_id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group"><label>Type *</label>
          <select name="peripheral_type" id="p_peripheral_type" required>
            <?php foreach(['Printer','Scanner','Projector','Switch','Router','UPS','Monitor','Keyboard','Mouse','Webcam','Other'] as $t) echo "<option>$t</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Status</label>
          <select name="status" id="p_status">
            <?php foreach(['Active','Inactive','Under Repair','Disposed'] as $s) echo "<option>$s</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Brand</label><input name="brand" id="p_brand" placeholder="e.g. HP, Canon"></div>
        <div class="form-group"><label>Model</label><input name="model" id="p_model" placeholder="e.g. LaserJet Pro"></div>
        <div class="form-group"><label>Serial Number</label><input name="serial_number" id="p_serial_number"></div>
        <div class="form-group"><label>IP Address</label><input name="ip_address" id="p_ip_address" placeholder="If network device"></div>
        <div class="form-group"><label>Floor</label>
          <select name="location_floor" id="p_location_floor">
            <option value="">Select Floor</option>
            <?php foreach(['Ground','First','Second','Third'] as $fl) echo "<option>$fl</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Room</label><input name="location_room" id="p_location_room" placeholder="e.g. Vlab"></div>
        <div class="form-group"><label>Purchase Date</label><input type="date" name="purchase_date" id="p_purchase_date"></div>
        <div class="form-group"><label>Warranty Expiry</label><input type="date" name="warranty_expiry" id="p_warranty_expiry"></div>
        <div class="form-group"><label>Last Service Date</label><input type="date" name="last_service_date" id="p_last_service_date"></div>
        <div class="form-group"><label>Next Service Date</label><input type="date" name="next_service_date" id="p_next_service_date"></div>
        <div class="form-group"><label>Vendor Name</label><input name="vendor_name" id="p_vendor_name"></div>
        <div class="form-group"><label>Asset Tag</label><input name="asset_tag" id="p_asset_tag"></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea name="notes" id="p_notes" rows="2" style="width:100%;resize:vertical"></textarea></div>
      <div class="popup-actions">
        <button type="submit" name="save_peripheral" class="btn-save">Save Device</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupPeri').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>
<form method="POST" id="frmDelPeri"><input type="hidden" name="del_id" id="del_peri_id"><input type="hidden" name="delete_peripheral" value="1"></form>

<script>
function switchTab(t) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display='none');
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-'+t).style.display = 'block';
  event.currentTarget.classList.add('active');
}

// ── Asset form helpers ──────────────────────────────────
// ── PC Auto-fill ────────────────────────────────────────
function filterPCList() {
  var q   = document.getElementById('pc_search_input').value.toLowerCase();
  var sel = document.getElementById('pc_select_dropdown');
  for (var i = 0; i < sel.options.length; i++) {
    var opt = sel.options[i];
    opt.style.display = (i === 0 || opt.text.toLowerCase().includes(q)) ? '' : 'none';
  }
}

function autoFillFromPC() {
  var sel = document.getElementById('pc_select_dropdown');
  var opt = sel.options[sel.selectedIndex];
  if (!opt.value) return;

  // Basic info
  document.getElementById('f_computer_name').value      = opt.value;
  document.getElementById('f_ip_address').value         = opt.dataset.ip        || '';
  document.getElementById('f_motherboard_serial').value = opt.dataset.serial     || '';
  document.getElementById('f_brand').value              = opt.dataset.brand      || '';

  // Location
  document.getElementById('f_location_floor').value    = opt.dataset.floor      || '';
  document.getElementById('f_location_room').value     = opt.dataset.room       || '';

  // Visual feedback — flash fields green
  var fields = ['f_computer_name','f_ip_address','f_motherboard_serial','f_brand','f_location_room'];
  fields.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
      el.style.borderColor = '#10b981';
      el.style.background  = '#f0fdf4';
      setTimeout(function(){ el.style.borderColor=''; el.style.background=''; }, 1500);
    }
  });
}

function resetAssetForm() {
  document.getElementById('asset_id').value = '';
  document.getElementById('assetPopupTitle').textContent = 'Add Asset';
  document.getElementById('pc_search_input').value    = '';
  document.getElementById('pc_select_dropdown').value = '';
  ['asset_tag','computer_name','ip_address','brand','model','motherboard_serial',
   'purchase_date','purchase_price','warranty_expiry','vendor_name','vendor_contact',
   'location_room','notes'].forEach(f => { var el=document.getElementById('f_'+f); if(el) el.value=''; });
  document.getElementById('f_warranty_years').value = 1;
  document.getElementById('f_asset_type').value      = 'PC';
  document.getElementById('f_condition_status').value= 'Good';
  document.getElementById('f_location_floor').value  = '';
}
function editAsset(a) {
  document.getElementById('asset_id').value = a.id;
  document.getElementById('assetPopupTitle').textContent = 'Edit Asset';
  ['asset_tag','computer_name','ip_address','brand','model','motherboard_serial',
   'purchase_date','purchase_price','warranty_expiry','vendor_name','vendor_contact',
   'location_room','notes'].forEach(f => {
    var el = document.getElementById('f_'+f); if(el) el.value = a[f]||'';
  });
  document.getElementById('f_warranty_years').value    = a.warranty_years||1;
  document.getElementById('f_asset_type').value        = a.asset_type||'PC';
  document.getElementById('f_condition_status').value  = a.condition_status||'Good';
  document.getElementById('f_location_floor').value    = a.location_floor||'';
  document.getElementById('popupAsset').classList.add('show');
}
function delAsset(id, name) {
  if(!confirm('Delete asset "'+name+'"?')) return;
  document.getElementById('del_asset_id').value = id;
  document.getElementById('frmDelAsset').submit();
}

// ── Maintenance form helpers ────────────────────────────
function filterMaintPC() {
  var q   = document.getElementById('mpc_search').value.toLowerCase();
  var sel = document.getElementById('mpc_select');
  for (var i = 0; i < sel.options.length; i++) {
    var opt = sel.options[i];
    opt.style.display = (i === 0 || opt.text.toLowerCase().includes(q)) ? '' : 'none';
  }
}
function autoFillMaintPC() {
  var sel = document.getElementById('mpc_select');
  var opt = sel.options[sel.selectedIndex];
  if (!opt.value) return;
  document.getElementById('m_computer_name').value       = opt.value;
  document.getElementById('m_motherboard_serial').value  = opt.dataset.serial || '';
  var fields = ['m_computer_name','m_motherboard_serial'];
  fields.forEach(function(id){
    var el = document.getElementById(id);
    if(el){ el.style.borderColor='#10b981'; el.style.background='#f0fdf4';
      setTimeout(function(){ el.style.borderColor=''; el.style.background=''; },1500); }
  });
}
function resetMaintForm() {
  document.getElementById('maint_id').value = '';
  document.getElementById('maintPopupTitle').textContent = 'Schedule Maintenance';
  document.getElementById('mpc_search').value  = '';
  document.getElementById('mpc_select').value  = '';
  ['computer_name','motherboard_serial','scheduled_date','completed_date','next_due_date','performed_by','cost','notes']
    .forEach(f => { var el=document.getElementById('m_'+f); if(el) el.value=''; });
  document.getElementById('m_maintenance_type').value = 'Full Service';
  document.getElementById('m_status').value = 'Pending';
}
function editMaint(m) {
  document.getElementById('maint_id').value = m.id;
  document.getElementById('maintPopupTitle').textContent = 'Edit Maintenance';
  ['computer_name','scheduled_date','completed_date','next_due_date','performed_by','cost','notes']
    .forEach(f => { var el=document.getElementById('m_'+f); if(el) el.value=m[f]||''; });
  document.getElementById('m_maintenance_type').value = m.maintenance_type||'Full Service';
  document.getElementById('m_status').value           = m.status||'Pending';
  document.getElementById('tab-maintenance').style.display = 'block';
  document.getElementById('popupMaint').classList.add('show');
}

// ── Peripheral form helpers ─────────────────────────────
function resetPeriForm() {
  document.getElementById('peri_id').value = '';
  document.getElementById('periPopupTitle').textContent = 'Add Peripheral';
  ['brand','model','serial_number','ip_address','location_room','purchase_date',
   'warranty_expiry','last_service_date','next_service_date','vendor_name','asset_tag','notes']
    .forEach(f => { var el=document.getElementById('p_'+f); if(el) el.value=''; });
  document.getElementById('p_peripheral_type').value = 'Printer';
  document.getElementById('p_status').value          = 'Active';
  document.getElementById('p_location_floor').value  = '';
}
function editPeri(p) {
  document.getElementById('peri_id').value = p.id;
  document.getElementById('periPopupTitle').textContent = 'Edit Peripheral';
  ['brand','model','serial_number','ip_address','location_room','purchase_date',
   'warranty_expiry','last_service_date','next_service_date','vendor_name','asset_tag','notes']
    .forEach(f => { var el=document.getElementById('p_'+f); if(el) el.value=p[f]||''; });
  document.getElementById('p_peripheral_type').value = p.peripheral_type||'Printer';
  document.getElementById('p_status').value          = p.status||'Active';
  document.getElementById('p_location_floor').value  = p.location_floor||'';
  document.getElementById('popupPeri').classList.add('show');
}
function delPeri(id, name) {
  if(!confirm('Delete "'+name+'"?')) return;
  document.getElementById('del_peri_id').value = id;
  document.getElementById('frmDelPeri').submit();
}
</script>

<?php include 'footer.php'; ?>