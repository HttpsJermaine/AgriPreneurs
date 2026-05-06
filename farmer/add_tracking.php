<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$farmer_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: orders.php");
  exit;
}

$order_id     = (int)($_POST['order_id'] ?? 0);
$provider     = trim($_POST['provider'] ?? '');       // optional
$tracking_ref = trim($_POST['tracking_ref'] ?? '');   // optional
$tracking_url = trim($_POST['tracking_url'] ?? '');   // optional

if ($order_id <= 0) die("Invalid order.");

// Optional: basic URL validation
if ($tracking_url !== '' && !preg_match('#^https?://#i', $tracking_url)) {
  die("Tracking URL must start with http:// or https://");
}

// If farmer did not provide a tracking ref, generate a free internal one
if ($tracking_ref === '') {
  // Example: PLM-000028
  $tracking_ref = "PLM-" . str_pad((string)$order_id, 6, "0", STR_PAD_LEFT);
}

// If provider empty, store "Internal" so UI doesn't look blank
if ($provider === '') {
  $provider = "Internal";
}

$conn->begin_transaction();

try {
  // Ensure farmer owns the order and it is approved + deliver
  $q = $conn->prepare("
    SELECT id, fulfillment, status
    FROM orders
    WHERE id=? AND farmer_id=?
    LIMIT 1
  ");
  if (!$q) throw new Exception("Prepare failed: " . $conn->error);

  $q->bind_param("ii", $order_id, $farmer_id);
  $q->execute();
  $o = $q->get_result()->fetch_assoc();
  $q->close();

  if (!$o) throw new Exception("Order not found.");
  if (strtolower($o['status']) !== 'approved') throw new Exception("Order must be approved first.");
  if (strtolower($o['fulfillment']) !== 'deliver') throw new Exception("This order is not for delivery.");

  // Ensure shipments row exists
  $ins = $conn->prepare("INSERT IGNORE INTO shipments (order_id, status, created_at, updated_at) VALUES (?, 'preparing', NOW(), NOW())");
  if (!$ins) throw new Exception("Prepare failed (insert shipments): " . $conn->error);
  $ins->bind_param("i", $order_id);
  $ins->execute();
  $ins->close();

  // Update shipments fields
  $up = $conn->prepare("
    UPDATE shipments
    SET provider=?, tracking_ref=?, tracking_url=?, updated_at=NOW()
    WHERE order_id=?
  ");
  if (!$up) throw new Exception("Prepare failed (update shipments): " . $conn->error);

  $up->bind_param("sssi", $provider, $tracking_ref, $tracking_url, $order_id);
  $up->execute();
  $up->close();

  // Log event: Tracking added/updated
  $getShip = $conn->prepare("SELECT id FROM shipments WHERE order_id=? LIMIT 1");
  $getShip->bind_param("i", $order_id);
  $getShip->execute();
  $shipRow = $getShip->get_result()->fetch_assoc();
  $getShip->close();

  $shipmentId = (int)($shipRow['id'] ?? 0);

  if ($shipmentId > 0) {
    $note = "Tracking saved: {$provider} ({$tracking_ref})";
    $ev = $conn->prepare("
      INSERT INTO shipment_events (shipment_id, status, note, event_time)
      VALUES (?, 'preparing', ?, NOW())
    ");
    if (!$ev) throw new Exception("Prepare failed (insert event): " . $conn->error);
    $ev->bind_param("is", $shipmentId, $note);
    $ev->execute();
    $ev->close();
  }

  $conn->commit();
  header("Location: orders.php?ok=1");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  die("Add tracking failed: " . $e->getMessage());
}
