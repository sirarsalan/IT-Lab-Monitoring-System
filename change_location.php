<?php
include 'auth.php';
include 'db.php';

// ─── SAVE CHANGE LOCATION ────────────────────────────────────────────────────
if(isset($_POST['save_change'])){
    $s=$conn->prepare("INSERT INTO location_changes (pc_name,ip_address,motherboard_serial,from_floor,from_room_no,from_room_name,from_row_no,from_table_no,to_floor,to_room_no,to_room_name,to_row_no,to_table_no,reason,order_by,change_date,changed_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
    $s->bind_param("sssssssssssssssss",
        $_POST['pc_name'],$_POST['ip_address'],$_POST['motherboard_serial'],
        $_POST['from_floor'],$_POST['from_room_no'],$_POST['from_room_name'],$_POST['from_row_no'],$_POST['from_table_no'],
        $_POST['to_floor'],$_POST['to_room_no'],$_POST['to_room_name'],$_POST['to_row_no'],$_POST['to_table_no'],
        $_POST['reason'],$_POST['order_by'],$_POST['change_date'],
        $_SESSION['username']??'admin'
    );
    $s->execute();

    // Also update pc_location table with new location
    if(!empty($_POST['motherboard_serial'])){
        $u=$conn->prepare("UPDATE pc_location SET floor_no=?,room_no=?,room_name=?,row_no=?,table_no=?,last_updated=NOW() WHERE motherboard_serial=?");
        $u->bind_param("ssssss",$_POST['to_floor'],$_POST['to_room_no'],$_POST['to_room_name'],$_POST['to_row_no'],$_POST['to_table_no'],$_POST['motherboard_serial']);
        $u->execute();
    }
    echo "<script>alert('Location Changed & Saved!');window.location='change_location.php';</script>"; exit;
}

// ─── FETCH PC list ────────────────────────────────────────────────────────────
$pcList = [];
$res=$conn->query("SELECT pl.id,pl.pc_name,pl.ip_address,pl.motherboard_serial,pl.floor_no,pl.room_no,pl.room_name,pl.row_no,pl.table_no FROM pc_location pl WHERE pl.pc_name IS NOT NULL AND pl.pc_name!='' ORDER BY pl.pc_name ASC");
while($r=$res->fetch_assoc()) $pcList[]=$r;

// ─── FETCH change history ─────────────────────────────────────────────────────
$history=[];
$hres=$conn->query("SELECT * FROM location_changes ORDER BY id DESC LIMIT 50");
if($hres) while($r=$hres->fetch_assoc()) $history[]=$r;

$_GET['view']='location';
include 'header.php';
?>

<div class="stats-row">
  <div class="stat-card">
    <div class="stat-accent teal"></div>
    <div class="stat-icon teal"><i class="ti ti-arrows-exchange"></i></div>
    <div class="stat-label">Change Location</div>
    <div class="stat-value" style="color:#0d9488"><?=count($history)?></div>
    <div class="stat-sub">total transfers recorded</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent blue"></div>
    <div class="stat-icon blue"><i class="ti ti-map-pin"></i></div>
    <div class="stat-label">Located PCs</div>
    <div class="stat-value" style="color:#6366f1"><?=count($pcList)?></div>
    <div class="stat-sub">available to move</div>
  </div>
</div>

<!-- Change Location Form -->
<div class="panel" style="margin-bottom:16px;">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-arrows-exchange" style="color:#0d9488"></i>Change PC Location</div>
    <a href="pclocation.php" class="filter-btn" style="background:#64748b"><i class="ti ti-arrow-left" style="font-size:13px"></i>Back</a>
  </div>
  <div style="padding:20px;">
    <form method="POST">
      <!-- PC Selection -->
      <div style="background:#f8fafc;border:.5px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:16px;">
        <div style="font-size:11px;font-weight:600;color:#6366f1;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;"><i class="ti ti-cpu"></i> Select PC to Move</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div class="form-group">
            <label>Search PC</label>
            <input type="text" id="cl_pc_search" placeholder="Type PC name..." onkeyup="clFilterPC()">
          </div>
          <div class="form-group">
            <label>PC Name *</label>
            <select id="cl_pc_select" onchange="clFillFrom()" required>
              <option value="">-- Select PC --</option>
              <?php foreach($pcList as $p): ?>
              <option value="<?=htmlspecialchars($p['pc_name'])?>"
                data-ip="<?=htmlspecialchars($p['ip_address']??'')?>"
                data-serial="<?=htmlspecialchars($p['motherboard_serial']??'')?>"
                data-floor="<?=htmlspecialchars($p['floor_no']??'')?>"
                data-roomno="<?=htmlspecialchars($p['room_no']??'')?>"
                data-roomname="<?=htmlspecialchars($p['room_name']??'')?>"
                data-rowno="<?=htmlspecialchars($p['row_no']??'')?>"
                data-tableno="<?=htmlspecialchars($p['table_no']??'')?>">
                <?=htmlspecialchars($p['pc_name'])?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
          <div class="form-group">
            <label>PC Name (hidden)</label>
            <input type="text" name="pc_name" id="cl_pc_name" readonly style="background:#f1f5f9">
          </div>
          <div class="form-group">
            <label>IP Address</label>
            <input type="text" name="ip_address" id="cl_ip" readonly style="background:#f1f5f9">
          </div>
          <div class="form-group">
            <label>MB Serial</label>
            <input type="text" name="motherboard_serial" id="cl_serial" readonly style="background:#f1f5f9">
          </div>
        </div>
      </div>

      <!-- From Location -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div style="background:#fff7ed;border:.5px solid #fed7aa;border-radius:10px;padding:16px;">
          <div style="font-size:11px;font-weight:600;color:#ea580c;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;"><i class="ti ti-map-pin-off"></i> From Location (Current)</div>
          <div class="form-group"><label>Floor</label><input type="text" name="from_floor" id="cl_from_floor" readonly style="background:#fff"></div>
          <div class="form-group"><label>Room No</label><input type="text" name="from_room_no" id="cl_from_room_no" readonly style="background:#fff"></div>
          <div class="form-group"><label>Room Name</label><input type="text" name="from_room_name" id="cl_from_room_name" readonly style="background:#fff"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div class="form-group"><label>Row</label><input type="text" name="from_row_no" id="cl_from_row" readonly style="background:#fff"></div>
            <div class="form-group"><label>Table</label><input type="text" name="from_table_no" id="cl_from_table" readonly style="background:#fff"></div>
          </div>
        </div>

        <!-- To Location -->
        <div style="background:#f0fdf4;border:.5px solid #bbf7d0;border-radius:10px;padding:16px;">
          <div style="font-size:11px;font-weight:600;color:#16a34a;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;"><i class="ti ti-map-pin"></i> To Location (New)</div>
          <div class="form-group"><label>Floor *</label>
            <select name="to_floor" required>
              <option value="">Select Floor</option>
              <?php foreach(['Ground','First','Second','Third'] as $f) echo "<option>$f</option>"; ?>
            </select>
          </div>
          <div class="form-group"><label>Room No *</label><input type="text" name="to_room_no" placeholder="e.g. 5" required></div>
          <div class="form-group"><label>Room Name *</label>
            <select name="to_room_name" required>
              <option value="">Select Room</option>
              <?php foreach(['Vlab','Admission Office','RND','Server Room','Office','Class Room','Account','Reception','Director Room','Store','Co-ordinator','Physic-Lab','Chemistry-Lab','Bio-Lab','Guard Room'] as $rm) echo "<option>$rm</option>"; ?>
            </select>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div class="form-group"><label>Row *</label><input type="number" name="to_row_no" placeholder="Row" required></div>
            <div class="form-group"><label>Table *</label><input type="number" name="to_table_no" placeholder="Table" required></div>
          </div>
        </div>
      </div>

      <!-- Reason / Order / Date -->
      <div style="background:#f8fafc;border:.5px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:16px;">
        <div style="font-size:11px;font-weight:600;color:#6366f1;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;"><i class="ti ti-file-description"></i> Transfer Details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
          <div class="form-group">
            <label>Reason *</label>
            <select name="reason" required>
              <option value="">Select Reason</option>
              <option>Lab Reorganization</option>
              <option>Hardware Repair</option>
              <option>Room Upgrade</option>
              <option>Student Requirement</option>
              <option>Director Order</option>
              <option>Principal Order</option>
              <option>Maintenance</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Order By *</label>
            <input type="text" name="order_by" placeholder="e.g. Director / Principal" required>
          </div>
          <div class="form-group">
            <label>Change Date *</label>
            <input type="date" name="change_date" value="<?=date('Y-m-d')?>" required>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="pclocation.php" class="btn-close" style="padding:9px 20px;text-decoration:none">Cancel</a>
        <button type="submit" name="save_change" class="btn-save" style="max-width:200px"><i class="ti ti-check"></i>&nbsp;Save Change</button>
      </div>
    </form>
  </div>
</div>

<!-- History Table -->
<?php if(!empty($history)): ?>
<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-history"></i>Transfer History <span class="panel-badge"><?=count($history)?> records</span></div>
    <a href="export_changes.php" class="filter-btn red" style="font-size:11px"><i class="ti ti-download" style="font-size:13px"></i>Export</a>
  </div>
  <table class="tbl">
    <thead><tr>
      <th>Date</th><th>PC Name</th><th>IP</th>
      <th>From</th><th>To</th>
      <th>Reason</th><th>Order By</th><th>Changed By</th>
    </tr></thead>
    <tbody>
    <?php foreach($history as $h): ?>
    <tr>
      <td class="mono" style="font-size:10px"><?=htmlspecialchars($h['change_date']??$h['created_at']??'—')?></td>
      <td style="font-weight:500"><?=htmlspecialchars($h['pc_name']??'—')?></td>
      <td class="mono"><?=htmlspecialchars($h['ip_address']??'—')?></td>
      <td style="font-size:10px;color:#d97706">
        <?=htmlspecialchars($h['from_floor']??'')?> /
        <?=htmlspecialchars($h['from_room_name']??'')?><br>
        <span style="color:#94a3b8">Row <?=htmlspecialchars($h['from_row_no']??'')?> Table <?=htmlspecialchars($h['from_table_no']??'')?></span>
      </td>
      <td style="font-size:10px;color:#059669">
        <?=htmlspecialchars($h['to_floor']??'')?> /
        <?=htmlspecialchars($h['to_room_name']??'')?><br>
        <span style="color:#94a3b8">Row <?=htmlspecialchars($h['to_row_no']??'')?> Table <?=htmlspecialchars($h['to_table_no']??'')?></span>
      </td>
      <td><?=htmlspecialchars($h['reason']??'—')?></td>
      <td style="font-weight:500"><?=htmlspecialchars($h['order_by']??'—')?></td>
      <td><?=htmlspecialchars($h['changed_by']??'—')?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
function clFilterPC(){
  var q   = document.getElementById('cl_pc_search').value.toLowerCase();
  var sel = document.getElementById('cl_pc_select');
  for(var i=0;i<sel.options.length;i++){
    sel.options[i].style.display=(i===0||sel.options[i].text.toLowerCase().includes(q))?'':'none';
  }
}
function clFillFrom(){
  var sel = document.getElementById('cl_pc_select');
  var opt = sel.options[sel.selectedIndex];
  document.getElementById('cl_pc_name').value      = opt.value            ||'';
  document.getElementById('cl_ip').value           = opt.dataset.ip       ||'';
  document.getElementById('cl_serial').value       = opt.dataset.serial   ||'';
  document.getElementById('cl_from_floor').value   = opt.dataset.floor    ||'';
  document.getElementById('cl_from_room_no').value = opt.dataset.roomno   ||'';
  document.getElementById('cl_from_room_name').value=opt.dataset.roomname ||'';
  document.getElementById('cl_from_row').value     = opt.dataset.rowno    ||'';
  document.getElementById('cl_from_table').value   = opt.dataset.tableno  ||'';
}
</script>

<?php include 'footer.php'; ?>