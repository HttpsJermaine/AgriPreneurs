<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$farmer_id = (int)$_SESSION['user_id'];

$action = $_POST['action'] ?? '';
$order_id = (int)($_POST['order_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($order_id <= 0) {
  header("Location: orders.php");
  exit;
}

$conn->begin_transaction();

try {
  $q = $conn->prepare("
    SELECT id, farmer_id, status, fulfillment, stock_deducted, outflow_logged
    FROM orders
    WHERE id=? AND farmer_id=?
    FOR UPDATE
  ");
  $q->bind_param("ii", $order_id, $farmer_id);
  $q->execute();
  $o = $q->get_result()->fetch_assoc();
  $q->close();

  if (!$o) throw new Exception("Order not found.");

  $fulfillment = strtolower($o['fulfillment'] ?? '');

    // Helper: insert marketplace transaction ONCE (per farmer+order)
    $logMarketplaceTxn = function() use ($conn, $farmer_id, $order_id) {
      $sum = $conn->prepare("
        SELECT COALESCE(SUM(oi.qty * oi.price), 0) AS amount
        FROM order_items oi
        JOIN farmer_products fp ON fp.id = oi.product_id
        WHERE oi.order_id = ?
          AND fp.farmer_id = ?
      ");
      $sum->bind_param("ii", $order_id, $farmer_id);
      $sum->execute();
      $row = $sum->get_result()->fetch_assoc();
      $sum->close();
  
      $amount = (float)($row['amount'] ?? 0);
  
      $txnDate = date("Y-m-d");
      $desc = "Order Completed";
  
      $ins = $conn->prepare("
        INSERT INTO marketplace_transactions (farmer_id, order_id, amount, description, txn_date)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          amount = VALUES(amount),
          description = VALUES(description),
          txn_date = VALUES(txn_date)
      ");
      $ins->bind_param("iidss", $farmer_id, $order_id, $amount, $desc, $txnDate);
      $ins->execute();
      $ins->close();
    };  

  // Helper: log stock outflow ONCE
  $logOutflowOnce = function() use ($conn, $farmer_id, $order_id, $o) {
    if ((int)$o['outflow_logged'] === 1) return;

    $items = $conn->prepare("SELECT product_id, qty FROM order_items WHERE order_id=?");
    $items->bind_param("i", $order_id);
    $items->execute();
    $r = $items->get_result();

    $ins = $conn->prepare("
      INSERT INTO stock_outflows (product_id, farmer_id, quantity, reason, date, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $outReason = "Order completed";
    $date = date("Y-m-d");

    while ($it = $r->fetch_assoc()) {
      $pid = (int)$it['product_id'];
      $qty = (int)$it['qty'];
      $ins->bind_param("iiiss", $pid, $farmer_id, $qty, $outReason, $date);
      $ins->execute();
    }

    $ins->close();
    $items->close();

    $flag = $conn->prepare("UPDATE orders SET outflow_logged=1 WHERE id=?");
    $flag->bind_param("i", $order_id);
    $flag->execute();
    $flag->close();
  };

  if ($action === 'approve') {
    if ($o['status'] !== 'pending') throw new Exception("Only pending orders can be approved.");

    // Approve order
    $up = $conn->prepare("UPDATE orders SET status='approved' WHERE id=? AND farmer_id=? AND status='pending'");
    $up->bind_param("ii", $order_id, $farmer_id);
    $up->execute();
    $up->close();

    // If deliver, create shipment + first event
    if ($fulfillment === 'deliver') {
      $insShip = $conn->prepare("INSERT IGNORE INTO shipments (order_id, status) VALUES (?, 'preparing')");
      $insShip->bind_param("i", $order_id);
      $insShip->execute();
      $insShip->close();

      $getShip = $conn->prepare("SELECT id FROM shipments WHERE order_id=? FOR UPDATE");
      $getShip->bind_param("i", $order_id);
      $getShip->execute();
      $shipRow = $getShip->get_result()->fetch_assoc();
      $getShip->close();

      $shipmentId = (int)($shipRow['id'] ?? 0);
      if ($shipmentId > 0) {
        $cntStmt = $conn->prepare("SELECT COUNT(*) AS c FROM shipment_events WHERE shipment_id=?");
        $cntStmt->bind_param("i", $shipmentId);
        $cntStmt->execute();
        $cnt = (int)($cntStmt->get_result()->fetch_assoc()['c'] ?? 0);
        $cntStmt->close();

        if ($cnt === 0) {
          $ev = $conn->prepare("INSERT INTO shipment_events (shipment_id, status, note) VALUES (?, 'preparing', 'Order is being prepared')");
          $ev->bind_param("i", $shipmentId);
          $ev->execute();
          $ev->close();
        }
      }
    }
  }

  elseif ($action === 'decline') {
    if ($o['status'] !== 'pending') throw new Exception("Only pending orders can be declined.");
    if ($reason === '') throw new Exception("Decline reason is required.");

    // Decline it
    $up = $conn->prepare("UPDATE orders SET status='declined', decline_reason=? WHERE id=? AND farmer_id=? AND status='pending'");
    $up->bind_param("sii", $reason, $order_id, $farmer_id);
    $up->execute();
    $up->close();

    // Restore stock if it was deducted
    if ((int)$o['stock_deducted'] === 1) {
      $items = $conn->prepare("SELECT product_id, qty FROM order_items WHERE order_id=?");
      $items->bind_param("i", $order_id);
      $items->execute();
      $r = $items->get_result();

      $restore = $conn->prepare("UPDATE farmer_products SET quantity = quantity + ? WHERE id=? AND farmer_id=?");
      while ($it = $r->fetch_assoc()) {
        $qty = (int)$it['qty'];
        $pid = (int)$it['product_id'];
        $restore->bind_param("iii", $qty, $pid, $farmer_id);
        $restore->execute();
      }
      $restore->close();
      $items->close();

      $reset = $conn->prepare("UPDATE orders SET stock_deducted=0 WHERE id=?");
      $reset->bind_param("i", $order_id);
      $reset->execute();
      $reset->close();
    }
  }

  // Pickup completion (farmer marks READY FOR PICKUP -> buyer will confirm received)
  elseif ($action === 'complete') {
    if (strtolower($o['status']) !== 'approved') {
      throw new Exception("Only approved orders can be marked ready for pickup.");
    }

    if ($fulfillment !== 'pickup') {
      throw new Exception("This action is only for pickup orders.");
    }

    // IMPORTANT: move order to 'awaiting' so buyer sees Confirm Received
    $up = $conn->prepare("
      UPDATE orders
      SET status='awaiting'
      WHERE id=? AND status='approved'
    ");
    $up->bind_param("i", $order_id);
    $up->execute();

    if ($up->affected_rows <= 0) {
      $up->close();
      throw new Exception("No changes were made. Order may already be awaiting or status is not approved.");
    }
    $up->close();

    // Optional: store an event under shipments (works as a timeline)
    $insShip = $conn->prepare("INSERT IGNORE INTO shipments (order_id, status) VALUES (?, 'preparing')");
    $insShip->bind_param("i", $order_id);
    $insShip->execute();
    $insShip->close();

    $getShip = $conn->prepare("SELECT id FROM shipments WHERE order_id=? LIMIT 1");
    $getShip->bind_param("i", $order_id);
    $getShip->execute();
    $shipRow = $getShip->get_result()->fetch_assoc();
    $getShip->close();

    $shipmentId = (int)($shipRow['id'] ?? 0);
    if ($shipmentId > 0) {
      $ev = $conn->prepare("
        INSERT INTO shipment_events (shipment_id, status, note)
        VALUES (?, 'preparing', 'Ready for pickup (awaiting buyer confirmation)')
      ");
      $ev->bind_param("i", $shipmentId);
      $ev->execute();
      $ev->close();
    }
  }
  
  // Delivery milestones
  elseif ($action === 'ship_out' || $action === 'ship_delivered') {
    if ($o['status'] !== 'approved') throw new Exception("Only approved orders can be updated for delivery.");
    if ($fulfillment !== 'deliver') throw new Exception("This order is not for delivery.");

    $insShip = $conn->prepare("INSERT IGNORE INTO shipments (order_id, status) VALUES (?, 'preparing')");
    $insShip->bind_param("i", $order_id);
    $insShip->execute();
    $insShip->close();

    $getShip = $conn->prepare("SELECT id, status FROM shipments WHERE order_id=? FOR UPDATE");
    $getShip->bind_param("i", $order_id);
    $getShip->execute();
    $ship = $getShip->get_result()->fetch_assoc();
    $getShip->close();

    $shipmentId = (int)($ship['id'] ?? 0);
    $current = strtolower($ship['status'] ?? 'preparing');
    if ($shipmentId <= 0) throw new Exception("Shipment not found.");

    if ($action === 'ship_out') {
      if ($current !== 'preparing') throw new Exception("Shipment must be 'preparing' first.");
      $new = 'out_for_delivery';
      $note = 'Out for delivery';
    } else {
      if ($current !== 'out_for_delivery') throw new Exception("Shipment must be 'out_for_delivery' first.");
      $new = 'delivered';
      $note = 'Delivered to buyer';
    }

    $upShip = $conn->prepare("UPDATE shipments SET status=? WHERE id=?");
    $upShip->bind_param("si", $new, $shipmentId);
    $upShip->execute();
    $upShip->close();

    $ev = $conn->prepare("INSERT INTO shipment_events (shipment_id, status, note) VALUES (?, ?, ?)");
    $ev->bind_param("iss", $shipmentId, $new, $note);
    $ev->execute();
    $ev->close();

    if ($new === 'delivered') {
      $up = $conn->prepare("
        UPDATE orders
        SET status='awaiting'
        WHERE id=? AND farmer_id=? AND status='approved'
      ");
      $up->bind_param("ii", $order_id, $farmer_id);
      $up->execute();
      $up->close();
    } 
  }

  else {
    throw new Exception("Invalid action.");
  }

  $conn->commit();

  $_SESSION['flash_type'] = 'success';
  $_SESSION['flash_msg']  = 'Order updated successfully.';
  header("Location: orders.php");
  exit;

} catch (Exception $e) {
  $conn->rollback();

  $_SESSION['flash_type'] = 'error';
  $_SESSION['flash_msg']  = $e->getMessage();
  header("Location: orders.php");
  exit;
}

