<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    header("Location: ../login.php?error=Access denied");
    exit;
}

$order_id = (int)($_GET['order_id'] ?? 0);
$buyer_id = (int)$_SESSION['user_id'];

// Update order status manually (for testing)
$stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND buyer_id = ?");
$stmt->bind_param("ii", $order_id, $buyer_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['flash_msg'] = "Payment marked as paid!";
    $_SESSION['flash_type'] = "success";
} else {
    $_SESSION['flash_msg'] = "Order not found or already paid";
    $_SESSION['flash_type'] = "error";
}
$stmt->close();

header("Location: orders_list.php");
exit;
?>