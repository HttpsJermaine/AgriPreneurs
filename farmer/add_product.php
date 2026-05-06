<?php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    echo json_encode(["success" => false, "error" => "Access denied"]);
    exit;
}

$farmer_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid product selected"]);
    exit;
}

// Get product details from rice_products table
$stmt = $conn->prepare("SELECT id, name, price, image FROM rice_products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(["success" => false, "error" => "Product not found"]);
    exit;
}

// CHECK FOR DUPLICATE in product_requests table
$checkStmt = $conn->prepare("
    SELECT id, status FROM product_requests 
    WHERE user_id = ? AND rice_variety = ? AND status != 'rejected'
");
$checkStmt->bind_param("is", $farmer_id, $product['name']);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();
    $statusText = $existing['status'] === 'active' ? 'active in your products' : 'pending approval';
    echo json_encode(["success" => false, "error" => "You already have '" . $product['name'] . "' (" . $statusText . ")"]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Also check in farmer_products table (already active products)
$checkStmt2 = $conn->prepare("
    SELECT id FROM farmer_products 
    WHERE farmer_id = ? AND product_name = ?
");
$checkStmt2->bind_param("is", $farmer_id, $product['name']);
$checkStmt2->execute();
$checkResult2 = $checkStmt2->get_result();

if ($checkResult2->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "You already have '" . $product['name'] . "' in your active stocks"]);
    $checkStmt2->close();
    exit;
}
$checkStmt2->close();

// Insert into product_requests table with status 'active' (no approval needed)
$stmt = $conn->prepare("
    INSERT INTO product_requests (
        user_id, 
        rice_variety, 
        price_per_sack, 
        product_image, 
        status,
        created_at
    ) 
    VALUES (?, ?, ?, ?, 'active', NOW())
");

if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
    exit;
}

$imageName = $product['image'] ?? null;
$stmt->bind_param("isds", $farmer_id, $product['name'], $product['price'], $imageName);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true, 
        "message" => "Product added successfully to your profile!",
        "request_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "error" => "Failed to add product: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>