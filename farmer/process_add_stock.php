<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  header("Location: ../login.php?error=" . urlencode("Access denied."));
  exit;
}

$farmerId = (int)$_SESSION['user_id'];
$productRequestId = (int)($_POST['product_request_id'] ?? 0);
$qty = (int)($_POST['quantity'] ?? 0);
$unit = 'sack';

$opts = $_POST['fulfillment_options'] ?? [];
$opts = array_values(array_intersect($opts, ['pickup','delivery']));
$fulfillment = implode(',', $opts);

if ($productRequestId <= 0 || $qty <= 0) {
  header("Location: stocks.php?error=" . urlencode("Invalid input."));
  exit;
}

$stmt = $conn->prepare("
  SELECT rice_variety, price_per_sack, product_image
  FROM product_requests
  WHERE id = ? AND user_id = ? AND status = 'active'
  LIMIT 1
");
$stmt->bind_param("ii", $productRequestId, $farmerId);
$stmt->execute();
$res = $stmt->get_result();
$pr = $res->fetch_assoc();
$stmt->close();

if (!$pr) {
  header("Location: stocks.php?error=" . urlencode("Approved product not found."));
  exit;
}

$productName = $pr['rice_variety'];
$price = (float)$pr['price_per_sack'];
$image = $pr['product_image'] ?? null;

// Check if product already exists
$check = $conn->prepare("
    SELECT id 
    FROM farmer_products 
    WHERE farmer_id = ? 
      AND product_name = ? 
      AND unit = ?
    LIMIT 1
");
$check->bind_param("iss", $farmerId, $productName, $unit);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // Product already exists
    header("Location: stocks.php?error=" . urlencode("This product is already added to your stocks. You can add stocks by clicking the + sign. "));
    exit;
}
$check->close();

$ins = $conn->prepare("
  INSERT INTO farmer_products (farmer_id, product_name, price, unit, quantity, image, fulfillment_options, created_at)
  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$ins->bind_param("isdssss", $farmerId, $productName, $price, $unit, $qty, $image, $fulfillment);
$ok = $ins->execute();
$ins->close();

header("Location: stocks.php?" . ($ok ? "success=" : "error=") . urlencode($ok ? "Stock added." : "Failed to add stock."));
exit;
