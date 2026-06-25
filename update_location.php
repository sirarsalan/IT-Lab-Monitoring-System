<?php
include 'db.php';

$pc = $_GET['pc'] ?? '';
if (!$pc) die("PC not found");

/* CURRENT DATA */
$current = $conn->query("
    SELECT * FROM pc_location WHERE pc_name='$pc'
")->fetch_assoc();

/* UPDATE */
if (isset($_POST['update'])) {

    $room = $_POST['room'];
    $row_no = $_POST['row_no'];
    $table_no = $_POST['table_no'];
    $reason = $_POST['reason'];

    $old = $current;

    /* UPDATE MAIN TABLE */
    $conn->query("
        UPDATE pc_location SET
        room='$room',
        row_no='$row_no',
        table_no='$table_no'
        WHERE pc_name='$pc'
    ");

    /* GET HARDWARE */
    $hw = $conn->query("
        SELECT ip_address, motherboard_serial
        FROM hardware_status
        WHERE computer_name='$pc'
        ORDER BY id DESC
        LIMIT 1
    ")->fetch_assoc();

    $ip = $hw['ip_address'] ?? '';
    $mb = $hw['motherboard_serial'] ?? '';

    /* INSERT HISTORY */
    $conn->query("
        INSERT INTO location_history (
            pc_name, ip_address, motherboard_serial,
            old_room, new_room,
            old_row, new_row,
            old_table, new_table,
            changed_at
        ) VALUES (
            '$pc', '$ip', '$mb',
            '{$old['room']}', '$room',
            '{$old['row_no']}', '$row_no',
            '{$old['table_no']}', '$table_no',
            NOW()
        )
    ");

    echo "<script>
        alert('Location Updated Successfully');
        window.location='pclocation.php';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update PC Location</title>
<style>
body{font-family:Arial;background:#f4f6f9;}
.container{width:420px;margin:60px auto;}
.card{background:#fff;padding:25px;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
input{width:100%;padding:10px;margin-top:5px;margin-bottom:10px;}
.btn{width:100%;padding:12px;background:#007bff;color:white;border:none;}
</style>
</head>
<body>

<div class="container">
<div class="card">

<h2>📍 Update Location</h2>

<form method="POST">

<label>PC</label>
<input value="<?= $pc ?>" disabled>

<label>Room</label>
<input name="room" value="<?= $current['room'] ?? '' ?>">

<label>Row</label>
<input name="row_no" value="<?= $current['row_no'] ?? '' ?>">

<label>Table</label>
<input name="table_no" value="<?= $current['table_no'] ?? '' ?>">

<label>Reason</label>
<input name="reason" required>

<button class="btn" name="update">Update</button>

</form>

</div>
</div>

</body>
</html>