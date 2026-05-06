<?php
session_start();
require_once "../db_connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($productId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
  echo json_encode(['success' => false, 'error' => 'Invalid request']);
  exit;
}

if ($action === 'approve') {
  $stmt = $conn->prepare("
    UPDATE product_requests
    SET status='active', rejection_reason=NULL, updated_at=CURRENT_TIMESTAMP
    WHERE id=?
  ");
  $stmt->bind_param("i", $productId);
} else {
  $reason = trim($_POST['reason'] ?? '');
  if ($reason === '') {
    echo json_encode(['success' => false, 'error' => 'Rejection reason required']);
    exit;
  }
  $stmt = $conn->prepare("
    UPDATE product_requests
    SET status='rejected', rejection_reason=?, updated_at=CURRENT_TIMESTAMP
    WHERE id=?
  ");
  $stmt->bind_param("si", $reason, $productId);
}

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => $conn->error]);
}
$stmt->close();
