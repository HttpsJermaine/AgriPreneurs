<?php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$farmerId = (int)$_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);

if (!$productId) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

// Verify product belongs to this farmer
$stmt = $conn->prepare("SELECT id, product_image FROM product_requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $productId, $farmerId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

// Delete image file if exists
if ($product['product_image']) {
    $imagePath = '../uploads/products/' . $product['product_image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// Delete from database
$stmt = $conn->prepare("DELETE FROM product_requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $productId, $farmerId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
?>