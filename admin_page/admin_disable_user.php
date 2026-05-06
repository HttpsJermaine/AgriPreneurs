<?php
session_start();
require_once "../db_connection.php";

// Only admin can access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET status='disabled' WHERE id=?");
$stmt->bind_param("i", $userId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
?>
