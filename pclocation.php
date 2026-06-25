<?php
include 'auth.php';
include 'db.php';

$fIP       = trim($_GET['ip']        ?? '');
$fPC       = trim($_GET['pc']        ?? '');
$fDate     = trim($_GET['date']      ?? '');
$fFloor    = trim($_GET['floor']     ?? 'Ground');  // default Ground Floor
$fRoom     = trim($_GET['room_name'] ?? '');
$fStatus   = trim($_GET['status']    ?? 'Located'); // default Located
$page      = max(1,intval($_GET['pg'] ?? 1));
$perPage   = 15;

// ─── hardware PCs for dropdown ───────────────────────────────────────────────
// $hw_res = $conn->query("SELECT computer_name,ip_address,motherboard_serial FROM hardware_status ORDER BY computer_name ASC");
// $hardware_pcs = [];
// while($r=$hw_res->fetch_assoc()) $hardware_pcs[]=$r;
$hw_res = $conn->query("SELECT computer_name,ip_address,motherboard_serial FROM hardware_master ORDER BY computer_name ASC");
$hardware_pcs = [];
if($hw_res) while($r=$hw_res->fetch_assoc()) $hardware_pcs[]=$r;

// ─── INSERT ENTRY ─────────────────────────────────────────────────────────────
if(isset($_POST['add_room'])){
    $chk=$conn->prepare("SELECT id FROM pc_location WHERE motherboard_serial=? AND motherboard_serial!='' LIMIT 1");
    $chk->bind_param("s",$_POST['motherboard_serial']); $chk->execute();
    if($chk->get_result()->num_rows>0){
        echo "<script>alert('This PC is already located!');window.location='pclocation.php';</script>"; exit;
    }
    $s=$conn->prepare("INSERT INTO pc_location (pc_name,room,row_no,table_no,last_updated,ip_address,motherboard_serial,room_no,room_name,floor_no,created_at) VALUES (?,?,?,?,NOW(),?,?,?,?,?,NOW())");
    $s->bind_param("sssssssss",$_POST['pc_name'],$_POST['room'],$_POST['row_no'],$_POST['table_no'],$_POST['ip_address'],$_POST['motherboard_serial'],$_POST['room_no'],$_POST['room_name'],$_POST['floor_no']);
    $s->execute();
    echo "<script>alert('Entry Added!');window.location='pclocation.php';</script>"; exit;
}

// ─── ADD ROOM ─────────────────────────────────────────────────────────────────
if(isset($_POST['add_new_room'])){
    $s=$conn->prepare("INSERT INTO pc_location (floor_no,room_no,room_name,row_no,table_no,created_at) VALUES (?,?,?,?,?,NOW())");
    $s->bind_param("sssss",$_POST['floor_no'],$_POST['room_no'],$_POST['room_name'],$_POST['row_no'],$_POST['table_no']);
    $s->execute();
    echo "<script>alert('Room Added!');window.location='pclocation.php';</script>"; exit;
}

// ─── UPDATE ───────────────────────────────────────────────────────────────────
if(isset($_POST['update_room'])){
    $edit_id    = intval($_POST['edit_id']);
    $new_serial = trim($_POST['motherboard_serial']);
    if($new_serial !== ''){
        $chk=$conn->prepare("SELECT id FROM pc_location WHERE motherboard_serial=? AND id!=? LIMIT 1");
        $chk->bind_param("si",$new_serial,$edit_id); $chk->execute();
        if($chk->get_result()->num_rows>0){
            echo "<script>alert('This PC is already assigned to another location!');window.location='pclocation.php';</script>"; exit;
        }
    }
    $s=$conn->prepare("UPDATE pc_location SET pc_name=?,ip_address=?,motherboard_serial=?,floor_no=?,room_no=?,room_name=?,row_no=?,table_no=?,last_updated=NOW() WHERE id=?");
    $s->bind_param("ssssssssi",$_POST['pc_name'],$_POST['ip_address'],$new_serial,$_POST['floor_no'],$_POST['room_no'],$_POST['room_name'],$_POST['row_no'],$_POST['table_no'],$edit_id);
    $s->execute();
    echo "<script>alert('Updated!');window.location='pclocation.php';</script>"; exit;
}

// ─── DELETE ───────────────────────────────────────────────────────────────────
if(isset($_POST['delete_entry'])){
    $s=$conn->prepare("DELETE FROM pc_location WHERE id=?");
    $s->bind_param("i",$_POST['delete_id']);
    $s->execute();
    echo "<script>alert('Entry Deleted!');window.location='pclocation.php';</script>"; exit;
}

// ─── counters ─────────────────────────────────────────────────────────────────
$locatedCount   = $conn->query("SELECT COUNT(*) t FROM pc_location WHERE pc_name IS NOT NULL AND pc_name!='' AND ip_address IS NOT NULL AND ip_address!='' AND motherboard_serial IS NOT NULL AND motherboard_serial!=''")->fetch_assoc()['t']??0;
$unlocatedCount = $conn->query("SELECT COUNT(*) t FROM pc_location WHERE (pc_name IS NULL OR pc_name='' OR ip_address IS NULL OR ip_address='' OR motherboard_serial IS NULL OR motherboard_serial='')")->fetch_assoc()['t']??0;
$totalCount     = $locatedCount + $unlocatedCount;

// ─── main query ───────────────────────────────────────────────────────────────
$sql="SELECT *,CASE WHEN ip_address IS NULL OR ip_address='' OR pc_name IS NULL OR pc_name='' OR motherboard_serial IS NULL OR motherboard_serial='' THEN 'Unlocated' ELSE 'Located' END AS loc_status FROM pc_location WHERE 1=1";
if($fIP)    $sql.=" AND ip_address LIKE '%".$conn->real_escape_string($fIP)."%'";
if($fPC)    $sql.=" AND pc_name LIKE '%".$conn->real_escape_string($fPC)."%'";
if($fDate)  $sql.=" AND DATE(last_updated)='".$conn->real_escape_string($fDate)."'";
if($fFloor) $sql.=" AND floor_no='".$conn->real_escape_string($fFloor)."'";
if($fRoom)  $sql.=" AND room_name LIKE '%".$conn->real_escape_string($fRoom)."%'";
if($fStatus==='Located')   $sql.=" AND ip_address IS NOT NULL AND ip_address!='' AND pc_name IS NOT NULL AND pc_name!='' AND motherboard_serial IS NOT NULL AND motherboard_serial!=''";
if($fStatus==='Unlocated') $sql.=" AND (ip_address IS NULL OR ip_address='' OR pc_name IS NULL OR pc_name='' OR motherboard_serial IS NULL OR motherboard_serial='')";
$sql.=" ORDER BY id DESC";
$res=$conn->query($sql);
$allRows=[]; while($r=$res->fetch_assoc()) $allRows[]=$r;
$total=count($allRows); $pages=max(1,ceil($total/$perPage));
$page=min($page,$pages); $rows=array_slice($allRows,($page-1)*$perPage,$perPage);

$_GET['view']='location';
include 'header.php';
?>

<div class="stats-row">
  <div class="stat-card"><div class="stat-accent blue"></div><div class="stat-icon blue"><i class="ti ti-map-pin"></i></div><div class="stat-label">Total Places</div><div class="stat-value" style="color:#6366f1"><?=$totalCount?></div><div class="stat-sub">pc_location table</div></div>
  <div class="stat-card"><div class="stat-accent green"></div><div class="stat-icon green"><i class="ti ti-circle-check"></i></div><div class="stat-label">Located PCs</div><div class="stat-value" style="color:#059669"><?=$locatedCount?></div><div class="stat-sub">assigned seats</div></div>
  <div class="stat-card"><div class="stat-accent red"></div><div class="stat-icon red"><i class="ti ti-circle-x"></i></div><div class="stat-label">Unlocated</div><div class="stat-value" style="color:#dc2626"><?=$unlocatedCount?></div><div class="stat-sub">empty seats</div></div>
  <div class="stat-card"><div class="stat-accent amber"></div><div class="stat-icon amber"><i class="ti ti-list"></i></div><div class="stat-label">Showing</div><div class="stat-value" style="color:#d97706"><?=$total?></div><div class="stat-sub">filtered results</div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title"><i class="ti ti-map-pin"></i>PC Location <span class="panel-badge"><?=$total?> records</span></div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <?php $bp=http_build_query(['ip'=>$fIP,'pc'=>$fPC,'date'=>$fDate,'floor'=>$fFloor,'room_name'=>$fRoom,'status'=>$fStatus]); ?>
      <div class="pager">
        <span class="pg-info">Page <?=$page?>/<?=$pages?></span>
        <a href="pclocation.php?<?=$bp?>&pg=<?=$page-1?>" class="pg-btn" <?=$page<=1?'style="pointer-events:none;opacity:.35"':''?>>‹</a>
        <a href="pclocation.php?<?=$bp?>&pg=<?=$page+1?>" class="pg-btn" <?=$page>=$pages?'style="pointer-events:none;opacity:.35"':''?>>›</a>
      </div>
      <button onclick="document.getElementById('popupEntry').classList.add('show')" class="filter-btn"><i class="ti ti-plus" style="font-size:13px"></i>Add Entry</button>
      <button onclick="document.getElementById('popupRoom').classList.add('show')" class="filter-btn" style="background:#8b5cf6"><i class="ti ti-building" style="font-size:13px"></i>Add Room</button>
      <a href="change_location.php" class="filter-btn" style="background:#0d9488"><i class="ti ti-arrows-exchange" style="font-size:13px"></i>Change Location</a>
      <a href="export_pclocation.php?<?=$bp?>" class="filter-btn red"><i class="ti ti-download" style="font-size:13px"></i>Export</a>
    </div>
  </div>

  <form method="GET" class="filter-row">
    <input name="ip" placeholder="Filter IP..." value="<?=htmlspecialchars($fIP)?>">
    <input name="pc" placeholder="Filter PC name..." value="<?=htmlspecialchars($fPC)?>">
    <input type="date" name="date" value="<?=htmlspecialchars($fDate)?>">
    <select name="floor">
      <option value="">All Floors</option>
      <?php foreach(['Ground','First','Second','Third'] as $f): ?>
        <option value="<?=$f?>" <?=$fFloor===$f?'selected':''?>><?=$f?></option>
      <?php endforeach; ?>
    </select>
    <select name="room_name">
      <option value="">All Rooms</option>
      <?php foreach(['Vlab','Admission Office','RND','Server Room','Office','Class Room','Account','Reception','Director Room','Store'] as $rm): ?>
        <option value="<?=$rm?>" <?=$fRoom===$rm?'selected':''?>><?=$rm?></option>
      <?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">All Status</option>
      <option value="Located" <?=$fStatus==='Located'?'selected':''?>>Located</option>
      <option value="Unlocated" <?=$fStatus==='Unlocated'?'selected':''?>>Unlocated</option>
    </select>
    <button type="submit" class="filter-btn"><i class="ti ti-search" style="font-size:13px"></i>Search</button>
  </form>

  <table class="tbl">
    <thead><tr>
      <th>ID</th><th>IP Address</th><th>PC Name</th><th>PC ID (MB Serial)</th>
      <th>Floor</th><th>Room No</th><th>Room Name</th><th>Row</th><th>Table</th>
      <th>Updated</th><th>Status</th><th style="text-align:center">Action</th>
    </tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="12" style="text-align:center;padding:24px;color:var(--color-text-tertiary)"><i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:6px"></i>No records found</td></tr>
    <?php else: foreach($rows as $r): $located=$r['loc_status']==='Located'; ?>
    <tr>
      <td style="color:var(--color-text-tertiary)"><?=$r['id']?></td>
      <td class="mono"><?=htmlspecialchars($r['ip_address']??'N/A')?></td>
      <td style="font-weight:500"><?=htmlspecialchars($r['pc_name']??'N/A')?></td>
      <td class="mono" style="font-size:9px"><?=htmlspecialchars($r['motherboard_serial']??'N/A')?></td>
      <td><?=htmlspecialchars($r['floor_no']??'N/A')?></td>
      <td><?=htmlspecialchars($r['room_no']??'N/A')?></td>
      <td><?=htmlspecialchars($r['room_name']??'N/A')?></td>
      <td><?=htmlspecialchars($r['row_no']??'N/A')?></td>
      <td><?=htmlspecialchars($r['table_no']??'N/A')?></td>
      <td class="mono" style="font-size:10px"><?=htmlspecialchars($r['last_updated']??$r['created_at']??'—')?></td>
      <td><?= $located?'<span class="pill p-conn">Located</span>':'<span class="pill p-disc">Unlocated</span>' ?></td>
      <td style="text-align:center;white-space:nowrap">
        <button class="action-btn" title="Edit"
          onclick="editEntry(
            '<?=$r['id']?>',
            '<?=addslashes($r['floor_no']??'')?>',
            '<?=addslashes($r['room_no']??'')?>',
            '<?=addslashes($r['room_name']??'')?>',
            '<?=addslashes($r['row_no']??'')?>',
            '<?=addslashes($r['table_no']??'')?>',
            '<?=addslashes($r['ip_address']??'')?>',
            '<?=addslashes($r['pc_name']??'')?>',
            '<?=addslashes($r['motherboard_serial']??'')?>')">
          <i class="ti ti-edit"></i>
        </button>
        <button class="action-btn btn-del" title="Delete"
          onclick="deleteEntry('<?=$r['id']?>', '<?=addslashes($r['pc_name']??$r['room_name']??'this entry')?>')">
          <i class="ti ti-trash"></i>
        </button>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- POPUP 1: Add Entry -->
<div id="popupEntry" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box">
    <h3><i class="ti ti-plus"></i>Add New Entry</h3>
    <form method="POST">
      <div class="form-group">
        <label>Search PC</label>
        <input type="text" id="pc_search" placeholder="Type to search PC..." onkeyup="filterPC('pc_select','pc_search')">
      </div>
      <div class="form-group">
        <label>PC Name</label>
        <select name="pc_name" id="pc_select" onchange="fillPCData('pc_select','ip_address','motherboard_serial')" required>
          <option value="">-- Select PC --</option>
          <?php foreach($hardware_pcs as $p): ?>
          <option value="<?=htmlspecialchars($p['computer_name'])?>" data-ip="<?=htmlspecialchars($p['ip_address'])?>" data-serial="<?=htmlspecialchars($p['motherboard_serial'])?>">
            <?=htmlspecialchars($p['computer_name'])?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Floor</label>
        <select name="floor_no" required><option value="">Select Floor</option><?php foreach(['Ground','First','Second','Third'] as $f) echo "<option>$f</option>"; ?></select>
      </div>
      <div class="form-group"><label>Room</label><input name="room" placeholder="Room" required></div>
      <div class="form-group"><label>Room No</label><input name="room_no" placeholder="Room No"></div>
      <div class="form-group"><label>Room Name</label><input name="room_name" placeholder="Room Name"></div>
      <div class="form-group"><label>Row No</label><input name="row_no" placeholder="Row No" required></div>
      <div class="form-group"><label>Table No</label><input name="table_no" placeholder="Table No" required></div>
      <div class="form-group"><label>IP Address</label><input name="ip_address" id="ip_address" placeholder="Auto-fill" readonly></div>
      <div class="form-group"><label>MB Serial</label><input name="motherboard_serial" id="motherboard_serial" placeholder="Auto-fill" readonly></div>
      <div class="popup-actions">
        <button type="submit" name="add_room" class="btn-save">Save Entry</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupEntry').classList.remove('show')">Close</button>
      </div>
    </form>
  </div>
</div>

<!-- POPUP 2: Add Room -->
<div id="popupRoom" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box">
    <h3><i class="ti ti-building"></i>Add New Room</h3>
    <form method="POST">
      <div class="form-group"><label>Floor</label>
        <select name="floor_no" required><option value="">Select Floor</option><?php foreach(['Ground','First','Second','Third'] as $f) echo "<option>$f</option>"; ?></select>
      </div>
      <div class="form-group"><label>Room Number</label><input name="room_no" placeholder="Room Number" required></div>
      <div class="form-group"><label>Room Name</label>
        <select name="room_name" required><option value="">Select Room Name</option><?php foreach(['Vlab','Admission Office','RND','Server Room','Office','Class Room','Account','Reception','Director Room','Store','Co-ordinator','Physic-Lab','Chemistry-Lab','Bio-Lab','Sport','Guard Room','Attendence Room'] as $rm) echo "<option>$rm</option>"; ?></select>
      </div>
      <div class="form-group"><label>Row Number</label><input name="row_no" placeholder="Row Number" required></div>
      <div class="form-group"><label>Table Number</label><input name="table_no" placeholder="Table Number" required></div>
      <div class="popup-actions">
        <button type="submit" name="add_new_room" class="btn-save">Save Room</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupRoom').classList.remove('show')">Close</button>
      </div>
    </form>
  </div>
</div>

<!-- POPUP 3: Edit Entry -->
<div id="popupEdit" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box">
    <h3><i class="ti ti-edit"></i>Update Entry</h3>
    <form method="POST">
      <input type="hidden" name="edit_id" id="edit_id">
      <div class="form-group"><label>Floor</label>
        <select name="floor_no" id="edit_floor" required><?php foreach(['Ground','First','Second','Third'] as $f) echo "<option>$f</option>"; ?></select>
      </div>
      <div class="form-group"><label>Room No</label><input name="room_no" id="edit_room_no" placeholder="Room No" required></div>
      <div class="form-group"><label>Room Name</label>
        <select name="room_name" id="edit_room_name"><?php foreach(['Vlab','Admission Office','RND','Server Room','Office','Class Room','Account','Reception','Director Room','Store','Jibran Office'] as $rm) echo "<option>$rm</option>"; ?></select>
      </div>
      <div class="form-group"><label>Row No</label><input name="row_no" id="edit_row_no" placeholder="Row No" required></div>
      <div class="form-group"><label>Table No</label><input name="table_no" id="edit_table_no" placeholder="Table No" required></div>
      <div class="form-group">
        <label>Search PC</label>
        <input type="text" id="edit_pc_search" placeholder="Type to search PC..." onkeyup="filterPC('edit_pc_select','edit_pc_search')">
      </div>
      <div class="form-group">
        <label>PC Name</label>
        <select name="pc_name" id="edit_pc_select" onchange="fillPCData('edit_pc_select','edit_ip_address','edit_pc_id')">
          <option value="">-- Select PC --</option>
          <?php foreach($hardware_pcs as $p): ?>
          <option value="<?=htmlspecialchars($p['computer_name'])?>" data-ip="<?=htmlspecialchars($p['ip_address'])?>" data-serial="<?=htmlspecialchars($p['motherboard_serial'])?>">
            <?=htmlspecialchars($p['computer_name'])?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>IP Address</label><input name="ip_address" id="edit_ip_address" placeholder="Auto-fill from PC selection"></div>
      <div class="form-group"><label>MB Serial</label><input name="motherboard_serial" id="edit_pc_id" placeholder="Auto-fill from PC selection"></div>
      <div class="popup-actions">
        <button type="submit" name="update_room" class="btn-save">Update</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupEdit').classList.remove('show')">Close</button>
      </div>
    </form>
  </div>
</div>

<!-- POPUP 4: Delete Confirm -->
<div id="popupDelete" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:360px">
    <h3 style="color:#dc2626"><i class="ti ti-trash" style="color:#dc2626"></i>Delete Entry</h3>
    <p style="font-size:12px;color:var(--color-text-secondary);margin-bottom:16px">
      Are you sure you want to delete "<strong id="delete_entry_name"></strong>"? This cannot be undone.
    </p>
    <form method="POST">
      <input type="hidden" name="delete_id" id="delete_id">
      <div class="popup-actions">
        <button type="submit" name="delete_entry" class="btn-save" style="background:#ef4444;">Delete</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupDelete').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterPC(selectId, searchId){
  var q   = document.getElementById(searchId).value.toLowerCase();
  var sel = document.getElementById(selectId);
  for(var i=0;i<sel.options.length;i++){
    sel.options[i].style.display=(i===0||sel.options[i].text.toLowerCase().includes(q))?'':'none';
  }
}
function fillPCData(selectId, ipFieldId, serialFieldId){
  var sel=document.getElementById(selectId);
  var opt=sel.options[sel.selectedIndex];
  document.getElementById(ipFieldId).value    =opt.dataset.ip    ||'';
  document.getElementById(serialFieldId).value=opt.dataset.serial||'';
}
function editEntry(id,floor,roomNo,roomName,rowNo,tableNo,ip,pcName,pcId){
  document.getElementById('edit_id').value       =id;
  document.getElementById('edit_floor').value    =floor||'Ground';
  document.getElementById('edit_room_no').value  =roomNo||'';
  document.getElementById('edit_room_name').value=roomName||'';
  document.getElementById('edit_row_no').value   =rowNo||'';
  document.getElementById('edit_table_no').value =tableNo||'';
  document.getElementById('edit_ip_address').value=ip||'';
  document.getElementById('edit_pc_id').value    =pcId||'';
  var sel=document.getElementById('edit_pc_select');
  var matched=false;
  for(var i=0;i<sel.options.length;i++){
    if(sel.options[i].value===pcName){ sel.selectedIndex=i; matched=true; break; }
  }
  if(!matched){ sel.selectedIndex=0; document.getElementById('edit_pc_search').value=pcName||''; }
  else { document.getElementById('edit_pc_search').value=''; }
  document.getElementById('popupEdit').classList.add('show');
}
function deleteEntry(id,name){
  document.getElementById('delete_id').value=id;
  document.getElementById('delete_entry_name').textContent=name;
  document.getElementById('popupDelete').classList.add('show');
}
</script>
<?php include 'footer.php'; ?>