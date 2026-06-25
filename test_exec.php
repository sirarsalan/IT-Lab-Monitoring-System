<?php
$disabled = ini_get('disable_functions');
$exec_ok  = function_exists('exec') && (strpos($disabled, 'exec') === false);
$shell_ok = function_exists('shell_exec') && (strpos($disabled, 'shell_exec') === false);

echo "<h2>exec() Test</h2>";
echo "exec() available: "       . ($exec_ok  ? '<b style="color:green">YES</b>' : '<b style="color:red">NO</b>') . "<br>";
echo "shell_exec() available: " . ($shell_ok ? '<b style="color:green">YES</b>' : '<b style="color:red">NO</b>') . "<br><br>";

if($exec_ok){
    $out = [];
    exec("ping -n 1 -w 500 100.2.1.1", $out, $ret);
    echo "Ping test (100.2.1.1): " . ($ret==0
        ? '<b style="color:green">REACHABLE</b>'
        : '<b style="color:orange">NOT REACHABLE (but exec works)</b>') . "<br>";
    echo "<pre>".implode("\n",$out)."</pre>";
} else {
    echo "<b style='color:red'>exec() is disabled.</b><br>";
    echo "Fix: XAMPP > php.ini > find 'disable_functions' > remove 'exec' and 'shell_exec' > restart Apache.";
}

echo "<hr><h2>PHP Version</h2>";
echo phpversion();

echo "<hr><h2>DB Test</h2>";
$conn = new mysqli("localhost","root","","network_db");
if($conn->connect_error){
    echo "<b style='color:red'>DB Error: ".$conn->connect_error."</b>";
} else {
    echo "<b style='color:green'>DB Connected OK</b><br><br>";

    // Show all tables
    $tables = $conn->query("SHOW TABLES");
    echo "<b>Tables in network_db:</b><br>";
    while($t = $tables->fetch_array()) echo "— ".$t[0]."<br>";

    echo "<br><b>lab_network_status columns:</b><br>";
    $cols = $conn->query("DESCRIBE lab_network_status");
    if($cols) while($c=$cols->fetch_assoc()) echo "— ".$c['Field']." (".$c['Type'].")<br>";
    else echo "<span style='color:red'>Table not found</span><br>";

    echo "<br><b>pc_status_table columns:</b><br>";
    $cols2 = $conn->query("DESCRIBE pc_status_table");
    if($cols2) while($c=$cols2->fetch_assoc()) echo "— ".$c['Field']." (".$c['Type'].")<br>";
    else echo "<span style='color:red'>Table not found</span><br>";

    echo "<br><b>Sample status values in pc_status_table (last 5):</b><br>";
    $sv = $conn->query("SELECT id, pc_id, pc_name, ip_address, status, last_seen FROM pc_status_table ORDER BY id DESC LIMIT 5");
    if($sv) while($r=$sv->fetch_assoc()) echo "ID:".$r['id']." | ".$r['pc_name']." | IP:".$r['ip_address']." | Status:<b>".$r['status']."</b> | ".$r['last_seen']."<br>";

    echo "<br><b>Sample data in lab_network_status (last 5):</b><br>";
    $nv = $conn->query("SELECT * FROM lab_network_status ORDER BY id DESC LIMIT 5");
    if($nv) while($r=$nv->fetch_assoc()) echo implode(" | ",$r)."<br>";
    else echo "<span style='color:red'>Table not found or empty</span><br>";
}
?>