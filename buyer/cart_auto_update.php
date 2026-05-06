<?php
session_start();
require_once "../db_connection.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  echo json_encode(["success" => false, "error" => "Access denied"]);
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  echo json_encode(["success" => false, "error" => "Invalid request"]);
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
$newQty = (int)($_POST['qty'] ?? 0);

if ($cart_item_id <= 0) {
  echo json_encode(["success" => false, "error" => "Invalid item"]);
  exit;
}

if ($newQty < 1) $newQty = 1;

// 1) Get product_id of this cart item and ensure it's owned by this buyer
$stmt = $conn->prepare("
  SELECT ci.product_id
  FROM cart_items ci
  JOIN carts c ON c.id = ci.cart_id
  WHERE ci.id = ? AND c.buyer_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $cart_item_id, $buyer_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
  echo json_encode(["success" => false, "error" => "Cart item not found"]);
  exit;
}

$product_id = (int)$row["product_id"];

// 2) Check stock from farmer_products
$stmt = $conn->prepare("SELECT quantity FROM farmer_products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
$p = $res->fetch_assoc();
$stmt->close();

if (!$p) {
  echo json_encode(["success" => false, "error" => "Product not found"]);
  exit;
}

$stock = (int)$p["quantity"];
if ($stock <= 0) {
  echo json_encode(["success" => false, "error" => "Out of stock", "qty" => 1, "stock" => 0]);
  exit;
}

// clamp qty to stock
if ($newQty > $stock) $newQty = $stock;

// 3) Update qty
$stmt = $conn->prepare("
  UPDATE cart_items ci
  JOIN carts c ON c.id = ci.cart_id
  SET ci.qty = ?
  WHERE ci.id = ? AND c.buyer_id = ?
");
$stmt->bind_param("iii", $newQty, $cart_item_id, $buyer_id);
$stmt->execute();
$stmt->close();

$conn->close();

echo json_encode(["success" => true, "qty" => $newQty, "stock" => $stock]);
exit;