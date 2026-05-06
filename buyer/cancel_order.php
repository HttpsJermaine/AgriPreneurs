<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$order_id = (int)($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
  header("Location: orders_list.php?status=pending&err=invalid");
  exit;
}

$conn->begin_transaction();

try {
  // 1) Lock the order row
  $stmt = $conn->prepare("
    SELECT id, status, farmer_id, stock_deducted
    FROM orders
    WHERE id = ? AND buyer_id = ?
    FOR UPDATE
  ");
  if (!$stmt) throw new Exception("Prepare failed (select order): " . $conn->error);

  $stmt->bind_param("ii", $order_id, $buyer_id);
  $stmt->execute();
  $order = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$order) throw new Exception("Order not found.");

  $status = strtolower($order['status'] ?? '');
  if ($status !== 'pending') {
    throw new Exception("You can only cancel pending orders.");
  }

  // 2) If stock was deducted during checkout, return it
  if ((int)$order['stock_deducted'] === 1) {

    // Get order items
    $it = $conn->prepare("SELECT product_id, qty FROM order_items WHERE order_id=?");
    if (!$it) throw new Exception("Prepare failed (select items): " . $conn->error);

    $it->bind_param("i", $order_id);
    $it->execute();
    $itemsRes = $it->get_result();

    $items = [];
    while ($r = $itemsRes->fetch_assoc()) {
      $items[] = $r;
    }
    $it->close();

    if (count($items) === 0) throw new Exception("Order has no items.");

    // Prepare stock return query (NO SELECT FOR UPDATE needed here)
    $addBack = $conn->prepare("
      UPDATE farmer_products
      SET quantity = quantity + ?
      WHERE id = ?
    ");
    if (!$addBack) throw new Exception("Prepare failed (add back): " . $conn->error);

    foreach ($items as $row) {
      $pid = (int)$row['product_id'];
      $qty = (int)$row['qty'];

      $addBack->bind_param("ii", $qty, $pid);
      $addBack->execute();

      if ($addBack->affected_rows !== 1) {
        throw new Exception("Failed to return stock for product_id=$pid");
      }
    }

    $addBack->close();

    // Set stock_deducted back to 0 (so it won't return again)
    $flag = $conn->prepare("UPDATE orders SET stock_deducted = 0 WHERE id=?");
    if (!$flag) throw new Exception("Prepare failed (flag back): " . $conn->error);

    $flag->bind_param("i", $order_id);
    $flag->execute();
    $flag->close();
  }

  // 3) Cancel order
  $upd = $conn->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND buyer_id=?");
  if (!$upd) throw new Exception("Prepare failed (cancel): " . $conn->error);

  $upd->bind_param("ii", $order_id, $buyer_id);
  $upd->execute();

  if ($upd->affected_rows !== 1) {
    throw new Exception("Cancel failed (no rows updated).");
  }
  $upd->close();

  $conn->commit();

  header("Location: orders_list.php?status=cancelled&ok=1");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  die("Cancel failed: " . $e->getMessage());
}