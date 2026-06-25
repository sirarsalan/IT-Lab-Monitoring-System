<?php
include 'auth.php';
include 'db.php';

$msg = '';
$msgType = '';

if (isset($_POST['save_license'])) {
    $id              = intval(isset($_POST['lic_id']) ? $_POST['lic_id'] : 0);
    $software_name   = "'".$conn->real_escape_string(trim(isset($_POST['software_name'])  ? $_POST['software_name']  : ''))."'";
    $version         = "'".$conn->real_escape_string(trim(isset($_POST['version'])        ? $_POST['version']        : ''))."'";
    $publisher       = "'".$conn->real_escape_string(trim(isset($_POST['publisher'])      ? $_POST['publisher']      : ''))."'";
    $license_type    = "'".$conn->real_escape_string(trim(isset($_POST['license_type'])   ? $_POST['license_type']   : ''))."'";
    $license_key     = "'".$conn->real_escape_string(trim(isset($_POST['license_key'])    ? $_POST['license_key']    : ''))."'";
    $vendor_name     = "'".$conn->real_escape_string(trim(isset($_POST['vendor_name'])    ? $_POST['vendor_name']    : ''))."'";
    $notes           = "'".$conn->real_escape_string(trim(isset($_POST['notes'])          ? $_POST['notes']          : ''))."'";
    $total_licenses  = intval(isset($_POST['total_licenses']) ? $_POST['total_licenses'] : 1);
    $used_licenses   = intval(isset($_POST['used_licenses'])  ? $_POST['used_licenses']  : 0);
    $cost            = floatval(isset($_POST['cost'])         ? $_POST['cost']           : 0);
    $added_by        = "'".$conn->real_escape_string(isset($_SESSION['username']) ? $_SESSION['username'] : 'admin')."'";
    $purchase_date   = (isset($_POST['purchase_date']) && $_POST['purchase_date']) ? "'".$conn->real_escape_string($_POST['purchase_date'])."'" : 'NULL';
    $expiry_date     = (isset($_POST['expiry_date'])   && $_POST['expiry_date'])   ? "'".$conn->real_escape_string($_POST['expiry_date'])."'"   : 'NULL';

    if ($id) {
        $conn->query("UPDATE software_licenses SET
            software_name=$software_name, version=$version, publisher=$publisher,
            license_type=$license_type, license_key=$license_key, vendor_name=$vendor_name,
            notes=$notes, total_licenses=$total_licenses, used_licenses=$used_licenses,
            cost=$cost, purchase_date=$purchase_date, expiry_date=$expiry_date,
            added_by=$added_by
            WHERE id=$id");
        $msg = 'License updated!'; $msgType = 'success';
    } else {
        $conn->query("INSERT INTO software_licenses
            (software_name,version,publisher,license_type,license_key,vendor_name,notes,
             total_licenses,used_licenses,cost,purchase_date,expiry_date,added_by)
            VALUES
            ($software_name,$version,$publisher,$license_type,$license_key,$vendor_name,$notes,
             $total_licenses,$used_licenses,$cost,$purchase_date,$expiry_date,$added_by)");
        $msg = 'License added!'; $msgType = 'success';
    }
}

if (isset($_POST['delete_license'])) {
    $conn->query("DELETE FROM software_licenses WHERE id=".intval($_POST['del_id']));
    $msg = 'License deleted.'; $msgType = 'warning';
}

$licenses = array();
$res = $conn->query("SELECT * FROM software_licenses ORDER BY software_name ASC");
if ($res) { while ($r = $res->fetch_assoc()) { $licenses[] = $r; } }

$today = date('Y-m-d');
$soon  = date('Y-m-d', strtotime('+30 days'));

$total        = count($licenses);
$expired      = 0;
$expiringSoon = 0;
$overUsed     = 0;
foreach ($licenses as $l) {
    if ($l['expiry_date'] && $l['expiry_date'] < $today) $expired++;
    if ($l['expiry_date'] && $l['expiry_date'] >= $today && $l['expiry_date'] <= $soon) $expiringSoon++;
    if ($l['used_licenses'] > $l['total_licenses']) $overUsed++;
}

$_GET['view'] = 'licenses';
include 'header.php';
?>
<style>
.alert-box{padding:10px 16px;border-radius:8px;font-size:12px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#dcfce7;color:#15803d;border:.5px solid #86efac;}
.alert-warning{background:#fef9c3;color:#854d0e;border:.5px solid #fde047;}
.lic-bar-wrap{background:#e2e8f0;border-radius:99px;height:6px;overflow:hidden;margin-top:3px;}
.lic-bar{height:100%;border-radius:99px;}
</style>

<div class="stats-row">
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-certificate"></i></div><div class="stat-label">Total Licenses</div><div class="stat-value" style="color:#6366f1"><?php echo $total; ?></div><div class="stat-sub">software tracked</div></div>
  <div class="stat-card"><div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-certificate-off"></i></div><div class="stat-label">Expired</div><div class="stat-value" style="color:#dc2626"><?php echo $expired; ?></div><div class="stat-sub">needs renewal</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-clock"></i></div><div class="stat-label">Expiring Soon</div><div class="stat-value" style="color:#d97706"><?php echo $expiringSoon; ?></div><div class="stat-sub">within 30 days</div></div>
  <div class="stat-card"><div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-alert-triangle"></i></div><div class="stat-label">Over Used</div><div class="stat-value" style="color:#dc2626"><?php echo $overUsed; ?></div><div class="stat-sub">exceeded license count</div></div>
</div>

<?php if ($msg): ?>
<div class="alert-box alert-<?php echo $msgType; ?>">
  <i class="ti ti-<?php echo $msgType==='success'?'circle-check':'alert-triangle'; ?>"></i>
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-certificate"></i>Software Licenses <span class="panel-badge"><?php echo $total; ?> total</span></div>
    <button onclick="document.getElementById('popupLic').classList.add('show');resetLicForm()" class="filter-btn"><i class="ti ti-plus"></i>&nbsp;Add License</button>
  </div>
  <div style="overflow-x:auto">
  <table class="tbl" style="min-width:1000px">
    <thead><tr>
      <th>Software</th><th>Version</th><th>Publisher</th><th>Type</th>
      <th>Usage</th><th>Total/Used</th><th>Cost</th><th>Expiry</th><th>Vendor</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php if (empty($licenses)): ?>
      <tr><td colspan="10" style="text-align:center;padding:24px;color:#94a3b8">
        <i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No licenses yet.
      </td></tr>
    <?php else: foreach ($licenses as $l):
      $pct      = ($l['total_licenses'] > 0) ? min(100, round($l['used_licenses'] / $l['total_licenses'] * 100)) : 0;
      $over     = ($l['used_licenses'] > $l['total_licenses']);
      $barColor = $over ? '#ef4444' : ($pct >= 80 ? '#f59e0b' : '#10b981');
      $exp      = ($l['expiry_date'] && $l['expiry_date'] < $today);
      $near     = ($l['expiry_date'] && !$exp && $l['expiry_date'] <= $soon);
    ?>
    <tr <?php if($exp) echo 'style="background:#fff5f5"'; elseif($near) echo 'style="background:#fffbeb"'; ?>>
      <td style="font-weight:500"><?php echo htmlspecialchars($l['software_name']); ?></td>
      <td class="mono" style="font-size:11px"><?php echo htmlspecialchars($l['version'] ? $l['version'] : '—'); ?></td>
      <td style="font-size:11px"><?php echo htmlspecialchars($l['publisher'] ? $l['publisher'] : '—'); ?></td>
      <td><span class="pill p-gray" style="font-size:10px"><?php echo $l['license_type']; ?></span></td>
      <td style="min-width:100px">
        <div style="font-size:10px;color:#64748b"><?php echo $pct; ?>% used <?php if($over) echo '<b style="color:#dc2626">⚠ OVER</b>'; ?></div>
        <div class="lic-bar-wrap"><div class="lic-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $barColor; ?>"></div></div>
      </td>
      <td style="font-size:11px;font-weight:500;color:<?php echo $over?'#dc2626':'inherit'; ?>"><?php echo $l['used_licenses']; ?>/<?php echo $l['total_licenses']; ?></td>
      <td class="mono" style="font-size:11px"><?php echo $l['cost']>0 ? 'Rs. '.number_format($l['cost'],0) : '—'; ?></td>
      <td style="font-size:11px;font-weight:<?php echo ($exp||$near)?'600':'400'; ?>;color:<?php echo $exp?'#dc2626':($near?'#d97706':'inherit'); ?>">
        <?php echo $l['expiry_date'] ? date('d M Y', strtotime($l['expiry_date'])) : '—'; ?>
        <?php if($exp) echo ' ⚠ Expired'; elseif($near) echo ' ⚠ Soon'; ?>
      </td>
      <td style="font-size:11px"><?php echo htmlspecialchars($l['vendor_name'] ? $l['vendor_name'] : '—'); ?></td>
      <td style="white-space:nowrap">
        <button class="action-btn" onclick="editLic(<?php echo htmlspecialchars(json_encode($l)); ?>)"><i class="ti ti-edit"></i></button>
        <button class="action-btn" onclick="delLic(<?php echo $l['id']; ?>)"><i class="ti ti-trash"></i></button>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- POPUP -->
<div id="popupLic" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:540px">
    <h3><i class="ti ti-certificate"></i>&nbsp;<span id="licPopupTitle">Add License</span></h3>
    <form method="POST">
      <input type="hidden" name="lic_id" id="lic_id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group"><label>Software Name *</label><input name="software_name" id="l_software_name" required placeholder="e.g. Microsoft Office"></div>
        <div class="form-group"><label>Version</label><input name="version" id="l_version" placeholder="e.g. 2021"></div>
        <div class="form-group"><label>Publisher</label><input name="publisher" id="l_publisher" placeholder="e.g. Microsoft"></div>
        <div class="form-group"><label>License Type</label>
          <select name="license_type" id="l_license_type">
            <option>Per Device</option>
            <option>Per User</option>
            <option>Site License</option>
            <option>OEM</option>
            <option>Open Source</option>
            <option>Subscription</option>
          </select>
        </div>
        <div class="form-group"><label>Total Licenses</label><input type="number" name="total_licenses" id="l_total_licenses" value="1" min="1"></div>
        <div class="form-group"><label>Used Licenses</label><input type="number" name="used_licenses" id="l_used_licenses" value="0" min="0"></div>
        <div class="form-group"><label>Purchase Date</label><input type="date" name="purchase_date" id="l_purchase_date"></div>
        <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" id="l_expiry_date"></div>
        <div class="form-group"><label>Cost (Rs.)</label><input type="number" name="cost" id="l_cost" placeholder="0"></div>
        <div class="form-group"><label>Vendor</label><input name="vendor_name" id="l_vendor_name" placeholder="Vendor name"></div>
      </div>
      <div class="form-group"><label>License Key</label><input name="license_key" id="l_license_key" placeholder="XXXXX-XXXXX-XXXXX" style="font-family:monospace"></div>
      <div class="form-group"><label>Notes</label><textarea name="notes" id="l_notes" rows="2" style="width:100%;resize:vertical;padding:8px;border:.5px solid #e2e8f0;border-radius:8px;font-size:12px"></textarea></div>
      <div class="popup-actions">
        <button type="submit" name="save_license" class="btn-save">Save</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupLic').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>
<form method="POST" id="frmDelLic">
  <input type="hidden" name="del_id" id="del_lic_id">
  <input type="hidden" name="delete_license" value="1">
</form>

<script>
function resetLicForm() {
  document.getElementById('lic_id').value = '';
  document.getElementById('licPopupTitle').textContent = 'Add License';
  var fields = ['software_name','version','publisher','license_key','vendor_name','notes','purchase_date','expiry_date','cost'];
  for (var i = 0; i < fields.length; i++) {
    var el = document.getElementById('l_' + fields[i]);
    if (el) el.value = '';
  }
  document.getElementById('l_total_licenses').value = 1;
  document.getElementById('l_used_licenses').value  = 0;
  document.getElementById('l_license_type').value   = 'Per Device';
}
function editLic(l) {
  document.getElementById('lic_id').value = l.id;
  document.getElementById('licPopupTitle').textContent = 'Edit License';
  var map = ['software_name','version','publisher','license_key','vendor_name','notes','purchase_date','expiry_date'];
  for (var i = 0; i < map.length; i++) {
    var el = document.getElementById('l_' + map[i]);
    if (el) el.value = l[map[i]] || '';
  }
  document.getElementById('l_total_licenses').value = l.total_licenses || 1;
  document.getElementById('l_used_licenses').value  = l.used_licenses  || 0;
  document.getElementById('l_cost').value           = l.cost           || '';
  document.getElementById('l_license_type').value   = l.license_type   || 'Per Device';
  document.getElementById('popupLic').classList.add('show');
}
function delLic(id) {
  if (!confirm('Delete this license?')) return;
  document.getElementById('del_lic_id').value = id;
  document.getElementById('frmDelLic').submit();
}
</script>

<?php include 'footer.php'; ?>