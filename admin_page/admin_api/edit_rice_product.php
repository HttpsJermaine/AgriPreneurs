<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors as HTML
session_start();

// Try multiple paths for db_connection
$paths_to_try = ['../db_connection.php', '../../db_connection.php', 'db_connection.php'];
$db_loaded = false;

foreach ($paths_to_try as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

header('Content-Type: application/json');

if (!$db_loaded) {
    echo json_encode(['success' => false, 'error' => 'Database connection file not found']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$price = floatval($_POST['price'] ?? 0);

if ($productId <= 0 || empty($name) || $price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product data']);
    exit;
}

// Get current image path
$stmt = $conn->prepare("SELECT image FROM rice_products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$currentProduct = $result->fetch_assoc();
$stmt->close();

$image = $currentProduct['image'] ?? '';

// Handle image upload if new image is provided
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../uploads/products/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }
    
    $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        // Delete old image if exists
        if (!empty($currentProduct['image']) && file_exists('../../uploads/' . $currentProduct['image'])) {
            unlink('../../uploads/' . $currentProduct['image']);
        }
        $image = 'products/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
        exit;
    }
}

$stmt = $conn->prepare("UPDATE rice_products SET name = ?, price = ?, image = ? WHERE id = ?");
$stmt->bind_param("sdsi", $name, $price, $image, $productId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
?>