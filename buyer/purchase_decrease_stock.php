<?php
session_start();
require_once "db_connection.php";
header('Content-Type: application/json');

// You can allow this to be called by checkout logic (buyer must be logged in)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['quantity'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');

if ($product_id <= 0 || $qty <= 0) {
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

// Start transaction
$conn->begin_transaction();

// Fetch product and farmer
$stmt = $conn->prepare("SELECT farmer_id, price, quantity FROM farmer_products WHERE id=? FOR UPDATE");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $conn->rollback();
    echo json_encode(['success'=>false,'error'=>'Product not found']);
    exit;
}
$p = $res->fetch_assoc();
$farmer_id = (int)$p['farmer_id'];
$price = (float)$p['price'];
$current_qty = (int)$p['quantity'];

if ($qty > $current_qty) {
    $conn->rollback();
    echo json_encode(['success'=>false,'error'=>'Insufficient stock']);
    exit;
}

$new_qty = $current_qty - $qty;
$stmt->close();

$stmt = $conn->prepare("UPDATE farmer_products SET quantity=? WHERE id=?");
$stmt->bind_param("ii", $new_qty, $product_id);
$stmt->execute();
$stmt->close();

$total_price = $price * $qty;
$stmt = $conn->prepare("INSERT INTO product_sales (buyer_id, product_id, farmer_id, quantity, total_price, date) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiids", $buyer_id, $product_id, $farmer_id, $qty, $total_price, $date);
$stmt->execute();
$sale_id = $stmt->insert_id;
$stmt->close();

// insert stock_outflows
$stmt = $conn->prepare("INSERT INTO stock_outflows (product_id, farmer_id, quantity, reason, date) VALUES (?, ?, ?, ?, ?)");
$reason = 'Sold';
$stmt->bind_param("iiiss", $product_id, $farmer_id, $qty, $reason, $date);
$stmt->execute();
$stmt->close();

$conn->commit();
echo json_encode(['success'=>true,'sale_id'=>$sale_id,'new_qty'=>$new_qty]);
