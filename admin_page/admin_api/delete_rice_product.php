<?php
// Turn off HTML error display
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'error' => $message
    ]);
    exit;
}

// Check admin session
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    sendResponse(false, 'Access denied');
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Validate product ID
if (!isset($_POST['product_id'])) {
    sendResponse(false, 'Product ID not provided');
}

$productId = intval($_POST['product_id']);

if ($productId <= 0) {
    sendResponse(false, 'Invalid product ID');
}

// Include db_connection - try multiple paths
$paths = [
    '../../../db_connection.php',  // admin_api -> admin_page -> parent (where db_connection likely is)
    '../../db_connection.php',     // admin_api -> admin_page
    '../db_connection.php',        // admin_api -> parent
    'db_connection.php'            // same folder
];

$db_found = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_found = true;
        break;
    }
}

if (!$db_found) {
    sendResponse(false, 'Database connection file not found');
}

if (!isset($conn) || $conn->connect_error) {
    sendResponse(false, 'Database connection failed');
}

// Delete the product
$deleteQuery = "DELETE FROM rice_products WHERE id = ?";
$stmt = $conn->prepare($deleteQuery);
$stmt->bind_param("i", $productId);

if ($stmt->execute()) {
    sendResponse(true, 'Product deleted successfully');
} else {
    sendResponse(false, 'Delete failed: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>