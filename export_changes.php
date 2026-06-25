<?php
include 'auth.php';
include 'db.php';

$res=$conn->query("SELECT * FROM location_changes ORDER BY id DESC");
$rows=[];
while($r=$res->fetch_assoc()) $rows[]=$r;

$filename='PC_Location_Changes_'.date('Y-m-d').'.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$out=fopen('php://output','w');

fputcsv($out,['NCR-CET IT Lab — PC Location Transfer Report']);
fputcsv($out,['Generated:',date('d M Y, h:i A')]);
fputcsv($out,['Total Records:',count($rows)]);
fputcsv($out,[]);
fputcsv($out,['Date','PC Name','IP Address','MB Serial','From Floor','From Room','From Row','From Table','To Floor','To Room','To Row','To Table','Reason','Order By','Changed By']);

foreach($rows as $r){
    fputcsv($out,[
        $r['change_date']??$r['created_at']??'',
        $r['pc_name']??'',
        $r['ip_address']??'',
        $r['motherboard_serial']??'',
        $r['from_floor']??'',
        $r['from_room_name']??'',
        $r['from_row_no']??'',
        $r['from_table_no']??'',
        $r['to_floor']??'',
        $r['to_room_name']??'',
        $r['to_row_no']??'',
        $r['to_table_no']??'',
        $r['reason']??'',
        $r['order_by']??'',
        $r['changed_by']??'',
    ]);
}
fclose($out); exit;