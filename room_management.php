<?php
include 'auth.php';
include 'db.php';

// ─── ADD ROOM ────────────────────────────────────────────────────────────────
if (isset($_POST['add_room'])) {
    $s = $conn->prepare("INSERT INTO room_management (floor_no, room_no, room_name, row_number, table_number, created_at) VALUES (?,?,?,?,?,NOW())");
    $s->bind_param("sssii", $_POST['floor_no'], $_POST['room_no'], $_POST['room_name'], $_POST['row_number'], $_POST['table_number']);
    $s->execute();
    echo "<script>alert('Room Added!');window.location='room_management.php';</script>"; exit;
}

// ─── UPDATE ROOM ─────────────────────────────────────────────────────────────
if (isset($_POST['update_room'])) {
    $s = $conn->prepare("UPDATE room_management SET floor_no=?, room_no=?, room_name=?, row_number=?, table_number=? WHERE id=?");
    $s->bind_param("ssssii", $_POST['floor_no'], $_POST['room_no'], $_POST['room_name'], $_POST['row_number'], $_POST['table_number'], $_POST['edit_id']);
    $s->execute();
    echo "<script>alert('Room Updated!');window.location='room_management.php';</script>"; exit;
}

// ─── DELETE ROOM ─────────────────────────────────────────────────────────────
if (isset($_POST['delete_room'])) {
    $s = $conn->prepare("DELETE FROM room_management WHERE id=?");
    $s->bind_param("i", $_POST['delete_id']);
    $s->execute();
    echo "<script>alert('Room Deleted!');window.location='room_management.php';</script>"; exit;
}

// ─── STATS ───────────────────────────────────────────────────────────────────
$totalRooms  = $conn->query("SELECT COUNT(*) t FROM room_management")->fetch_assoc()['t'] ?? 0;
$groundRooms = $conn->query("SELECT COUNT(*) t FROM room_management WHERE floor_no='Ground'")->fetch_assoc()['t'] ?? 0;
$firstRooms  = $conn->query("SELECT COUNT(*) t FROM room_management WHERE floor_no='First'")->fetch_assoc()['t'] ?? 0;
$secondRooms = $conn->query("SELECT COUNT(*) t FROM room_management WHERE floor_no='Second'")->fetch_assoc()['t'] ?? 0;
$thirdRooms  = $conn->query("SELECT COUNT(*) t FROM room_management WHERE floor_no='Third'")->fetch_assoc()['t'] ?? 0;

// ─── FETCH ALL ROOMS WITH PC COUNT ───────────────────────────────────────────
$floors = ['Ground', 'First', 'Second', 'Third'];
$roomsByFloor = [];
$allRooms = [];
foreach ($floors as $fl) {
    $res = $conn->query("
        SELECT rm.*,
               COUNT(CASE WHEN pl.pc_name IS NOT NULL AND pl.pc_name!='' AND pl.motherboard_serial IS NOT NULL AND pl.motherboard_serial!='' THEN 1 END) AS pc_count
        FROM room_management rm
        LEFT JOIN pc_location pl ON pl.floor_no = rm.floor_no AND pl.room_no = rm.room_no
        WHERE rm.floor_no = '" . $conn->real_escape_string($fl) . "'
        GROUP BY rm.id
        ORDER BY rm.id ASC
    ");
    $roomsByFloor[$fl] = [];
    while ($r = $res->fetch_assoc()) {
        $roomsByFloor[$fl][] = $r;
        $allRooms[] = $r;
    }
}

$_GET['view'] = 'rooms';
include 'header.php';
?>

<!-- STATS ROW -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-accent blue"></div>
    <div class="stat-icon blue"><i class="ti ti-building"></i></div>
    <div class="stat-label">Total Rooms</div>
    <div class="stat-value" style="color:#6366f1"><?= $totalRooms ?></div>
    <div class="stat-sub">in room_management</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent amber"></div>
    <div class="stat-icon amber"><i class="ti ti-building-skyscraper"></i></div>
    <div class="stat-label">Ground Floor</div>
    <div class="stat-value" style="color:#d97706"><?= $groundRooms ?></div>
    <div class="stat-sub">rooms</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent green"></div>
    <div class="stat-icon green"><i class="ti ti-building-skyscraper"></i></div>
    <div class="stat-label">First Floor</div>
    <div class="stat-value" style="color:#059669"><?= $firstRooms ?></div>
    <div class="stat-sub">rooms</div>
  </div>
  <div class="stat-card">
    <div class="stat-accent purple"></div>
    <div class="stat-icon purple"><i class="ti ti-building-skyscraper"></i></div>
    <div class="stat-label">Second Floor</div>
    <div class="stat-value" style="color:#7c3aed"><?= $secondRooms ?></div>
    <div class="stat-sub">rooms</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;">
  <div class="stat-card">
    <div class="stat-accent teal"></div>
    <div class="stat-icon teal"><i class="ti ti-building-skyscraper"></i></div>
    <div class="stat-label">Third Floor</div>
    <div class="stat-value" style="color:#0d9488"><?= $thirdRooms ?></div>
    <div class="stat-sub">rooms</div>
  </div>
</div>

<!-- Action Buttons -->
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;">
  <button onclick="document.getElementById('popupAdd').classList.add('show')" class="filter-btn">
    <i class="ti ti-plus"></i>&nbsp;Add Room
  </button>
  <button onclick="printReport()" class="filter-btn" style="background:#0d9488;">
    <i class="ti ti-printer"></i>&nbsp;Print Report
  </button>
  <a href="export_rooms.php" class="filter-btn" style="background:#ef4444;">
    <i class="ti ti-download"></i>&nbsp;Export CSV
  </a>
</div>

<?php
$floorColors = [
  'Ground' => ['accent'=>'amber',  'label_color'=>'#d97706'],
  'First'  => ['accent'=>'green',  'label_color'=>'#059669'],
  'Second' => ['accent'=>'purple', 'label_color'=>'#7c3aed'],
  'Third'  => ['accent'=>'teal',   'label_color'=>'#0d9488'],
];
foreach ($floors as $fl):
  if (empty($roomsByFloor[$fl])) continue;
  $col = $floorColors[$fl];
?>
<div class="panel" style="margin-bottom:16px;">
  <div class="panel-head">
    <div class="panel-title">
      <i class="ti ti-building" style="color:<?= $col['label_color'] ?>"></i>
      &nbsp;<?= $fl ?> Floor
      <span class="panel-badge" style="background:none;border:.5px solid <?= $col['label_color'] ?>;color:<?= $col['label_color'] ?>">
        <?= count($roomsByFloor[$fl]) ?> rooms
      </span>
    </div>
  </div>
  <table class="tbl">
    <thead>
      <tr>
        <th>ID</th>
        <th>Room No</th>
        <th>Room Name</th>
        <th style="text-align:right">Rows</th>
        <th style="text-align:right">Tables</th>
        <th style="text-align:center">PCs Assigned</th>
        <th style="text-align:center">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($roomsByFloor[$fl] as $r): ?>
      <tr>
        <td style="color:var(--color-text-tertiary)"><?= $r['id'] ?></td>
        <td>
          <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#ede9fe;color:#6366f1;font-size:11px;font-weight:600;">
            <?= htmlspecialchars($r['room_no']) ?>
          </span>
        </td>
        <td style="font-weight:500"><?= htmlspecialchars($r['room_name']) ?></td>
        <td style="text-align:right"><?= $r['row_number'] ?></td>
        <td style="text-align:right"><?= $r['table_number'] ?></td>
        <td style="text-align:center">
          <?php if ($r['pc_count'] > 0): ?>
            <span class="pill p-green"><?= $r['pc_count'] ?> PCs</span>
          <?php else: ?>
            <span style="color:var(--color-text-tertiary);font-size:11px">0 PCs</span>
          <?php endif; ?>
        </td>
        <td style="text-align:center">
          <button class="action-btn" title="Edit"
            onclick="editRoom('<?= $r['id'] ?>','<?= addslashes($r['floor_no']) ?>','<?= addslashes($r['room_no']) ?>','<?= addslashes($r['room_name']) ?>','<?= $r['row_number'] ?>','<?= $r['table_number'] ?>')">
            <i class="ti ti-edit"></i>
          </button>
          <button class="action-btn btn-del" title="Delete"
            onclick="deleteRoom('<?= $r['id'] ?>', '<?= addslashes($r['room_name']) ?>')">
            <i class="ti ti-trash"></i>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>

<!-- ═══════════════════════════════════════════════════════════
     PRINT REPORT — hidden div, shown only on window.print()
═══════════════════════════════════════════════════════════ -->
<div id="printArea" style="display:none;">
  <style>
    @media print {
      body * { visibility: hidden; }
      #printArea, #printArea * { visibility: visible; }
      #printArea { position: absolute; top: 0; left: 0; width: 100%; }
    }
  </style>
  <div style="font-family:'Segoe UI',sans-serif;padding:20px;color:#0f172a;">
    <div style="text-align:center;border-bottom:2px solid #6366f1;padding-bottom:12px;margin-bottom:20px;">
      <div style="font-size:22px;font-weight:700;color:#1e1b4b;">NCR-CET IT Lab</div>
      <div style="font-size:14px;color:#6366f1;font-weight:600;margin-top:4px;">Room Management Report</div>
      <div style="font-size:11px;color:#64748b;margin-top:4px;">
        Generated: <?= date('d M Y, h:i A') ?> &nbsp;|&nbsp;
        Total Rooms: <?= $totalRooms ?> &nbsp;|&nbsp;
        Floors: Ground (<?= $groundRooms ?>), First (<?= $firstRooms ?>), Second (<?= $secondRooms ?>), Third (<?= $thirdRooms ?>)
      </div>
    </div>

    <div style="margin-bottom:20px;">
      <div style="font-size:13px;font-weight:600;color:#1e1b4b;margin-bottom:8px;padding:6px 10px;background:#f1f5f9;border-left:3px solid #6366f1;">
        Floor Summary
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
          <tr style="background:#1e1b4b;color:#fff;">
            <th style="padding:8px 10px;text-align:left;">Floor</th>
            <th style="padding:8px 10px;text-align:center;">Total Rooms</th>
            <th style="padding:8px 10px;text-align:center;">Total Rows</th>
            <th style="padding:8px 10px;text-align:center;">Total Tables</th>
            <th style="padding:8px 10px;text-align:center;">PCs Assigned</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $floorSummary = [];
          foreach ($allRooms as $r) {
              $fl = $r['floor_no'];
              if (!isset($floorSummary[$fl])) $floorSummary[$fl] = ['rooms'=>0,'rows'=>0,'tables'=>0,'pcs'=>0];
              $floorSummary[$fl]['rooms']++;
              $floorSummary[$fl]['rows']  += $r['row_number'];
              $floorSummary[$fl]['tables']+= $r['table_number'];
              $floorSummary[$fl]['pcs']   += $r['pc_count'];
          }
          $grandRooms=$grandRows=$grandTables=$grandPCs=0;
          foreach (['Ground','First','Second','Third'] as $idx => $fl):
            if (!isset($floorSummary[$fl])) continue;
            $s = $floorSummary[$fl];
            $grandRooms+=$s['rooms']; $grandRows+=$s['rows']; $grandTables+=$s['tables']; $grandPCs+=$s['pcs'];
            $bg = $idx%2==0?'#ffffff':'#f8fafc';
          ?>
          <tr style="background:<?= $bg ?>;">
            <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;font-weight:500;"><?= $fl ?> Floor</td>
            <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $s['rooms'] ?></td>
            <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $s['rows'] ?></td>
            <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $s['tables'] ?></td>
            <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $s['pcs'] ?></td>
          </tr>
          <?php endforeach; ?>
          <tr style="background:#1e1b4b;color:#fff;font-weight:600;">
            <td style="padding:8px 10px;">TOTAL</td>
            <td style="padding:8px 10px;text-align:center;"><?= $grandRooms ?></td>
            <td style="padding:8px 10px;text-align:center;"><?= $grandRows ?></td>
            <td style="padding:8px 10px;text-align:center;"><?= $grandTables ?></td>
            <td style="padding:8px 10px;text-align:center;"><?= $grandPCs ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <?php foreach (['Ground','First','Second','Third'] as $fl):
      if (empty($roomsByFloor[$fl])) continue; ?>
    <div style="margin-bottom:18px;">
      <div style="font-size:13px;font-weight:600;color:#1e1b4b;margin-bottom:8px;padding:6px 10px;background:#f1f5f9;border-left:3px solid #6366f1;">
        <?= $fl ?> Floor — <?= count($roomsByFloor[$fl]) ?> Rooms
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
          <tr style="background:#334155;color:#fff;">
            <th style="padding:7px 10px;text-align:left;">ID</th>
            <th style="padding:7px 10px;text-align:left;">Room No</th>
            <th style="padding:7px 10px;text-align:left;">Room Name</th>
            <th style="padding:7px 10px;text-align:center;">Rows</th>
            <th style="padding:7px 10px;text-align:center;">Tables</th>
            <th style="padding:7px 10px;text-align:center;">PCs Assigned</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($roomsByFloor[$fl] as $idx => $r): ?>
          <tr style="background:<?= $idx%2==0?'#ffffff':'#f8fafc' ?>;">
            <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;color:#94a3b8;"><?= $r['id'] ?></td>
            <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;"><?= htmlspecialchars($r['room_no']) ?></td>
            <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;font-weight:500;"><?= htmlspecialchars($r['room_name']) ?></td>
            <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $r['row_number'] ?></td>
            <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $r['table_number'] ?></td>
            <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:center;">
              <?= $r['pc_count'] > 0 ? $r['pc_count'].' PCs' : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:20px;border-top:1px solid #e2e8f0;padding-top:10px;font-size:10px;color:#94a3b8;text-align:center;">
      NCR-CET IT Lab Network Dashboard &nbsp;|&nbsp; Room Management Report &nbsp;|&nbsp; <?= date('d M Y') ?>
    </div>
  </div>
</div>

<!-- POPUP: Add Room -->
<div id="popupAdd" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box">
    <h3><i class="ti ti-plus"></i>&nbsp;Add New Room</h3>
    <form method="POST">
      <div class="form-group">
        <label>Floor</label>
        <select name="floor_no" required>
          <option value="">Select Floor</option>
          <?php foreach (['Ground','First','Second','Third'] as $f) echo "<option>$f</option>"; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Room Number</label>
        <input name="room_no" placeholder="e.g. 5" required>
      </div>
      <div class="form-group">
        <label>Room Name</label>
        <select name="room_name" required>
          <option value="">Select Room Name</option>
          <?php foreach (['Reception','Admission Office','Office','Store','Class Room','Account','Vlab','Server Room','Director Room','RND','Principal','Guard Room','Attendance Room','Co-ordinator','Physic-Lab','Chemistry-Lab','Bio-Lab'] as $rm) echo "<option>$rm</option>"; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Number of Rows</label>
        <input type="number" name="row_number" placeholder="e.g. 10" min="1" required>
      </div>
      <div class="form-group">
        <label>Number of Tables</label>
        <input type="number" name="table_number" placeholder="e.g. 10" min="1" required>
      </div>
      <div class="popup-actions">
        <button type="submit" name="add_room" class="btn-save">Save Room</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupAdd').classList.remove('show')">Close</button>
      </div>
    </form>
  </div>
</div>

<!-- POPUP: Edit Room -->
<div id="popupEdit" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box">
    <h3><i class="ti ti-edit"></i>&nbsp;Edit Room</h3>
    <form method="POST">
      <input type="hidden" name="edit_id" id="edit_id">
      <div class="form-group">
        <label>Floor</label>
        <select name="floor_no" id="edit_floor" required>
          <?php foreach (['Ground','First','Second','Third'] as $f) echo "<option>$f</option>"; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Room Number</label>
        <input name="room_no" id="edit_room_no" placeholder="Room Number" required>
      </div>
      <div class="form-group">
        <label>Room Name</label>
        <select name="room_name" id="edit_room_name" required>
          <?php foreach (['Reception','Admission Office','Office','Store','Class Room','Account','Vlab','Server Room','Director Room','RND','Principal','Guard Room','Attendance Room','Co-ordinator','Physic-Lab','Chemistry-Lab','Bio-Lab'] as $rm) echo "<option>$rm</option>"; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Number of Rows</label>
        <input type="number" name="row_number" id="edit_row_number" placeholder="Rows" min="1" required>
      </div>
      <div class="form-group">
        <label>Number of Tables</label>
        <input type="number" name="table_number" id="edit_table_number" placeholder="Tables" min="1" required>
      </div>
      <div class="popup-actions">
        <button type="submit" name="update_room" class="btn-save">Update Room</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupEdit').classList.remove('show')">Close</button>
      </div>
    </form>
  </div>
</div>

<!-- POPUP: Delete Confirm -->
<div id="popupDelete" class="popup" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="popup-box" style="width:360px">
    <h3 style="color:#dc2626"><i class="ti ti-trash" style="color:#dc2626"></i>&nbsp;Delete Room</h3>
    <p style="font-size:12px;color:var(--color-text-secondary);margin-bottom:16px">
      Are you sure you want to delete "<strong id="delete_room_name"></strong>"? This cannot be undone.
    </p>
    <form method="POST">
      <input type="hidden" name="delete_id" id="delete_id">
      <div class="popup-actions">
        <button type="submit" name="delete_room" class="btn-save" style="background:#ef4444;">Delete</button>
        <button type="button" class="btn-close" onclick="document.getElementById('popupDelete').classList.remove('show')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editRoom(id, floor, roomNo, roomName, rowNum, tableNum) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_floor').value = floor;
  document.getElementById('edit_room_no').value = roomNo;
  document.getElementById('edit_room_name').value = roomName;
  document.getElementById('edit_row_number').value = rowNum;
  document.getElementById('edit_table_number').value = tableNum;
  document.getElementById('popupEdit').classList.add('show');
}
function deleteRoom(id, name) {
  document.getElementById('delete_id').value = id;
  document.getElementById('delete_room_name').textContent = name;
  document.getElementById('popupDelete').classList.add('show');
}
function printReport() {
  document.getElementById('printArea').style.display = 'block';
  window.print();
  document.getElementById('printArea').style.display = 'none';
}
</script>

<?php include 'footer.php'; ?>