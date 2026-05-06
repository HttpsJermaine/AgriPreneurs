<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

if ($product_id <= 0 || $qty <= 0) {
  header("Location: products.php?err=invalid");
  exit;
}

// Validate stock
$stmt = $conn->prepare("SELECT quantity FROM farmer_products WHERE id=? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  header("Location: products.php?err=notfound");
  exit;
}

if ($qty > (int)$row['quantity']) {
  header("Location: products.php?err=stock");
  exit;
}

// Save Buy Now session
$_SESSION['buy_now'] = [
  'product_id' => $product_id,
  'qty' => $qty
];

header("Location: checkout.php?mode=buynow");
exit;