<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

if ($product_id <= 0 || $qty <= 0) {
  header("Location: products.php?err=invalid");
  exit;
}

/* Get product price + stock */
$stmt = $conn->prepare("SELECT price, quantity FROM farmer_products WHERE id=? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) { header("Location: products.php?err=notfound"); exit; }

$stock = (int)$prod['quantity'];
$price = (float)$prod['price'];

if ($qty > $stock) {
  header("Location: products.php?err=stock");
  exit;
}

/* Ensure cart exists */
$stmt = $conn->prepare("SELECT id FROM carts WHERE buyer_id=? LIMIT 1");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cartRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cart_id = (int)($cartRow['id'] ?? 0);

if ($cart_id <= 0) {
  $stmt = $conn->prepare("INSERT INTO carts (buyer_id) VALUES (?)");
  $stmt->bind_param("i", $buyer_id);
  $stmt->execute();
  $cart_id = (int)$stmt->insert_id;
  $stmt->close();
}

/* If item exists in cart, update qty; else insert */
$stmt = $conn->prepare("SELECT id, qty FROM cart_items WHERE cart_id=? AND product_id=? LIMIT 1");
$stmt->bind_param("ii", $cart_id, $product_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($item) {
  $newQty = (int)$item['qty'] + $qty;
  if ($newQty > $stock) $newQty = $stock;

  $stmt = $conn->prepare("UPDATE cart_items SET qty=? WHERE id=?");
  $stmt->bind_param("ii", $newQty, $item['id']);
  $stmt->execute();
  $stmt->close();
} else {
  $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, qty, price_at_add) VALUES (?,?,?,?)");
  $stmt->bind_param("iiid", $cart_id, $product_id, $qty, $price);
  $stmt->execute();
  $stmt->close();
}

header("Location: cart.php?added=1");
exit;