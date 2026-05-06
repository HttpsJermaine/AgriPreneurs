<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$mode = $_GET['mode'] ?? ($_POST['mode'] ?? 'cart');

$delivery_provider = trim($_POST['delivery_provider'] ?? '');
$payment_method    = trim($_POST['payment_method'] ?? 'cod');

// Check if this is a Paymongo payment (from QR modal)
$is_paid = isset($_POST['payment_status']) && $_POST['payment_status'] === 'paid';

// Set payment status based on method
if ($payment_method === 'paymongo' && $is_paid) {
    $payment_status = 'paid'; // Payment completed via Paymongo
} else {
    $payment_status = 'unpaid'; // Default for COD or failed Paymongo
}

function normalizePlace($s) {
  $s = strtolower(trim((string)$s));
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}

function addBusinessDays(DateTime $dt, int $days): DateTime {
  // business day = Mon-Sat (skip Sunday)
  while ($days > 0) {
    $dt->modify('+1 day');
    // 0 = Sunday
    if ((int)$dt->format('w') !== 0) {
      $days--;
    }
  }
  return $dt;
}

function computeLeadDays(string $province, string $city): int {
  // Simple defendable rule-set (edit as you want)
  // Same province/city can be faster. Default longer.
  $province = normalizePlace($province);
  $city = normalizePlace($city);

  if ($province === '') return 3;

  // Example heuristic:
  // Bulacan deliveries faster (because PLAMAL base)
  if ($province === 'bulacan') return 1;

  // Same metro / nearby provinces could be 2
  $near = ['pampanga','nueva ecija','tarlac','bataan','metro manila','manila','cavite','laguna','rizal'];
  if (in_array($province, $near, true)) return 2;

  // Otherwise nationwide
  return 3;
}

function lookupDeliveryFee(mysqli $conn, string $province, string $city): float {
  $provinceN = normalizePlace($province);
  $cityN = normalizePlace($city);

  // 1) city + province exact (case-insensitive)
  $stmt = $conn->prepare("
    SELECT fee
    FROM delivery_fee_rules
    WHERE LOWER(TRIM(province)) = ?
      AND LOWER(TRIM(city)) = ?
    LIMIT 1
  ");
  $stmt->bind_param("ss", $provinceN, $cityN);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if ($row) return (float)$row['fee'];

  // 2) province-only fallback (city empty in table)
  $empty = '';
  $stmt = $conn->prepare("
    SELECT fee
    FROM delivery_fee_rules
    WHERE LOWER(TRIM(province)) = ?
      AND (city IS NULL OR TRIM(city) = ?)
    LIMIT 1
  ");
  $stmt->bind_param("ss", $provinceN, $empty);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if ($row) return (float)$row['fee'];

  // 3) default if no rule (you can change)
  return 0.00;
}


// fulfillment can be forced by checkout modal
$forceFulfillment = trim($_POST['force_fulfillment'] ?? '');
if ($forceFulfillment === 'pickup' || $forceFulfillment === 'deliver') {
  $fulfillment = $forceFulfillment;
} else {
  $fulfillment = ($_POST['fulfillment'] ?? 'pickup') === 'deliver' ? 'deliver' : 'pickup';
}

$date_needed = trim($_POST['date_needed'] ?? '');


$delivery_address = null;
$delivery_fee = 0.00;

if ($fulfillment === 'deliver') {

  $address_id = (int)($_POST['address_id'] ?? 0);
  if ($address_id <= 0) {
    header("Location: checkout.php?mode=" . urlencode($mode) . "&err=address");
    exit;
  }

  if ($delivery_provider === '') {
    header("Location: checkout.php?mode=" . urlencode($mode) . "&err=courier");
    exit;
  }

  // Fetch chosen address first
  $stmt = $conn->prepare("SELECT label, street, city, province, zip FROM buyer_addresses WHERE id=? AND user_id=? LIMIT 1");
  if (!$stmt) die("Prepare failed (address): " . $conn->error);

  $stmt->bind_param("ii", $address_id, $buyer_id);
  $stmt->execute();
  $addr = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$addr) {
    header("Location: checkout.php?mode=" . urlencode($mode) . "&err=address");
    exit;
  }

  $delivery_address = trim(
    ($addr['label'] ?? '') . " - " .
    ($addr['street'] ?? '') . ", " .
    ($addr['city'] ?? '') . ", " .
    ($addr['province'] ?? '') . " " .
    ($addr['zip'] ?? '')
  );

  // Compute delivery fee AFTER getting address
  $delivery_fee = lookupDeliveryFee($conn, $addr['province'] ?? '', $addr['city'] ?? '');
}

$itemsByFarmer = [];  
$allCartItemIds = []; 
$total_amount = 0; // Initialize total amount

if ($mode === 'buynow') {
  if (empty($_SESSION['buy_now'])) {
    header("Location: products.php");
    exit;
  }

  $pid = (int)$_SESSION['buy_now']['product_id'];
  $qty = (int)$_SESSION['buy_now']['qty'];

  $stmt = $conn->prepare("SELECT id, farmer_id, product_name, price, unit, quantity FROM farmer_products WHERE id=? LIMIT 1");
  if (!$stmt) die("Prepare failed (buynow product): " . $conn->error);

  $stmt->bind_param("i", $pid);
  $stmt->execute();
  if ($stmt->errno) die("Execute failed (buynow product): " . $stmt->error);

  $p = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$p) die("Product not found.");
  if ($qty <= 0) die("Invalid quantity.");
  if ($qty > (int)$p['quantity']) die("Not enough stock.");

  $fid = (int)$p['farmer_id'];
  if (!isset($itemsByFarmer[$fid])) $itemsByFarmer[$fid] = [];

  $item_total = $qty * (float)$p['price'];
  $total_amount += $item_total;

  $itemsByFarmer[$fid][] = [
    'product_id'   => (int)$p['id'],
    'product_name' => $p['product_name'],
    'qty'          => $qty,
    'price'        => (float)$p['price'],
    'unit'         => $p['unit'],
    'cart_item_id' => 0
  ];

} else {
  // cart mode
  $selectedIds = $_SESSION['checkout_selected_cart_item_ids'] ?? [];
  $selectedIds = array_map('intval', (array)$selectedIds);
  $selectedIds = array_values(array_filter($selectedIds, fn($x)=>$x>0));

  if (count($selectedIds) === 0) {
    header("Location: cart.php?err=select");
    exit;
  }

  // Find cart_id
  $stmt = $conn->prepare("SELECT id FROM carts WHERE buyer_id=? LIMIT 1");
  if (!$stmt) die("Prepare failed (cart id): " . $conn->error);

  $stmt->bind_param("i", $buyer_id);
  $stmt->execute();
  if ($stmt->errno) die("Execute failed (cart id): " . $stmt->error);

  $cartRow = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $cart_id = (int)($cartRow['id'] ?? 0);
  if ($cart_id <= 0) {
    header("Location: cart.php?err=select");
    exit;
  }

  $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
  $types = str_repeat('i', count($selectedIds));

  $sql = "
    SELECT
      ci.id AS cart_item_id,
      ci.qty,
      ci.price_at_add AS price,
      fp.id AS product_id,
      fp.farmer_id,
      fp.product_name,
      fp.unit
    FROM cart_items ci
    JOIN farmer_products fp ON fp.id = ci.product_id
    WHERE ci.cart_id = ?
      AND ci.id IN ($placeholders)
    ORDER BY ci.id DESC
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) die("Prepare failed (cart items): " . $conn->error);

  $bindTypes = "i" . $types;
  $params = array_merge([$cart_id], $selectedIds);

  $stmt->bind_param($bindTypes, ...$params);
  $stmt->execute();
  if ($stmt->errno) die("Execute failed (cart items): " . $stmt->error);

  $res = $stmt->get_result();

  while ($r = $res->fetch_assoc()) {
    $fid = (int)$r['farmer_id'];
    if (!isset($itemsByFarmer[$fid])) $itemsByFarmer[$fid] = [];

    $item_total = (int)$r['qty'] * (float)$r['price'];
    $total_amount += $item_total;

    $itemsByFarmer[$fid][] = [
      'product_id'   => (int)$r['product_id'],
      'product_name' => $r['product_name'],
      'qty'          => (int)$r['qty'],
      'price'        => (float)$r['price'],
      'unit'         => $r['unit'],
      'cart_item_id' => (int)$r['cart_item_id'],
    ];

    $allCartItemIds[] = (int)$r['cart_item_id'];
  }

  $stmt->close();

  if (count($itemsByFarmer) === 0) {
    header("Location: cart.php?err=select");
    exit;
  }
}

// ============================================
// DELIVERY QUANTITY VALIDATION - ADD THIS HERE
// ============================================
// Calculate total quantity for delivery validation
$total_quantity = 0;
foreach ($itemsByFarmer as $farmerItems) {
    foreach ($farmerItems as $item) {
        $total_quantity += (int)($item['qty'] ?? 0);
    }
}

// Validate delivery quantity policy (10-50 sacks)
if ($fulfillment === 'deliver') {
    if ($total_quantity < 10) {
        $_SESSION['flash_msg'] = "Delivery requires a minimum of 10 sacks. Your current total is $total_quantity sack(s). Please choose Pickup instead or add more items.";
        $_SESSION['flash_type'] = "error";
        header("Location: checkout.php?mode=" . urlencode($mode));
        exit;
    }
    
    if ($total_quantity > 50) {
        $_SESSION['flash_msg'] = "Delivery maximum is 50 sacks. Your current total is $total_quantity sacks. Please reduce quantity or choose Pickup.";
        $_SESSION['flash_type'] = "error";
        header("Location: checkout.php?mode=" . urlencode($mode) . "&force_pickup=1");
        exit;
    }
}
// ============================================
// END OF DELIVERY QUANTITY VALIDATION
// ============================================

// Add delivery fee to total
$total_amount += $delivery_fee;

$conn->begin_transaction();

try {
  // prepared statements reused for performance + safety
  $insOrder = $conn->prepare("
  INSERT INTO orders (
    buyer_id, farmer_id, fulfillment,
    delivery_address, date_needed,
    delivery_provider, delivery_fee,
    payment_method, payment_status,
    status, created_at
  )
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");
if (!$insOrder) throw new Exception("Prepare failed (insert order): " . $conn->error);

  $insItem = $conn->prepare("
    INSERT INTO order_items (order_id, product_id, product_name, qty, price, unit)
    VALUES (?, ?, ?, ?, ?, ?)
  ");
  if (!$insItem) throw new Exception("Prepare failed (insert items): " . $conn->error);

  $check = $conn->prepare("SELECT quantity FROM farmer_products WHERE id=? FOR UPDATE");
  if (!$check) throw new Exception("Prepare failed (check stock): " . $conn->error);

  $deduct = $conn->prepare("
    UPDATE farmer_products
    SET quantity = quantity - ?
    WHERE id = ?
      AND quantity >= ?
  ");
  if (!$deduct) throw new Exception("Prepare failed (deduct stock): " . $conn->error);

  $flag = $conn->prepare("UPDATE orders SET stock_deducted = 1 WHERE id=?");
  if (!$flag) throw new Exception("Prepare failed (flag): " . $conn->error);

  $createdOrderIds = [];

  foreach ($itemsByFarmer as $fid => $farmerItems) {
    if (!$farmerItems) continue;

    $delivery_address_db = ($fulfillment === 'deliver') ? $delivery_address : null;
    $date_needed_db      = ($date_needed !== '') ? $date_needed : null;
    
    $provider_db = ($fulfillment === 'deliver') ? $delivery_provider : null;
    $fee_db      = ($fulfillment === 'deliver') ? (float)$delivery_fee : 0.00;
    
    $payment_method_db = $payment_method ?: 'cod';
    $payment_status_db = $payment_status ?: 'unpaid';
    

    $insOrder->bind_param(
      "iissssdss",
      $buyer_id,
      $fid,
      $fulfillment,
      $delivery_address_db,
      $date_needed_db,
      $provider_db,
      $fee_db,
      $payment_method_db,
      $payment_status_db
    );
       

    $insOrder->execute();
    if ($insOrder->errno) throw new Exception("Execute failed (insert order farmer_id=$fid): " . $insOrder->error);

    $order_id = (int)$conn->insert_id;
    if ($order_id <= 0) throw new Exception("Order insert failed for farmer_id=$fid. insert_id=" . $conn->insert_id);

    // insert items + deduct stock for each item
    foreach ($farmerItems as $it) {
      $pid   = (int)$it['product_id'];
      $pname = $it['product_name'];
      $qty   = (int)$it['qty'];
      $price = (float)$it['price'];
      $unit  = $it['unit'];

      if ($qty <= 0) throw new Exception("Invalid qty for product ID $pid.");

      // lock and validate stock
      $check->bind_param("i", $pid);
      $check->execute();
      if ($check->errno) throw new Exception("Execute failed (check stock pid=$pid): " . $check->error);

      $row = $check->get_result()->fetch_assoc();
      if (!$row) throw new Exception("Product not found while deducting stock (ID: $pid).");

      if ($qty > (int)$row['quantity']) {
        throw new Exception("Not enough stock for product ID $pid. Available: " . (int)$row['quantity']);
      }

      // insert order item
      $insItem->bind_param("iisids", $order_id, $pid, $pname, $qty, $price, $unit);
      $insItem->execute();
      if ($insItem->errno) throw new Exception("Execute failed (insert item pid=$pid): " . $insItem->error);

      // deduct
      $deduct->bind_param("iii", $qty, $pid, $qty);
      $deduct->execute();
      if ($deduct->errno) throw new Exception("Execute failed (deduct pid=$pid): " . $deduct->error);

      if ($deduct->affected_rows !== 1) {
        throw new Exception("Failed to deduct stock for product ID $pid. affected_rows=" . $deduct->affected_rows);
      }
    }

    // flag stock deducted
    $flag->bind_param("i", $order_id);
    $flag->execute();
    if ($flag->errno) throw new Exception("Execute failed (flag): " . $flag->error);

    if ($flag->affected_rows !== 1) {
      throw new Exception("stock_deducted not updated. order_id=$order_id affected_rows=" . $flag->affected_rows);
    }

    $createdOrderIds[] = $order_id;
  }

  // cleanup statements
  $insOrder->close();
  $insItem->close();
  $check->close();
  $deduct->close();
  $flag->close();

  // If cart mode, remove those cart items 
  if ($mode !== 'buynow') {
    $allCartItemIds = array_values(array_filter(array_unique($allCartItemIds), fn($x)=>$x>0));
    if (count($allCartItemIds) > 0) {
      $ph = implode(',', array_fill(0, count($allCartItemIds), '?'));
      $types = str_repeat('i', count($allCartItemIds));

      $sql = "DELETE FROM cart_items WHERE id IN ($ph)";
      $stmt = $conn->prepare($sql);
      if (!$stmt) throw new Exception("Prepare failed (delete cart items): " . $conn->error);

      $stmt->bind_param($types, ...$allCartItemIds);
      $stmt->execute();
      if ($stmt->errno) throw new Exception("Execute failed (delete cart items): " . $stmt->error);

      $stmt->close();
    }
  }

  unset($_SESSION['buy_now']);
  unset($_SESSION['checkout_selected_cart_item_ids']);

  $conn->commit();

  // After successful order creation, check payment method
  if ($payment_method === 'paymongo' && !$is_paid) {
    // Get the first order ID (or handle multiple orders)
    $first_order_id = $createdOrderIds[0] ?? 0;
    
    if ($first_order_id) {
        // Store order ID in session for payment
        $_SESSION['pending_payment_order'] = $first_order_id;
        $_SESSION['pending_payment_amount'] = $total_amount;
        
        // Redirect to Paymongo payment page
        header("Location: paymongo_payment.php?order_id=" . $first_order_id);
        exit;
    }
  }
  
  // For COD or already paid Paymongo, redirect to orders list
  header("Location: orders_list.php?status=pending&ok=1");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  die("Place order failed: " . $e->getMessage());
}
?>