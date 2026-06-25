<?php
include 'auth.php';
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id              = intval($_POST['id'] ?? 0);
$category_manual = trim($_POST['category_manual'] ?? '');
$remarks         = trim($_POST['remarks'] ?? '');
$date_of_purchase = trim($_POST['date_of_purchase'] ?? '');

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// validate category
if ($category_manual && !in_array($category_manual, ['A', 'B', 'C'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid category']);
    exit;
}

$cat  = $category_manual ? "'".$conn->real_escape_string($category_manual)."'" : "NULL";
$rem  = "'".$conn->real_escape_string($remarks)."'";
$dop  = $date_of_purchase ? "'".$conn->real_escape_string($date_of_purchase)."'" : "NULL";

$sql = "UPDATE hardware_status SET
    category_manual  = $cat,
    remarks          = $rem,
    date_of_purchase = $dop
    WHERE id = $id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Saved']);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}