<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

$conn->query("
    UPDATE active_users 
    SET status='offline'
    WHERE user_id='$user_id'
");

session_destroy();

header("Location: login.php");
exit;
?>