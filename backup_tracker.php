<?php
include 'auth.php';
include 'db.php';

$msg = ''; $msgType = '';

if (isset($_POST['save_backup'])) {
    $id = intval($_POST['bk_id'] ?? 0);
    $f  = [];
    foreach (['computer_name','ip_address','backup_type','backup_location','backup_size','status','performed_by','notes'] as $k)
        $f[$k] = "'".$conn->real_escape_string(trim($_POST[$k]??''))."'";
    $f['backup_date'] = $_POST['backup_date'] ? "'".$_POST['backup_date']."'" : 'NOW()';
    if ($id) {
        $sets=[]; foreach($f as $k=>$v) $sets[]="$k=$v";
        $conn->query("UPDATE backup_logs SET ".implode(',',$sets)." WHERE id=$id");
        $msg='Backup updated!'; $msgType='success';
    } else {
        $cols=implode(',',array_keys($f)); $vals=implode(',',array_values($f));
        $conn->query("INSERT INTO backup_logs ($cols) VALUES ($vals)");
        $msg='Backup log added!'; $msgType='success';
    }
}
if (isset($_POST['delete_backup'])) {
    $conn->query("DELETE FROM backup_logs WHERE id=".intval($_POST['del_id']));
    $msg='Log deleted.'; $msgType='warning';
}

$fPC     = trim($_GET['pc']     ?? '');
$fStatus = trim($_GET['status'] ?? '');
$sql = "SELECT * FROM backup_logs WHERE 1=1";
if ($fPC)     $sql .= " AND computer_name LIKE '%".$conn->real_escape_string($fPC)."%'";
if ($fStatus) $sql .= " AND status='".$conn->real_escape_string($fStatus)."'";
$sql .= " ORDER BY backup_date DESC";

$logs = [];
$res = $conn->query($sql);
if ($res) while ($r = $res->fetch_assoc()) $logs[] = $r;

$total   = count($logs);
$success = count(array_filter($logs, function($l){ return $l['status']==='Success'; }));
$failed  = count(array_filter($logs, function($l){ return $l['status']==='Failed'; }));
$partial = count(array_filter($logs, function($l){ return $l['status']==='Partial'; }));

// PCs with no backup in last 7 days
$noBackup_res = $conn->query("SELECT COUNT(DISTINCT computer_name) t FROM hardware_master WHERE computer_name NOT IN (SELECT DISTINCT computer_name FROM backup_logs WHERE backup_date >= NOW() - INTERVAL 7 DAY)");
$noBackup = ($noBackup_res ? $noBackup_res->fetch_assoc()['t'] : 0) ?? 0;

$_GET['view'] = 'backup';
include 'header.php';
?>
<style>
.alert-box{padding:10px 16px;border-radius:8px;font-size:12px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#dcfce7;color:#15803d;border:.5px solid #86efac;}
.alert-warning{background:#fef9c3;color:#854d0e;border:.5px solid #fde047;}
</style>

<div class="stats-row">
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-database"></i></div><div class="stat-label">Total Logs</div><div class="stat-value" style="color:#6366f1"><?=$total?></div><div class="stat-sub">backup records</div></div>
  <div class="stat-card"><div class="stat-accent green"></div><div class="stat-icon green"><i class="ti ti-circle-check"></i></div><div class="stat-label">Successful</div><div class="stat-value" style="color:#059669"><?=$success?></div><div class="stat-sub">completed OK</div></div>
  <div class="stat-card"><div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-circle-x"></i></div><div class="stat-label">Failed</div><div class="stat-value" style="color:#dc2626"><?=$failed?></div><div class="stat-sub">needs attention</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-alert-circle"></i></div><div class="stat-label">No Backup (7d)</div><div class="stat-value" style="color:#d97706"><?=$noBackup?></div><div class="stat-sub">PCs not backed up</div></div>
</div>

<?php if($msg): ?><div class="alert-box alert-<?=$msgType?>"><i class="ti ti-<?=$msgType==='success'?'circle-check':'alert-triangle'?>"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-database"></i>Backup Logs <span class="panel-badge"><?=$total?> records</span></div>
    <button onclick="document.getElementById('popupBk').classList.add('show');resetBkForm()" class="filter-btn"><i class="ti ti-plus"></i>&nbsp;Add Backup Log</button>
  </div>
  <form method="GET" class="filter-row">
    <input name="pc" placeholder="Filter PC..." value="<?=htmlspecialchars($fPC)?>">
    <select name="status">
      <option value="">All Status</option>
      <?php foreach(['Success','Failed','Partial'] as $s): ?>
      <option <?=$fStatus===$s?'selected':''?>><?=$s?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="filter-btn"><i class="ti ti-search"></i> Search</button>
    <a href="backup_tracker.php" class="filter-btn" style="background:#64748b"><i class="ti ti-x"></i> Clear</a>
  </form>
  <table class="tbl">
    <thead><tr><th>Date/Time</th><th>PC Name</th><th>IP</th><th>Type</th><th>Location</th><th>Size</th><th>Status</th><th>Performed By</th><th>Notes</th><th>Action</th></tr></thead>
    <tbody>
    <?php if(empty($logs)): ?>
      <tr><td colspan="10" style="text-align:center;padding:24px;color:#94a3b8"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No backup logs found.</td></tr>
    <?php else: foreach($logs as $l):
      $stColor=['Success'=>'p-conn','Failed'=>'p-disc','Partial'=>'p-amber'][$l['status']]??'p-gray';
    ?>
    <tr <?=$l['status']==='Failed'?'style="background:#fff5f5"':'' ?>>
      <td class="mono" style="font-size:10px"><?=htmlspecialchars($l['backup_date'])?></td>
      <td style="font-weight:500"><?=htmlspecialchars($l['computer_name']??'—')?></td>
      <td class="mono"><?=htmlspecialchars($l['ip_address']??'—')?></td>
      <td><span class="pill p-blue" style="font-size:10px"><?=$l['backup_type']?></span></td>
      <td style="font-size:11px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($l['backup_location']??'')?>">
        <?=htmlspecialchars($l['backup_location']??'—')?></td>
      <td class="mono" style="font-size:11px"><?=htmlspecialchars($l['backup_size']??'—')?></td>
      <td><span class="pill <?=$stColor?>"><?=$l['status']?></span></td>
      <td style="font-size:11px"><?=htmlspecialchars($l['performed_by']??'—')?></td>
      <td style="font-size:11px;color:#64748b"><?=htmlspecialchars($l['notes']??'—')?></td>
      <td style="white-space:nowrap">
        <button class="action-btn" onclick="editBk(<?=htmlspecialchars(json_encode($l))?>)"><i class="ti ti-edit"></i></button>
        <button class="action-btn btn-del" onclick="delBk(<?=$l['id']?>)"><i class="ti ti-trash"></i></button>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div id="popupBk" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:500px">
    <h3><i class="ti ti-database"></i>&nbsp;<span id="bkPopupTitle">Add Backup Log</span></h3>
    <form method="POST">
      <input type="hidden" name="bk_id" id="bk_id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group"><label>PC Name *</label><input name="computer_name" id="b_computer_name" required placeholder="e.g. LAB-PC-01"></div>
        <div class="form-group"><label>IP Address</label><input name="ip_address" id="b_ip_address" placeholder="192.168.x.x"></div>
        <div class="form-group"><label>Backup Type</label>
          <select name="backup_type" id="b_backup_type">
            <?php foreach(['Full','Incremental','System Image','Data Only','Database'] as $t) echo "<option>$t</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Status</label>
          <select name="status" id="b_status">
            <?php foreach(['Success','Failed','Partial'] as $s) echo "<option>$s</option>"; ?>
          </select>
        </div>
        <div class="form-group"><label>Backup Location</label><input name="backup_location" id="b_backup_location" placeholder="e.g. D:\Backups\"></div>
        <div class="form-group"><label>Backup Size</label><input name="backup_size" id="b_backup_size" placeholder="e.g. 4.5 GB"></div>
        <div class="form-group"><label>Performed By</label><input name="performed_by" id="b_performed_by" placeholder="Staff name"></div>
        <div class="form-group"><label>Backup Date/Time</label><input type="datetime-local" name="backup_date" id="b_backup_date"></div>
      </div>
      <div class="form-group"><label>Notes</label><textarea name="notes" id="b_notes" rows="2" style="width:100%;resize:vertical"></textarea></div>
      <div class="popup-actions">
        <button type="submit" name="save_backup" class="btn-save">Save</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupBk').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>
<form method="POST" id="frmDelBk"><input type="hidden" name="del_id" id="del_bk_id"><input type="hidden" name="delete_backup" value="1"></form>

<script>
function resetBkForm(){
  document.getElementById('bk_id').value='';
  document.getElementById('bkPopupTitle').textContent='Add Backup Log';
  ['computer_name','ip_address','backup_location','backup_size','performed_by','notes','backup_date'].forEach(f=>{var e=document.getElementById('b_'+f);if(e)e.value='';});
  document.getElementById('b_backup_type').value='Full';
  document.getElementById('b_status').value='Success';
}
function editBk(b){
  document.getElementById('bk_id').value=b.id;
  document.getElementById('bkPopupTitle').textContent='Edit Backup Log';
  ['computer_name','ip_address','backup_location','backup_size','performed_by','notes'].forEach(f=>{var e=document.getElementById('b_'+f);if(e)e.value=b[f]||'';});
  document.getElementById('b_backup_date').value=(b.backup_date||'').replace(' ','T').substring(0,16);
  document.getElementById('b_backup_type').value=b.backup_type||'Full';
  document.getElementById('b_status').value=b.status||'Success';
  document.getElementById('popupBk').classList.add('show');
}
function delBk(id){if(!confirm('Delete this log?'))return;document.getElementById('del_bk_id').value=id;document.getElementById('frmDelBk').submit();}
</script>
<?php include 'footer.php'; ?>