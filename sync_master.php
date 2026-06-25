<?php
// ============================================================
// sync_master.php
// hardware_transaction → hardware_master sync
// Single PC ya Bulk dono support karta hai
// ============================================================
include 'db.php';

header('Content-Type: application/json');

$mode = $_POST['mode'] ?? 'bulk'; // 'bulk' ya 'single'
$id   = intval($_POST['id'] ?? 0);

$fields = "ip_address,computer_name,brand_name,model,os_name,os_version,
           ram_total,ram_free,cpu_name,cpu_manufacturer,cpu_serial_number,
           cpu_speed,cpu_cores,logical_processors,architecture,
           bios_vendor,bios_version,bios_date,
           motherboard_manufacturer,motherboard_serial,
           cpu,motherboard,hard_disk,domain_name,logged_user,boot_time,ram,
           category_manual,remarks,date_of_purchase,date_time";

$update = "ip_address=VALUES(ip_address),
           computer_name=VALUES(computer_name),
           brand_name=VALUES(brand_name),
           model=VALUES(model),
           os_name=VALUES(os_name),
           os_version=VALUES(os_version),
           ram_total=VALUES(ram_total),
           ram_free=VALUES(ram_free),
           cpu_name=VALUES(cpu_name),
           cpu_manufacturer=VALUES(cpu_manufacturer),
           cpu_serial_number=VALUES(cpu_serial_number),
           cpu_speed=VALUES(cpu_speed),
           cpu_cores=VALUES(cpu_cores),
           logical_processors=VALUES(logical_processors),
           architecture=VALUES(architecture),
           bios_vendor=VALUES(bios_vendor),
           bios_version=VALUES(bios_version),
           bios_date=VALUES(bios_date),
           motherboard_manufacturer=VALUES(motherboard_manufacturer),
           cpu=VALUES(cpu),
           motherboard=VALUES(motherboard),
           hard_disk=VALUES(hard_disk),
           domain_name=VALUES(domain_name),
           logged_user=VALUES(logged_user),
           boot_time=VALUES(boot_time),
           ram=VALUES(ram),
           date_time=VALUES(date_time)";
           // Note: category_manual, remarks, date_of_purchase update nahi honge
           // taake manual entries preserve hon

if ($mode === 'single' && $id > 0) {
    // ── Single PC sync ──
    $sql = "INSERT INTO hardware_master ($fields)
            SELECT $fields FROM hardware_transaction
            WHERE id = $id
              AND motherboard_serial IS NOT NULL
              AND motherboard_serial != ''
            ON DUPLICATE KEY UPDATE $update";

    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        echo json_encode(['ok'=>true, 'mode'=>'single', 'affected'=>$affected, 'msg'=>'Master updated!']);
    } else {
        echo json_encode(['ok'=>false, 'msg'=>$conn->error]);
    }

} else {
    // ── Bulk sync — har PC ka latest record master mein bhejo ──
    $sql = "INSERT INTO hardware_master ($fields)
            SELECT $fields
            FROM hardware_transaction t1
            WHERE t1.id = (
                SELECT MAX(t2.id)
                FROM hardware_transaction t2
                WHERE t2.motherboard_serial = t1.motherboard_serial
            )
            AND t1.motherboard_serial IS NOT NULL
            AND t1.motherboard_serial != ''
            ON DUPLICATE KEY UPDATE $update";

    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        echo json_encode(['ok'=>true, 'mode'=>'bulk', 'affected'=>$affected, 'msg'=>$affected.' PCs master mein sync ho gaye!']);
    } else {
        echo json_encode(['ok'=>false, 'msg'=>$conn->error]);
    }
}
?>