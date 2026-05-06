<?php
session_start();
require_once "../db_connection.php";

function back($msg) {
  header("Location: stocks.php?error=" . urlencode($msg));
  exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  back("Access denied.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  back("Invalid request.");
}

$farmerId  = (int)$_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$action    = $_POST['action'] ?? '';
$qty       = (int)($_POST['qty'] ?? 0);
$reason    = trim($_POST['reason'] ?? '');
$date      = $_POST['date'] ?? date('Y-m-d');

if ($productId <= 0 || $qty <= 0) back("Invalid product or quantity.");
if ($action !== 'increase' && $action !== 'decrease') back("Invalid action.");

// Reason validation for decrease
$allowedReasons = ["Sold", "Spoiled", "Personal Use"];
if ($action === "decrease") {
  if ($reason === "" || !in_array($reason, $allowedReasons, true)) {
    back("Please select a valid reason for deduction.");
  }
} else {
  // for increase, ignore reason
  $reason = "";
}

// Get product (must belong to this farmer)
$stmt = $conn->prepare("
  SELECT id, product_name, unit, price, quantity
  FROM farmer_products
  WHERE id = ? AND farmer_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $productId, $farmerId);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();

if (!$product) back("Product not found.");

$currentStock = (int)$product['quantity'];
$price        = (float)$product['price'];
$productName  = $product['product_name'];
$unit         = $product['unit'];

$conn->begin_transaction();

try {
  if ($action === "increase") {

    $stmt = $conn->prepare("
      UPDATE farmer_products
      SET quantity = quantity + ?
      WHERE id = ? AND farmer_id = ?
    ");
    $stmt->bind_param("iii", $qty, $productId, $farmerId);
    $stmt->execute();
    $stmt->close();

  } else {
    // decrease
    if ($qty > $currentStock) {
      throw new Exception("Not enough stock to deduct.");
    }

    $stmt = $conn->prepare("
      UPDATE farmer_products
      SET quantity = quantity - ?
      WHERE id = ? AND farmer_id = ?
    ");
    $stmt->bind_param("iii", $qty, $productId, $farmerId);
    $stmt->execute();
    $stmt->close();

    // record outflow
    $stmt = $conn->prepare("
      INSERT INTO stock_outflows (farmer_id, product_id, quantity, reason, date)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiss", $farmerId, $productId, $qty, $reason, $date);
    $stmt->execute();
    $stmt->close();

    // ✅ If reason is Sold, add Income automatically
    if ($reason === "Sold") {
      $amount = $qty * $price;

      $desc = "Manual sale: {$productName} ({$qty} {$unit})";

      $stmt = $conn->prepare("
        INSERT INTO farmer_transactions (farmer_id, type, amount, description, date)
        VALUES (?, 'Income', ?, ?, ?)
      ");
      $stmt->bind_param("idss", $farmerId, $amount, $desc, $date);
      $stmt->execute();
      $stmt->close();
    }
  }

  $conn->commit();
} catch (Exception $e) {
  $conn->rollback();
  back($e->getMessage());
}

$conn->close();
header("Location: stocks.php?success=" . urlencode("Stock updated successfully."));
exit;