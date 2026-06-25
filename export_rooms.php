<?php
include 'auth.php';
include 'db.php';

// ── Fetch data ───────────────────────────────────────────────────────────────
$floors = ['Ground','First','Second','Third'];
$roomsByFloor = [];
$allRooms = [];
foreach ($floors as $fl) {
    $res = $conn->query("
        SELECT rm.*,
               COUNT(CASE WHEN pl.pc_name IS NOT NULL AND pl.pc_name!='' AND pl.motherboard_serial IS NOT NULL AND pl.motherboard_serial!='' THEN 1 END) AS pc_count
        FROM room_management rm
        LEFT JOIN pc_location pl ON pl.floor_no = rm.floor_no AND pl.room_no = rm.room_no
        WHERE rm.floor_no = '" . $conn->real_escape_string($fl) . "'
        GROUP BY rm.id ORDER BY rm.id ASC
    ");
    $roomsByFloor[$fl] = [];
    while ($r = $res->fetch_assoc()) {
        $roomsByFloor[$fl][] = $r;
        $allRooms[] = $r;
    }
}

// ── Floor totals ─────────────────────────────────────────────────────────────
$floorTotals = [];
foreach ($floors as $fl) {
    $rooms=count($roomsByFloor[$fl]); $rows=$tables=$pcs=0;
    foreach ($roomsByFloor[$fl] as $r) {
        $rows   += $r['row_number'];
        $tables += $r['table_number'];
        $pcs    += $r['pc_count'];
    }
    $floorTotals[$fl] = ['rooms'=>$rooms,'rows'=>$rows,'tables'=>$tables,'pcs'=>$pcs];
}
$grand = ['rooms'=>0,'rows'=>0,'tables'=>0,'pcs'=>0];
foreach ($floorTotals as $t) {
    $grand['rooms']+=$t['rooms']; $grand['rows']+=$t['rows'];
    $grand['tables']+=$t['tables']; $grand['pcs']+=$t['pcs'];
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=room_management_report.xls");
?>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 11pt; }

  /* ── Title ── */
  .title-row td {
    background-color: #1E1B4B;
    color: #FFFFFF;
    font-size: 16pt;
    font-weight: bold;
    text-align: center;
    padding: 12px 16px;
  }
  .subtitle-row td {
    background-color: #1E1B4B;
    color: #A5B4FC;
    font-size: 9pt;
    text-align: center;
    padding: 4px 16px 8px;
  }

  /* ── Stat cards ── */
  .stat-label {
    font-size: 9pt; font-weight: bold; text-align: center; padding: 6px 10px 2px;
  }
  .stat-value {
    font-size: 20pt; font-weight: bold; text-align: center; padding: 4px 10px;
  }
  .stat-sub {
    font-size: 8pt; text-align: center; padding: 2px 10px 6px; color: #64748B;
  }
  .card-total  { background-color:#EDE9FE; color:#6366F1; }
  .card-ground { background-color:#FEF3C7; color:#D97706; }
  .card-first  { background-color:#D1FAE5; color:#059669; }
  .card-second { background-color:#EDE9FE; color:#7C3AED; }
  .card-third  { background-color:#CCFBF1; color:#0D9488; }

  /* ── Section headers ── */
  .section-ground td { background-color:#D97706; color:#FFFFFF; font-weight:bold; font-size:12pt; padding:8px 10px; }
  .section-first  td { background-color:#059669; color:#FFFFFF; font-weight:bold; font-size:12pt; padding:8px 10px; }
  .section-second td { background-color:#6366F1; color:#FFFFFF; font-weight:bold; font-size:12pt; padding:8px 10px; }
  .section-third  td { background-color:#0D9488; color:#FFFFFF; font-weight:bold; font-size:12pt; padding:8px 10px; }

  /* ── Table headers ── */
  .thead-ground th { background-color:#D97706; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #B45309; }
  .thead-first  th { background-color:#059669; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #047857; }
  .thead-second th { background-color:#6366F1; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #4338CA; }
  .thead-third  th { background-color:#0D9488; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #0F766E; }

  /* ── Data rows ── */
  .row-even { background-color:#F8FAFC; }
  .row-odd  { background-color:#FFFFFF; }
  .tbl td { padding:6px 10px; border:1px solid #E2E8F0; font-size:10pt; vertical-align:middle; }
  .tbl .col-id    { color:#94A3B8; text-align:center; }
  .tbl .col-rno   { text-align:center; font-weight:bold; color:#6366F1; }
  .tbl .col-name  { font-weight:bold; }
  .tbl .col-num   { text-align:center; }
  .tbl .col-pcs-yes { text-align:center; font-weight:bold; color:#059669; }
  .tbl .col-pcs-no  { text-align:center; color:#94A3B8; }

  /* ── Total rows ── */
  .total-ground td { background-color:#D97706; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #B45309; }
  .total-first  td { background-color:#059669; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #047857; }
  .total-second td { background-color:#6366F1; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #4338CA; }
  .total-third  td { background-color:#0D9488; color:#FFFFFF; font-weight:bold; text-align:center; padding:7px 10px; border:1px solid #0F766E; }

  /* ── Summary table ── */
  .sum-head th { background-color:#1E1B4B; color:#FFFFFF; font-weight:bold; text-align:center; padding:8px 12px; border:1px solid #334155; }
  .sum-even td { background-color:#F8FAFC; padding:7px 12px; border:1px solid #E2E8F0; font-size:10pt; }
  .sum-odd  td { background-color:#FFFFFF;  padding:7px 12px; border:1px solid #E2E8F0; font-size:10pt; }
  .sum-grand td { background-color:#1E1B4B; color:#FFFFFF; font-weight:bold; text-align:center; padding:8px 12px; border:1px solid #334155; }

  .spacer td { height:14px; }
  .section-label td { background-color:#F1F5F9; color:#1E1B4B; font-weight:bold; font-size:11pt; padding:6px 10px; border-left:4px solid #6366F1; }
</style>
</head>
<body>

<table width="700" cellspacing="0" cellpadding="0">

  <!-- ── Title ── -->
  <tr class="title-row"><td colspan="6">NCR-CET IT Lab &mdash; Room Management Report</td></tr>
  <tr class="subtitle-row"><td colspan="6">Generated: <?= date('d F Y, h:i A') ?></td></tr>
  <tr class="spacer"><td colspan="6"></td></tr>

  <!-- ── Stat Cards ── -->
  <tr>
    <td class="stat-label card-total">TOTAL ROOMS</td>
    <td class="stat-label card-ground">GROUND FLOOR</td>
    <td class="stat-label card-first">FIRST FLOOR</td>
    <td class="stat-label card-second">SECOND FLOOR</td>
    <td class="stat-label card-third">THIRD FLOOR</td>
  </tr>
  <tr>
    <td class="stat-value card-total"><?= $grand['rooms'] ?></td>
    <td class="stat-value card-ground"><?= $floorTotals['Ground']['rooms'] ?></td>
    <td class="stat-value card-first"><?= $floorTotals['First']['rooms'] ?></td>
    <td class="stat-value card-second"><?= $floorTotals['Second']['rooms'] ?></td>
    <td class="stat-value card-third"><?= $floorTotals['Third']['rooms'] ?></td>
  </tr>
  <tr>
    <td class="stat-sub card-total">in room_management</td>
    <td class="stat-sub card-ground">rooms</td>
    <td class="stat-sub card-first">rooms</td>
    <td class="stat-sub card-second">rooms</td>
    <td class="stat-sub card-third">rooms</td>
  </tr>

  <tr class="spacer"><td colspan="6"></td></tr>

  <!-- ── Floor Summary Table ── -->
  <tr class="section-label"><td colspan="6">Floor Summary</td></tr>
  <tr class="spacer"><td colspan="6"></td></tr>

</table>

<table width="700" border="0" cellspacing="0" cellpadding="0">
  <tr class="sum-head">
    <th width="160">Floor</th>
    <th width="110">Total Rooms</th>
    <th width="110">Total Rows</th>
    <th width="120">Total Tables</th>
    <th width="120">PCs Assigned</th>
  </tr>
  <?php foreach ($floors as $idx => $fl): $t = $floorTotals[$fl]; $cls = $idx%2==0?'sum-even':'sum-odd'; ?>
  <tr class="<?= $cls ?>">
    <td style="font-weight:bold;color:<?= ['Ground'=>'#D97706','First'=>'#059669','Second'=>'#7C3AED','Third'=>'#0D9488'][$fl] ?>"><?= $fl ?> Floor</td>
    <td style="text-align:center"><?= $t['rooms'] ?></td>
    <td style="text-align:center"><?= $t['rows'] ?></td>
    <td style="text-align:center"><?= $t['tables'] ?></td>
    <td style="text-align:center;font-weight:bold;color:<?= $t['pcs']>0?'#059669':'#94A3B8' ?>"><?= $t['pcs'] > 0 ? $t['pcs'].' PCs' : '—' ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="sum-grand">
    <td>GRAND TOTAL</td>
    <td><?= $grand['rooms'] ?></td>
    <td><?= $grand['rows'] ?></td>
    <td><?= $grand['tables'] ?></td>
    <td><?= $grand['pcs'] ?> PCs</td>
  </tr>
</table>

<?php
// ── Per-floor detail tables ──────────────────────────────────────────────────
$floorMeta = [
  'Ground' => ['section'=>'section-ground','thead'=>'thead-ground','total'=>'total-ground'],
  'First'  => ['section'=>'section-first', 'thead'=>'thead-first', 'total'=>'total-first'],
  'Second' => ['section'=>'section-second','thead'=>'thead-second','total'=>'total-second'],
  'Third'  => ['section'=>'section-third', 'thead'=>'thead-third', 'total'=>'total-third'],
];
foreach ($floors as $fl):
  if (empty($roomsByFloor[$fl])) continue;
  $m = $floorMeta[$fl];
  $t = $floorTotals[$fl];
?>
<br>
<table width="700" border="0" cellspacing="0" cellpadding="0">
  <tr class="<?= $m['section'] ?>"><td colspan="6"><?= $fl ?> Floor &mdash; <?= count($roomsByFloor[$fl]) ?> Rooms</td></tr>
</table>
<table class="tbl" width="700" border="0" cellspacing="0" cellpadding="0">
  <tr class="<?= $m['thead'] ?>">
    <th width="40">ID</th>
    <th width="80">Room No</th>
    <th width="200">Room Name</th>
    <th width="100">Rows</th>
    <th width="100">Tables</th>
    <th width="130">PCs Assigned</th>
  </tr>
  <?php foreach ($roomsByFloor[$fl] as $idx => $r): $cls=$idx%2==0?'row-even':'row-odd'; ?>
  <tr class="<?= $cls ?>">
    <td class="col-id"><?= $r['id'] ?></td>
    <td class="col-rno"><?= htmlspecialchars($r['room_no']) ?></td>
    <td class="col-name"><?= htmlspecialchars($r['room_name']) ?></td>
    <td class="col-num"><?= $r['row_number'] ?></td>
    <td class="col-num"><?= $r['table_number'] ?></td>
    <td class="<?= $r['pc_count']>0?'col-pcs-yes':'col-pcs-no' ?>"><?= $r['pc_count']>0 ? $r['pc_count'].' PCs' : '0 PCs' ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="<?= $m['total'] ?>">
    <td colspan="3">TOTAL</td>
    <td><?= $t['rows'] ?></td>
    <td><?= $t['tables'] ?></td>
    <td><?= $t['pcs'] ?> PCs</td>
  </tr>
</table>
<?php endforeach; ?>

<br><br>
<table width="700"><tr><td style="color:#94A3B8;font-size:8pt;text-align:center;border-top:1px solid #E2E8F0;padding-top:8px;">
  NCR-CET IT Lab Network Dashboard &nbsp;|&nbsp; Room Management Report &nbsp;|&nbsp; <?= date('d M Y') ?>
</td></tr></table>

</body>
</html>