<?php
include 'auth.php';
include 'db.php';

$fIP     = trim($_GET['ip']        ?? '');
$fPC     = trim($_GET['pc']        ?? '');
$fDate   = trim($_GET['date']      ?? '');
$fFloor  = trim($_GET['floor']     ?? '');
$fRoom   = trim($_GET['room_name'] ?? '');
$fStatus = trim($_GET['status']    ?? '');

$sql="SELECT *,CASE WHEN ip_address IS NULL OR ip_address='' OR pc_name IS NULL OR pc_name='' OR motherboard_serial IS NULL OR motherboard_serial='' THEN 'Unlocated' ELSE 'Located' END AS loc_status FROM pc_location WHERE 1=1";
if($fIP)    $sql.=" AND ip_address LIKE '%".$conn->real_escape_string($fIP)."%'";
if($fPC)    $sql.=" AND pc_name LIKE '%".$conn->real_escape_string($fPC)."%'";
if($fDate)  $sql.=" AND DATE(last_updated)='".$conn->real_escape_string($fDate)."'";
if($fFloor) $sql.=" AND floor_no='".$conn->real_escape_string($fFloor)."'";
if($fRoom)  $sql.=" AND room_name LIKE '%".$conn->real_escape_string($fRoom)."%'";
if($fStatus==='Located')   $sql.=" AND ip_address IS NOT NULL AND ip_address!='' AND pc_name IS NOT NULL AND pc_name!='' AND motherboard_serial IS NOT NULL AND motherboard_serial!=''";
if($fStatus==='Unlocated') $sql.=" AND (ip_address IS NULL OR ip_address='' OR pc_name IS NULL OR pc_name='' OR motherboard_serial IS NULL OR motherboard_serial='')";
$sql.=" ORDER BY floor_no ASC, room_name ASC, id ASC";

$res  = $conn->query($sql);
$rows = [];
while($r=$res->fetch_assoc()) $rows[]=$r;

// ─── Label for filename & heading ────────────────────────────────────────────
$statusLabel = $fStatus ?: 'All';
$floorLabel  = $fFloor  ?: 'All-Floors';
$filename    = 'PC_Location_'.$statusLabel.'_'.$floorLabel.'_'.date('Y-m-d').'.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$out = fopen('php://output','w');

// ─── Report heading rows ──────────────────────────────────────────────────────
fputcsv($out, ['NCR-CET IT Lab — PC Location Report']);
fputcsv($out, ['Generated:', date('d M Y, h:i A')]);
fputcsv($out, ['Status Filter:', $statusLabel]);
fputcsv($out, ['Floor Filter:',  $floorLabel]);
fputcsv($out, ['Total Records:', count($rows)]);
fputcsv($out, []);

// ─── Column headers ───────────────────────────────────────────────────────────
fputcsv($out, ['ID','IP Address','PC Name','MB Serial','Floor','Room No','Room Name','Row','Table','Last Updated','Status']);

// ─── Data rows ────────────────────────────────────────────────────────────────
foreach($rows as $r){
    fputcsv($out,[
        $r['id'],
        $r['ip_address']        ?? '',
        $r['pc_name']           ?? '',
        $r['motherboard_serial']?? '',
        $r['floor_no']          ?? '',
        $r['room_no']           ?? '',
        $r['room_name']         ?? '',
        $r['row_no']            ?? '',
        $r['table_no']          ?? '',
        $r['last_updated']      ?? $r['created_at'] ?? '',
        $r['loc_status'],
    ]);
}
fclose($out);
exit;