<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=" . urlencode("Access denied"));
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];

function flash($type, $msg) {
  $_SESSION['flash_type'] = $type; // success | error
  $_SESSION['flash_msg']  = $msg;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  flash("error", "Invalid request.");
  header("Location: orders_list.php?status=awaiting");
  exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) {
  flash("error", "Invalid order.");
  header("Location: orders_list.php?status=awaiting");
  exit;
}

$conn->begin_transaction();

try {
  // Lock order
  $stmt = $conn->prepare("
    SELECT id, buyer_id, farmer_id, status, fulfillment, stock_deducted, outflow_logged
    FROM orders
    WHERE id=? AND buyer_id=?
    FOR UPDATE
  ");
  $stmt->bind_param("ii", $order_id, $buyer_id);
  $stmt->execute();
  $o = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$o) throw new Exception("Order not found.");

  $status = strtolower($o['status'] ?? '');
  if ($status !== 'awaiting') {
    throw new Exception("This order is not yet ready for confirmation.");
  }

  $farmer_id = (int)$o['farmer_id'];

  // Mark completed (ONLY buyer confirmation)
  $up = $conn->prepare("
    UPDATE orders
    SET status='completed',
        completed_at = NOW(),
        completed_by = 'buyer'
    WHERE id=? AND buyer_id=? AND status='awaiting'
  ");
  $up->bind_param("ii", $order_id, $buyer_id);
  $up->execute();

  if ($up->affected_rows <= 0) {
    $up->close();
    throw new Exception("Nothing changed. Please refresh and try again.");
  }
  $up->close();

  // Get items (for outflows + marketplace sum)
  $items = $conn->prepare("SELECT product_id, qty, price FROM order_items WHERE order_id=?");
  $items->bind_param("i", $order_id);
  $items->execute();
  $res = $items->get_result();

  $rows = [];
  $total = 0.0;
  while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
    $total += ((float)$r['qty'] * (float)$r['price']);
  }
  $items->close();

  // SAFETY (optional): Deduct stock ONLY if not deducted yet
  // If your checkout already deducts stock (recommended), stock_deducted will be 1 and this block won't run.
  if ((int)$o['stock_deducted'] !== 1) {
    $ded = $conn->prepare("
      UPDATE farmer_products
      SET quantity = GREATEST(quantity - ?, 0)
      WHERE id=? AND farmer_id=?
    ");
    foreach ($rows as $r) {
      $qty = (int)$r['qty'];
      $pid = (int)$r['product_id'];
      $ded->bind_param("iii", $qty, $pid, $farmer_id);
      $ded->execute();
    }
    $ded->close();

    $flag = $conn->prepare("UPDATE orders SET stock_deducted=1 WHERE id=?");
    $flag->bind_param("i", $order_id);
    $flag->execute();
    $flag->close();
  }

  // Log stock outflows ONCE
  if ((int)$o['outflow_logged'] !== 1) {
    $ins = $conn->prepare("
      INSERT INTO stock_outflows (product_id, farmer_id, quantity, reason, date, created_at)
      VALUES (?, ?, ?, 'Order completed', CURDATE(), NOW())
    ");

    foreach ($rows as $r) {
      $pid = (int)$r['product_id'];
      $qty = (int)$r['qty'];
      $ins->bind_param("iii", $pid, $farmer_id, $qty);
      $ins->execute();
    }
    $ins->close();

    $flag2 = $conn->prepare("UPDATE orders SET outflow_logged=1 WHERE id=?");
    $flag2->bind_param("i", $order_id);
    $flag2->execute();
    $flag2->close();
  }

  // Log marketplace transaction (ONCE per farmer+order)
  $desc = "Order Completed";
  $txnDate = date("Y-m-d");

  $mp = $conn->prepare("
    INSERT INTO marketplace_transactions (farmer_id, order_id, amount, description, txn_date)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      amount = VALUES(amount),
      description = VALUES(description),
      txn_date = VALUES(txn_date)
  ");
  $mp->bind_param("iidss", $farmer_id, $order_id, $total, $desc, $txnDate);
  $mp->execute();
  $mp->close();

  $conn->commit();

  flash("success", "Order confirmed received. Thank you!");
  header("Location: orders_list.php?status=completed");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  flash("error", $e->getMessage());
  header("Location: orders_list.php?status=awaiting");
  exit;
}