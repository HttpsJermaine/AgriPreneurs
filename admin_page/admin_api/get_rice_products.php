<?php
session_start();

// Simple test - no database yet
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Try to include db_connection - try multiple paths
$db_loaded = false;
$paths_to_try = ['../db_connection.php', '../../db_connection.php', 'db_connection.php'];

foreach ($paths_to_try as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

if (!$db_loaded) {
    echo json_encode(['success' => false, 'error' => 'Database connection file not found. Tried: ' . implode(', ', $paths_to_try)]);
    exit;
}

$query = "SELECT id, name, price, image FROM rice_products ORDER BY name";
$result = $conn->query($query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'image' => $row['image']
    ];
}

echo json_encode(['success' => true, 'products' => $products]);
?>