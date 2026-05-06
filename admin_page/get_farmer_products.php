<?php
session_start();
require_once "../db_connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$farmerId = (int)($_GET['farmer_id'] ?? 0);
if ($farmerId <= 0) {
  echo json_encode(['success' => false, 'error' => 'Invalid farmer_id']);
  exit;
}

// Only change: Added "AND status = 'active'" to filter only active products
$stmt = $conn->prepare("
  SELECT id, rice_variety, price_per_sack, product_image, status, rejection_reason, created_at
  FROM product_requests
  WHERE user_id = ? AND status = 'active'
  ORDER BY created_at DESC
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$res = $stmt->get_result();

$products = [];
while ($row = $res->fetch_assoc()) {
  $products[] = [
    'id' => (int)$row['id'],
    'rice_variety' => $row['rice_variety'],
    'price_per_sack' => (float)$row['price_per_sack'],
    'product_image' => $row['product_image'] ?? null,
    'status' => $row['status'],
    'rejection_reason' => $row['rejection_reason'] ?? null,
    'created_at' => $row['created_at'],
  ];
}

$stmt->close();
echo json_encode(['success' => true, 'products' => $products]);