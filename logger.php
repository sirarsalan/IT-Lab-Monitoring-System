<?php
function logAction($conn, $action){

    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $ip = $_SERVER['REMOTE_ADDR'];

    $conn->query("
        INSERT INTO user_history (user_id, username, action, ip_address)
        VALUES ('$user_id', '$username', '$action', '$ip')
    ");
}
?>