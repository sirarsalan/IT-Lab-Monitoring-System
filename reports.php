<?php
// Prevent function redeclaration conflicts with other included files
if (!function_exists('rpt_getCat')) {
    function rpt_getCat($cpu, $ram) {
        $ram = strtoupper(trim($ram));
        $ramGB = (strpos($ram,'MB')!==false) ? floatval($ram)/1024 : floatval($ram);
        $cpu = strtoupper($cpu);
        if (!empty($cpu) && (strpos($cpu,'I3')!==false||strpos($cpu,'I5')!==false||strpos($cpu,'I7')!==false||strpos($cpu,'I9')!==false) && $ramGB>=8) return 'A';
        if (!empty($cpu) && (strpos($cpu,'I3')!==false||strpos($cpu,'I5')!==false||strpos($cpu,'I7')!==false||strpos($cpu,'I9')!==false)) return 'B';
        if (!empty($cpu) && (strpos($cpu,'DUO')!==false||strpos($cpu,'PENTIUM')!==false) && $ramGB>=4) return 'B';
        return 'C';
    }
}

// Safe query helper — returns 0 if table missing or query fails
function safeCount($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row ? (int)($row['t'] ?? 0) : 0;
}

include 'auth.php';
include 'db.php';

// ── CSV Export with selected columns ─────────────────────
if (isset($_POST['do_export'])) {
    $rType   = $conn->real_escape_string($_POST['report_type'] ?? '');
    $cols    = isset($_POST['cols']) ? $_POST['cols'] : array();
    $filename = 'report_'.$rType.'_'.date('Y-m-d').'.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('NCR-CET IT Lab — '.ucfirst($rType).' Report', date('d M Y')));
    fputcsv($out, array());

    $allCols = array(
        'hardware' => array(
            'computer_name'           => 'PC Name',
            'ip_address'              => 'IP Address',
            'brand_name'              => 'Brand',
            'os_name'                 => 'OS',
            'os_version'              => 'OS Version',
            'ram_total'               => 'RAM Total',
            'ram_free'                => 'RAM Free',
            'cpu_name'                => 'CPU Name',
            'cpu_manufacturer'        => 'CPU Manufacturer',
            'cpu_speed'               => 'CPU Speed',
            'cpu_cores'               => 'CPU Cores',
            'cpu_serial_number'       => 'CPU Serial',
            'motherboard_serial'      => 'MB Serial',
            'motherboard_manufacturer'=> 'MB Manufacturer',
            'hard_disk'               => 'Hard Disk',
            'architecture'            => 'Architecture',
            'logged_user'             => 'Logged User',
            'category_manual'         => 'Category',
            'remarks'                 => 'Remarks',
            'date_of_purchase'        => 'Date of Purchase',
        ),
        'assets' => array(
            'asset_tag'        => 'Asset Tag',
            'computer_name'    => 'PC Name',
            'asset_type'       => 'Asset Type',
            'brand'            => 'Brand',
            'model'            => 'Model',
            'condition_status' => 'Condition',
            'location_floor'   => 'Floor',
            'location_room'    => 'Room',
            'purchase_date'    => 'Purchase Date',
            'purchase_price'   => 'Purchase Price',
            'warranty_years'   => 'Warranty Years',
            'warranty_expiry'  => 'Warranty Expiry',
            'vendor_name'      => 'Vendor Name',
            'vendor_contact'   => 'Vendor Contact',
            'motherboard_serial'=> 'MB Serial',
            'ip_address'       => 'IP Address',
            'notes'            => 'Notes',
        ),
        'maintenance' => array(
            'computer_name'      => 'PC Name',
            'motherboard_serial' => 'MB Serial',
            'maintenance_type'   => 'Maintenance Type',
            'scheduled_date'     => 'Scheduled Date',
            'completed_date'     => 'Completed Date',
            'status'             => 'Status',
            'performed_by'       => 'Performed By',
            'cost'               => 'Cost',
            'next_due_date'      => 'Next Due Date',
            'notes'              => 'Notes',
            'created_by'         => 'Created By',
        ),
        'peripherals' => array(
            'peripheral_type'   => 'Type',
            'brand'             => 'Brand',
            'model'             => 'Model',
            'serial_number'     => 'Serial Number',
            'ip_address'        => 'IP Address',
            'location_floor'    => 'Floor',
            'location_room'     => 'Room',
            'location_detail'   => 'Location Detail',
            'status'            => 'Status',
            'purchase_date'     => 'Purchase Date',
            'warranty_expiry'   => 'Warranty Expiry',
            'last_service_date' => 'Last Service',
            'next_service_date' => 'Next Service',
            'vendor_name'       => 'Vendor',
            'asset_tag'         => 'Asset Tag',
            'notes'             => 'Notes',
        ),
        'licenses' => array(
            'software_name'  => 'Software Name',
            'version'        => 'Version',
            'publisher'      => 'Publisher',
            'license_type'   => 'License Type',
            'total_licenses' => 'Total Licenses',
            'used_licenses'  => 'Used Licenses',
            'purchase_date'  => 'Purchase Date',
            'expiry_date'    => 'Expiry Date',
            'cost'           => 'Cost',
            'vendor_name'    => 'Vendor',
            'license_key'    => 'License Key',
            'notes'          => 'Notes',
        ),
        'print' => array(
            'username'    => 'Username',
            'quota_limit' => 'Quota Limit',
            'pages_used'  => 'Pages Used',
            'last_reset'  => 'Last Reset',
            'notes'       => 'Notes',
        ),
        'backup' => array(
            'computer_name'   => 'PC Name',
            'ip_address'      => 'IP Address',
            'backup_type'     => 'Backup Type',
            'backup_location' => 'Backup Location',
            'backup_size'     => 'Size',
            'status'          => 'Status',
            'performed_by'    => 'Performed By',
            'backup_date'     => 'Backup Date',
            'notes'           => 'Notes',
        ),
        'network' => array(
            'ip'            => 'IP Address',
            'computer_name' => 'PC Name',
            'status'        => 'Status',
            'date_time'     => 'Date/Time',
        ),
        'location' => array(
            'pc_name'            => 'PC Name',
            'ip_address'         => 'IP Address',
            'motherboard_serial' => 'MB Serial',
            'floor_no'           => 'Floor',
            'room_no'            => 'Room No',
            'room_name'          => 'Room Name',
            'row_no'             => 'Row',
            'table_no'           => 'Table',
            'last_updated'       => 'Last Updated',
        ),
        'users' => array(
            'username'   => 'Username',
            'role'       => 'Role',
            'category'   => 'Category',
            'created_at' => 'Created At',
        ),
    );

    $queries = array(
        'hardware'    => "SELECT * FROM hardware_master ORDER BY computer_name",
        'assets'      => "SELECT * FROM asset_management ORDER BY computer_name",
        'maintenance' => "SELECT * FROM maintenance_schedule ORDER BY scheduled_date DESC",
        'peripherals' => "SELECT * FROM peripherals ORDER BY peripheral_type, brand",
        'licenses'    => "SELECT * FROM software_licenses ORDER BY software_name",
        'print'       => "SELECT * FROM print_quotas ORDER BY username",
        'backup'      => "SELECT * FROM backup_logs ORDER BY backup_date DESC",
        'network'     => "SELECT n1.* FROM lab_network_status n1 WHERE n1.id=(SELECT MAX(n2.id) FROM lab_network_status n2 WHERE n2.ip=n1.ip) ORDER BY ip",
        'location'    => "SELECT * FROM pc_location ORDER BY floor_no, room_name",
        'users'       => "SELECT * FROM users ORDER BY username",
    );

    if (!isset($allCols[$rType]) || !isset($queries[$rType])) { exit; }

    $selectedCols = array();
    foreach ($cols as $c) {
        $c = $conn->real_escape_string($c);
        if (isset($allCols[$rType][$c])) {
            $selectedCols[$c] = $allCols[$rType][$c];
        }
    }
    if (empty($selectedCols)) { $selectedCols = $allCols[$rType]; }

    fputcsv($out, array_values($selectedCols));

    $res = $conn->query($queries[$rType]);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $row = array();
            foreach (array_keys($selectedCols) as $col) {
                if ($rType === 'hardware' && $col === 'category_manual') {
                    $val = isset($r['category_manual']) && $r['category_manual'] ? $r['category_manual'] : 'Auto';
                } elseif ($rType === 'print' && $col === 'pages_used') {
                    $remaining = max(0, $r['quota_limit'] - $r['pages_used']);
                    $val = $r['pages_used'].' (Remaining: '.$remaining.')';
                } else {
                    $val = isset($r[$col]) ? $r[$col] : '';
                }
                $row[] = $val;
            }
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

// ── Stats — safeCount() use karo taake missing tables pe crash na ho ──
$hw_total    = safeCount($conn, "SELECT COUNT(DISTINCT computer_name) t FROM hardware_master");
$asset_total = safeCount($conn, "SELECT COUNT(*) t FROM asset_management");
$maint_due   = safeCount($conn, "SELECT COUNT(*) t FROM maintenance_schedule WHERE status='Pending' AND scheduled_date < CURDATE()");
$lic_exp     = safeCount($conn, "SELECT COUNT(*) t FROM software_licenses WHERE expiry_date <= DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND expiry_date >= CURDATE()");
$backup_fail = safeCount($conn, "SELECT COUNT(*) t FROM backup_logs WHERE status='Failed' AND DATE(backup_date)=CURDATE()");
$warranty_exp= safeCount($conn, "SELECT COUNT(*) t FROM asset_management WHERE warranty_expiry < CURDATE()");
$alerts_unr  = safeCount($conn, "SELECT COUNT(*) t FROM system_alerts WHERE is_read=0");

// print_total — SUM query alag handle karni hai
$pt = $conn->query("SELECT SUM(pages_used) t FROM print_quotas");
$print_total = ($pt && ($row=$pt->fetch_assoc())) ? (int)($row['t'] ?? 0) : 0;

// Category counts — hardware_master se
$catA = 0; $catB = 0; $catC = 0;
$cats = $conn->query("SELECT cpu_name, ram_total, category_manual FROM hardware_master");
if ($cats) {
    while ($r = $cats->fetch_assoc()) {
        $rpt_eff = !empty($r['category_manual']) ? $r['category_manual'] : rpt_getCat($r['cpu_name'], $r['ram_total']);
        if ($rpt_eff==='A') $catA++;
        elseif ($rpt_eff==='B') $catB++;
        else $catC++;
    }
}

$_GET['view'] = 'reports';
include 'header.php';
?>
<style>
.rep-section { background:#fff; border:.5px solid #e2e8f0; border-radius:12px; margin-bottom:14px; overflow:hidden; }
.rep-head    { padding:14px 18px; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border-bottom:.5px solid #e2e8f0; }
.rep-title   { font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px; color:#0f172a; }
.rep-title i { font-size:17px; }
.rep-desc    { font-size:11px; color:#64748b; padding:10px 18px 14px; }
.export-btn  { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border:none; border-radius:8px; font-size:12px; font-weight:500; cursor:pointer; color:#fff; }
.sum-grid    { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px; padding:16px 18px; }
.sum-card    { background:#f8fafc; border:.5px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center; }
.sum-val     { font-size:22px; font-weight:600; margin:4px 0; }
.sum-lbl     { font-size:10px; color:#64748b; }
.col-popup          { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:1000; justify-content:center; align-items:center; }
.col-popup.show     { display:flex; }
.col-popup-box      { background:#fff; width:520px; max-height:85vh; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,.2); display:flex; flex-direction:column; overflow:hidden; }
.col-popup-head     { padding:16px 20px; border-bottom:.5px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; }
.col-popup-head h3  { font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; margin:0; }
.col-popup-body     { padding:16px 20px; overflow-y:auto; flex:1; }
.col-popup-foot     { padding:12px 20px; border-top:.5px solid #e2e8f0; display:flex; gap:8px; justify-content:flex-end; background:#f8fafc; }
.col-grid           { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
.col-item           { display:flex; align-items:center; gap:8px; padding:7px 10px; border:.5px solid #e2e8f0; border-radius:8px; cursor:pointer; transition:all .12s; font-size:12px; }
.col-item:hover     { background:#ede9fe; border-color:#a5b4fc; }
.col-item.checked   { background:#ede9fe; border-color:#6366f1; color:#4338ca; font-weight:500; }
.col-item input     { display:none; }
.col-check          { width:16px; height:16px; border:1.5px solid #cbd5e1; border-radius:4px; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .12s; }
.col-item.checked .col-check { background:#6366f1; border-color:#6366f1; }
.col-item.checked .col-check::after { content:'✓'; color:#fff; font-size:10px; font-weight:700; }
.sel-all-btn { font-size:11px; color:#6366f1; background:none; border:none; cursor:pointer; padding:0; margin-bottom:10px; }
</style>

<!-- SUMMARY -->
<div class="panel" style="margin-bottom:16px">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-chart-bar"></i>System Summary — <?php echo date('d M Y'); ?></div>
  </div>
  <div class="sum-grid">
    <div class="sum-card"><div class="sum-val" style="color:#6366f1"><?php echo $hw_total; ?></div><div class="sum-lbl">Total PCs</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#059669"><?php echo $catA; ?></div><div class="sum-lbl">Category A</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#0d9488"><?php echo $catB; ?></div><div class="sum-lbl">Category B</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#d97706"><?php echo $catC; ?></div><div class="sum-lbl">Category C</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#dc2626"><?php echo $maint_due; ?></div><div class="sum-lbl">Maintenance Overdue</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#d97706"><?php echo $lic_exp; ?></div><div class="sum-lbl">Licenses Expiring</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#6366f1"><?php echo $print_total; ?></div><div class="sum-lbl">Pages Printed</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#dc2626"><?php echo $backup_fail; ?></div><div class="sum-lbl">Failed Backups Today</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#dc2626"><?php echo $warranty_exp; ?></div><div class="sum-lbl">Warranty Expired</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#d97706"><?php echo $alerts_unr; ?></div><div class="sum-lbl">Unread Alerts</div></div>
  </div>
  <?php if ($hw_total > 0): ?>
  <div style="padding:0 18px 16px">
    <div style="font-size:11px;color:#64748b;margin-bottom:6px">PC Category Distribution</div>
    <div style="display:flex;height:12px;border-radius:99px;overflow:hidden;gap:1px">
      <?php if($catA): ?><div style="flex:<?php echo $catA; ?>;background:#6366f1" title="A: <?php echo $catA; ?>"></div><?php endif; ?>
      <?php if($catB): ?><div style="flex:<?php echo $catB; ?>;background:#10b981" title="B: <?php echo $catB; ?>"></div><?php endif; ?>
      <?php if($catC): ?><div style="flex:<?php echo $catC; ?>;background:#f59e0b" title="C: <?php echo $catC; ?>"></div><?php endif; ?>
    </div>
    <div style="display:flex;gap:16px;margin-top:6px;font-size:11px">
      <span><span style="display:inline-block;width:10px;height:10px;background:#6366f1;border-radius:2px;margin-right:4px"></span>A: <?php echo $catA; ?></span>
      <span><span style="display:inline-block;width:10px;height:10px;background:#10b981;border-radius:2px;margin-right:4px"></span>B: <?php echo $catB; ?></span>
      <span><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:2px;margin-right:4px"></span>C: <?php echo $catC; ?></span>
    </div>
  </div>
  <?php endif; ?>
</div>

<div style="font-size:13px;font-weight:600;color:#0f172a;margin-bottom:10px;padding-left:2px">
  <i class="ti ti-download" style="color:#6366f1"></i> Export Reports — Column Select ke baad CSV download
</div>

<?php
$sections = array(
    'hardware'    => array('icon'=>'ti-cpu',         'color'=>'#6366f1', 'title'=>'Hardware Inventory',    'desc'=>'All PCs hardware info — CPU, RAM, OS, disk, category, remarks'),
    'assets'      => array('icon'=>'ti-box',         'color'=>'#0d9488', 'title'=>'Asset Management',      'desc'=>'All PCs warranty, vendor, purchase info aur condition'),
    'maintenance' => array('icon'=>'ti-tool',        'color'=>'#d97706', 'title'=>'Maintenance Schedule',  'desc'=>'All PCs maintenance records — pending, completed, overdue'),
    'peripherals' => array('icon'=>'ti-printer',     'color'=>'#7c3aed', 'title'=>'Peripherals & Devices', 'desc'=>'Printers, projectors, switches aur baaki devices'),
    'licenses'    => array('icon'=>'ti-certificate', 'color'=>'#0891b2', 'title'=>'Software Licenses',     'desc'=>'Software licenses — total, used, expiry, cost'),
    'print'       => array('icon'=>'ti-printer',     'color'=>'#059669', 'title'=>'Print Quota',           'desc'=>'Users  print limit and usage'),
    'backup'      => array('icon'=>'ti-database',    'color'=>'#dc2626', 'title'=>'Backup Logs',           'desc'=>'All PCs backup records — success, failed, partial'),
    'network'     => array('icon'=>'ti-wifi',        'color'=>'#6366f1', 'title'=>'Network Status',        'desc'=>'All PCs latest network connection status'),
    'location'    => array('icon'=>'ti-map-pin',     'color'=>'#d97706', 'title'=>'PC Location',           'desc'=>'All PCs PC floor, room, row, table location'),
    'users'       => array('icon'=>'ti-users',       'color'=>'#7c3aed', 'title'=>'Users',                 'desc'=>'System users, roles aur categories'),
);

$allColsJS = array(
    'hardware'    => array('computer_name'=>'PC Name','ip_address'=>'IP Address','brand_name'=>'Brand','os_name'=>'OS','os_version'=>'OS Version','ram_total'=>'RAM Total','ram_free'=>'RAM Free','cpu_name'=>'CPU Name','cpu_manufacturer'=>'CPU Manufacturer','cpu_speed'=>'CPU Speed','cpu_cores'=>'CPU Cores','cpu_serial_number'=>'CPU Serial','motherboard_serial'=>'MB Serial','motherboard_manufacturer'=>'MB Manufacturer','hard_disk'=>'Hard Disk','architecture'=>'Architecture','logged_user'=>'Logged User','category_manual'=>'Category','remarks'=>'Remarks','date_of_purchase'=>'Date of Purchase'),
    'assets'      => array('asset_tag'=>'Asset Tag','computer_name'=>'PC Name','asset_type'=>'Asset Type','brand'=>'Brand','model'=>'Model','condition_status'=>'Condition','location_floor'=>'Floor','location_room'=>'Room','purchase_date'=>'Purchase Date','purchase_price'=>'Purchase Price','warranty_years'=>'Warranty Years','warranty_expiry'=>'Warranty Expiry','vendor_name'=>'Vendor Name','vendor_contact'=>'Vendor Contact','motherboard_serial'=>'MB Serial','ip_address'=>'IP Address','notes'=>'Notes'),
    'maintenance' => array('computer_name'=>'PC Name','motherboard_serial'=>'MB Serial','maintenance_type'=>'Maintenance Type','scheduled_date'=>'Scheduled Date','completed_date'=>'Completed Date','status'=>'Status','performed_by'=>'Performed By','cost'=>'Cost','next_due_date'=>'Next Due Date','notes'=>'Notes','created_by'=>'Created By'),
    'peripherals' => array('peripheral_type'=>'Type','brand'=>'Brand','model'=>'Model','serial_number'=>'Serial Number','ip_address'=>'IP Address','location_floor'=>'Floor','location_room'=>'Room','location_detail'=>'Location Detail','status'=>'Status','purchase_date'=>'Purchase Date','warranty_expiry'=>'Warranty Expiry','last_service_date'=>'Last Service','next_service_date'=>'Next Service','vendor_name'=>'Vendor','asset_tag'=>'Asset Tag','notes'=>'Notes'),
    'licenses'    => array('software_name'=>'Software Name','version'=>'Version','publisher'=>'Publisher','license_type'=>'License Type','total_licenses'=>'Total Licenses','used_licenses'=>'Used Licenses','purchase_date'=>'Purchase Date','expiry_date'=>'Expiry Date','cost'=>'Cost','vendor_name'=>'Vendor','license_key'=>'License Key','notes'=>'Notes'),
    'print'       => array('username'=>'Username','quota_limit'=>'Quota Limit','pages_used'=>'Pages Used','last_reset'=>'Last Reset','notes'=>'Notes'),
    'backup'      => array('computer_name'=>'PC Name','ip_address'=>'IP Address','backup_type'=>'Backup Type','backup_location'=>'Backup Location','backup_size'=>'Size','status'=>'Status','performed_by'=>'Performed By','backup_date'=>'Backup Date','notes'=>'Notes'),
    'network'     => array('ip'=>'IP Address','computer_name'=>'PC Name','status'=>'Status','date_time'=>'Date/Time'),
    'location'    => array('pc_name'=>'PC Name','ip_address'=>'IP Address','motherboard_serial'=>'MB Serial','floor_no'=>'Floor','room_no'=>'Room No','room_name'=>'Room Name','row_no'=>'Row','table_no'=>'Table','last_updated'=>'Last Updated'),
    'users'       => array('username'=>'Username','role'=>'Role','category'=>'Category','created_at'=>'Created At'),
);
?>

<?php foreach ($sections as $key => $sec): ?>
<div class="rep-section">
  <div class="rep-head">
    <div class="rep-title">
      <i class="ti <?php echo $sec['icon']; ?>" style="color:<?php echo $sec['color']; ?>"></i>
      <?php echo $sec['title']; ?>
      <span style="font-size:10px;background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:99px;font-weight:400">
        <?php echo count($allColsJS[$key]); ?> columns available
      </span>
    </div>
    <button class="export-btn" style="background:<?php echo $sec['color']; ?>"
      onclick="openColSelector('<?php echo $key; ?>', '<?php echo addslashes($sec['title']); ?>')">
      <i class="ti ti-table-options"></i> Columns Select &amp; Export
    </button>
  </div>
  <div class="rep-desc"><?php echo $sec['desc']; ?></div>
</div>
<?php endforeach; ?>

<!-- COLUMN SELECTOR POPUP -->
<div id="colPopup" class="col-popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="col-popup-box">
    <div class="col-popup-head">
      <h3><i class="ti ti-table-options" style="color:#6366f1"></i><span id="colPopupTitle">Select Columns</span></h3>
      <button onclick="document.getElementById('colPopup').classList.remove('show')"
        style="background:none;border:none;cursor:pointer;font-size:20px;color:#94a3b8">×</button>
    </div>
    <div class="col-popup-body">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-size:11px;color:#64748b">Jo columns chahiye un per tick lagao:</div>
        <div>
          <button class="sel-all-btn" onclick="selectAllCols(true)">✓ Sab Select</button>
          &nbsp;|&nbsp;
          <button class="sel-all-btn" onclick="selectAllCols(false)">✗ Sab Hata</button>
        </div>
      </div>
      <div class="col-grid" id="colGrid"></div>
    </div>
    <div class="col-popup-foot">
      <span id="colCount" style="font-size:11px;color:#64748b;margin-right:auto;align-self:center">0 columns selected</span>
      <button class="btn-close" onclick="document.getElementById('colPopup').classList.remove('show')">Cancel</button>
      <button class="btn-save" onclick="doExport()" style="max-width:160px">
        <i class="ti ti-download"></i>&nbsp;Export CSV
      </button>
    </div>
  </div>
</div>

<!-- Hidden export form -->
<form method="POST" id="exportForm">
  <input type="hidden" name="do_export" value="1">
  <input type="hidden" name="report_type" id="exportType">
  <div id="exportColInputs"></div>
</form>

<script>
var allCols   = <?php echo json_encode($allColsJS); ?>;
var curReport = '';

function openColSelector(rType, title) {
  curReport = rType;
  document.getElementById('colPopupTitle').textContent = title + ' — Columns Select Karo';
  var grid = document.getElementById('colGrid');
  grid.innerHTML = '';
  var cols = allCols[rType];
  for (var key in cols) {
    var item = document.createElement('div');
    item.className   = 'col-item checked';
    item.dataset.col = key;
    item.innerHTML   = '<div class="col-check"></div><span>' + cols[key] + '</span>';
    item.onclick = function() { this.classList.toggle('checked'); updateCount(); };
    grid.appendChild(item);
  }
  updateCount();
  document.getElementById('colPopup').classList.add('show');
}

function updateCount() {
  var checked = document.querySelectorAll('#colGrid .col-item.checked').length;
  document.getElementById('colCount').textContent = checked + ' columns selected';
}

function selectAllCols(val) {
  var items = document.querySelectorAll('#colGrid .col-item');
  for (var i = 0; i < items.length; i++) {
    if (val) items[i].classList.add('checked');
    else     items[i].classList.remove('checked');
  }
  updateCount();
}

function doExport() {
  var checked = document.querySelectorAll('#colGrid .col-item.checked');
  if (checked.length === 0) { alert('Kam az kam ek column select karo!'); return; }
  document.getElementById('exportType').value = curReport;
  var container = document.getElementById('exportColInputs');
  container.innerHTML = '';
  for (var i = 0; i < checked.length; i++) {
    var inp   = document.createElement('input');
    inp.type  = 'hidden';
    inp.name  = 'cols[]';
    inp.value = checked[i].dataset.col;
    container.appendChild(inp);
  }
  document.getElementById('colPopup').classList.remove('show');
  document.getElementById('exportForm').submit();
}
</script>

<?php include 'footer.php'; ?>