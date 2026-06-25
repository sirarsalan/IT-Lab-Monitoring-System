<?php
// scan_runner.php — AJAX: scan one IP, sirf CONNECTED save karo DB mein
header('Content-Type: application/json');
include 'db.php';

$ip = $_GET['ip'] ?? '';
if(!preg_match('/^100\.2\.[0-9]+\.[0-9]+$/', $ip)){
    echo json_encode(['error'=>'Invalid IP']); exit;
}

// Ping — 1 packet, 300ms timeout
$ping_out = [];
exec("ping -n 1 -w 300 ".escapeshellarg($ip)." >nul 2>&1", $ping_out, $ret);
$status = ($ret === 0) ? 'CONNECTED' : 'NOT CONNECTED';

$computer_name = '';
if($status === 'CONNECTED'){
    $nb_out = [];
    exec("nbtstat -A ".escapeshellarg($ip)." 2>nul", $nb_out);
    foreach($nb_out as $line){
        $line = trim($line);
        if(stripos($line,'<00>') !== false && stripos($line,'GROUP') === false){
            $parts = preg_split('/\s+/', $line);
            if(!empty($parts[0]) && strlen($parts[0]) > 1){
                $computer_name = strtoupper(trim($parts[0]));
                break;
            }
        }
    }
    if(!$computer_name) $computer_name = 'DEVICE';

    // ── Sirf CONNECTED PCs save karo ──
    $conn->query("INSERT INTO lab_network_status (ip, status, computer_name, date_time)
        VALUES ('".$conn->real_escape_string($ip)."','CONNECTED',
                '".$conn->real_escape_string($computer_name)."', NOW())");
}
// NOT CONNECTED: DB mein kuch nahi jaata

echo json_encode([
    'ip'     => $ip,
    'status' => $status,
    'name'   => $computer_name ?: '—',
]);
exit;