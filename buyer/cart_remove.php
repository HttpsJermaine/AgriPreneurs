<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: cart.php?error=" . urlencode("Access denied"));
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: cart.php?error=" . urlencode("Invalid request"));
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$cart_item_id = (int)($_POST['cart_item_id'] ?? 0);

if ($cart_item_id <= 0) {
  header("Location: cart.php?error=" . urlencode("Invalid item"));
  exit;
}

$stmt = $conn->prepare("
  DELETE ci
  FROM cart_items ci
  JOIN carts c ON c.id = ci.cart_id
  WHERE ci.id = ? AND c.buyer_id = ?
");
$stmt->bind_param("ii", $cart_item_id, $buyer_id);
$stmt->execute();
$stmt->close();

$conn->close();

header("Location: cart.php?success=" . urlencode("Item removed."));
exit;