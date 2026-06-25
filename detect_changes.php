<?php
// ============================================================
// detect_changes.php
// Yeh file sync ke waqt call hoti hai.
// Nayi hardware_status entry aur purani master_pc_data compare
// karke inventory_changes mein differences save karti hai.
// ============================================================

function detectAndLogChanges($conn, $new) {

    $pc = $conn->real_escape_string($new['computer_name'] ?? '');
    if (!$pc) return;

    // ----- Purana record fetch karo -----
    $res = $conn->query("
        SELECT * FROM master_pc_data
        WHERE pc_name = '$pc'
        ORDER BY last_seen DESC
        LIMIT 1
    ");

    if (!$res || $res->num_rows === 0) return; // Naya PC — no comparison yet
    $old = $res->fetch_assoc();

    // ----- Jo fields check karni hain -----
    // [change_type, old_field_key, new_field_key, label]
    $checks = [
        ['ram',         'ram_total',          'ram_total',          'RAM Total'],
        ['ip',          'ip_address',         'ip_address',         'IP Address'],
        ['cpu',         'cpu_name',           'cpu_name',           'CPU Name'],
        ['cpu',         'cpu_cores',          'cpu_cores',          'CPU Cores'],
        ['cpu',         'cpu_speed',          'cpu_speed',          'CPU Speed'],
        ['os',          'os_name',            'os_name',            'OS Name'],
        ['os',          'os_version',         'os_version',         'OS Version'],
        ['disk',        'hard_disk',          'hard_disk',          'Hard Disk'],
        ['arch',        'architecture',       'architecture',       'Architecture'],
        ['mb_serial',   'motherboard_serial', 'motherboard_serial', 'MB Serial'],
        ['logged_user', 'logged_user',        'logged_user',        'Logged User'],
    ];

    foreach ($checks as $c) {
        [$type, $oldKey, $newKey, $label] = $c;

        $oldVal = trim($old[$oldKey] ?? '');
        $newVal = trim($new[$newKey] ?? '');

        // Empty values ignore karo
        if ($oldVal === '' || $newVal === '') continue;

        // Same hai? Skip
        if ($oldVal === $newVal) continue;

        // ----- Duplicate check: last 24 hours mein same change already log hua? -----
        $ov = $conn->real_escape_string($oldVal);
        $nv = $conn->real_escape_string($newVal);
        $fn = $conn->real_escape_string($label);

        $dup = $conn->query("
            SELECT id FROM inventory_changes
            WHERE pc_name    = '$pc'
              AND field_name = '$fn'
              AND old_value  = '$ov'
              AND new_value  = '$nv'
              AND detected_at >= NOW() - INTERVAL 24 HOUR
            LIMIT 1
        ");
        if ($dup && $dup->num_rows > 0) continue;

        $ip = $conn->real_escape_string($new['ip_address'] ?? '');
        $mb = $conn->real_escape_string($new['motherboard_serial'] ?? '');
        $ct = $conn->real_escape_string($type);

        $conn->query("
            INSERT INTO inventory_changes
                (pc_name, ip_address, motherboard_serial, change_type, field_name, old_value, new_value)
            VALUES
                ('$pc', '$ip', '$mb', '$ct', '$fn', '$ov', '$nv')
        ");
    }

    // ----- Location change check (pc_location table se) -----
    $locRes = $conn->query("
        SELECT * FROM pc_location
        WHERE pc_name = '$pc'
        ORDER BY id DESC LIMIT 1
    ");

    if ($locRes && $locRes->num_rows > 0) {
        $loc = $locRes->fetch_assoc();

        $newIP = $conn->real_escape_string($new['ip_address'] ?? '');
        $oldIP = trim($loc['ip_address'] ?? '');

        if ($oldIP && $newIP && $oldIP !== $newIP) {
            // IP changed → location moved ho sakti hai
            $fn = 'IP Address (Location)';
            $ov = $conn->real_escape_string($oldIP);

            // Duplicate check
            $dup2 = $conn->query("
                SELECT id FROM inventory_changes
                WHERE pc_name    = '$pc'
                  AND field_name = '$fn'
                  AND old_value  = '$ov'
                  AND new_value  = '$newIP'
                  AND detected_at >= NOW() - INTERVAL 24 HOUR
                LIMIT 1
            ");
            if (!$dup2 || $dup2->num_rows === 0) {
                $conn->query("
                    INSERT IGNORE INTO inventory_changes
                        (pc_name, ip_address, motherboard_serial, change_type, field_name, old_value, new_value)
                    VALUES ('$pc', '$newIP', '', 'location', '$fn', '$ov', '$newIP')
                ");
            }
        }
    }
}
?>
