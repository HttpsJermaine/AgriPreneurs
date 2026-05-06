<?php
// This file is included from paymongo_payment.php after successful payment

if (!isset($_SESSION['pending_checkout']) || !isset($buyer_id)) {
    header("Location: checkout.php");
    exit;
}

$checkout = $_SESSION['pending_checkout']['data'];
$buyer_id = (int)$_SESSION['user_id'];

// Extract data from checkout session
$mode = $checkout['mode'] ?? 'cart';
$fulfillment = $checkout['fulfillment'] ?? 'pickup';
$delivery_provider = $checkout['delivery_provider'] ?? '';
$address_id = (int)($checkout['address_id'] ?? 0);
$delivery_fee = (float)($checkout['delivery_fee'] ?? 0);
$payment_method = 'paymongo';
$payment_status = 'paid';

// Get delivery address if needed
$delivery_address = null;
if ($fulfillment === 'deliver' && $address_id > 0) {
    $stmt = $conn->prepare("SELECT label, street, city, province, zip FROM buyer_addresses WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $address_id, $buyer_id);
    $stmt->execute();
    $addr = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($addr) {
        $delivery_address = trim(
            ($addr['label'] ?? '') . " - " .
            ($addr['street'] ?? '') . ", " .
            ($addr['city'] ?? '') . ", " .
            ($addr['province'] ?? '') . " " .
            ($addr['zip'] ?? '')
        );
    }
}

// Group items by farmer (from checkout data)
$itemsByFarmer = [];
foreach ($checkout['items'] as $item) {
    $fid = (int)($item['farmer_id'] ?? 0);
    if (!isset($itemsByFarmer[$fid])) {
        $itemsByFarmer[$fid] = [];
    }
    $itemsByFarmer[$fid][] = $item;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Prepare statements
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
    
    if (!$insOrder) throw new Exception("Prepare failed: " . $conn->error);
    
    $insItem = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, qty, price, unit)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$insItem) throw new Exception("Prepare failed: " . $conn->error);
    
    $deduct = $conn->prepare("
        UPDATE farmer_products
        SET quantity = quantity - ?
        WHERE id = ? AND quantity >= ?
    ");
    if (!$deduct) throw new Exception("Prepare failed: " . $conn->error);
    
    $createdOrderIds = [];
    
    foreach ($itemsByFarmer as $fid => $farmerItems) {
        $delivery_address_db = $delivery_address ?? '';
        $date_needed_db = ''; 
         
        $insOrder->bind_param(
            "iissssdss",  
            $buyer_id,         
            $fid,                
            $fulfillment,       
            $delivery_address_db, 
            $date_needed_db,      
            $delivery_provider,   
            $delivery_fee,       
            $payment_method,      
            $payment_status       
        );
        
        $insOrder->execute();
        if ($insOrder->errno) throw new Exception("Execute failed: " . $insOrder->error);
        
        $order_id = $conn->insert_id;
        $createdOrderIds[] = $order_id;
        
        // Insert items and deduct stock
        foreach ($farmerItems as $item) {
            $product_id = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $product_name = $item['product_name'] ?? '';
            $qty = (int)($item['qty'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            $unit = $item['unit'] ?? 'pc';
            
            if ($qty <= 0) continue;
            
            // Insert order item
            $insItem->bind_param(
                "iisids",
                $order_id,
                $product_id,
                $product_name,
                $qty,
                $price,
                $unit
            );
            $insItem->execute();
            if ($insItem->errno) throw new Exception("Item insert failed: " . $insItem->error);
            
            // Deduct stock
            $deduct->bind_param("iii", $qty, $product_id, $qty);
            $deduct->execute();
            if ($deduct->errno) throw new Exception("Stock deduct failed: " . $deduct->error);
            
            if ($deduct->affected_rows !== 1) {
                throw new Exception("Insufficient stock for product ID $product_id");
            }
        }
    }
    
    // Clear cart items if from cart
    if ($mode !== 'buynow' && isset($checkout['cart_item_ids']) && is_array($checkout['cart_item_ids'])) {
        $cartItemIds = array_filter($checkout['cart_item_ids']);
        if (!empty($cartItemIds)) {
            $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
            $types = str_repeat('i', count($cartItemIds));
            
            $delete = $conn->prepare("DELETE FROM cart_items WHERE id IN ($placeholders)");
            if ($delete) {
                $delete->bind_param($types, ...$cartItemIds);
                $delete->execute();
                $delete->close();
            }
        }
    }
    
    $conn->commit();
    
    // Clear the pending checkout
    unset($_SESSION['pending_checkout']);
    
    // Store success message
    $_SESSION['flash_msg'] = "Order placed successfully!";
    $_SESSION['flash_type'] = "success";
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Order creation failed: " . $e->getMessage());
    $_SESSION['flash_msg'] = "Failed to create order: " . $e->getMessage();
    $_SESSION['flash_type'] = "error";
}
?>