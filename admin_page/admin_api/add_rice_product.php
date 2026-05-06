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

$name = trim($_POST['name'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$image = '';

if (empty($name) || $price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product name or price']);
    exit;
}

// Handle image upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp']);
        exit;
    }
    
    $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        $image = 'products/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
        exit;
    }
}

$stmt = $conn->prepare("INSERT INTO rice_products (name, price, image) VALUES (?, ?, ?)");
$stmt->bind_param("sds", $name, $price, $image);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
?>