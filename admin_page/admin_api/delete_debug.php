<?php
// This file will show any PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting debug...<br>";

session_start();
echo "Session started<br>";

// Include db_connection
$db_path = '../db_connection.php';
echo "Looking for db_connection at: " . $db_path . "<br>";

if (file_exists($db_path)) {
    echo "db_connection.php found!<br>";
    require_once $db_path;
    if ($conn) {
        echo "Database connected!<br>";
    } else {
        echo "Database connection failed!<br>";
    }
} else {
    echo "db_connection.php NOT found at: " . $db_path . "<br>";
}

echo "Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
echo "Session role: " . ($_SESSION['role'] ?? 'not set') . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST request received<br>";
    echo "product_id: " . ($_POST['product_id'] ?? 'not set') . "<br>";
    
    $productId = intval($_POST['product_id'] ?? 0);
    
    if ($productId > 0) {
        echo "Attempting to delete product ID: $productId<br>";
        
        $result = $conn->query("DELETE FROM rice_products WHERE id = $productId");
        
        if ($result) {
            echo "Delete successful!<br>";
        } else {
            echo "Delete failed: " . $conn->error . "<br>";
        }
    }
} else {
    echo "Not a POST request. Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
}
?>