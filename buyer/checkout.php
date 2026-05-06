<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];
$mode = $_GET['mode'] ?? 'cart';

$items = [];
$total = 0;

// Load buyer addresses
$addresses = [];
$stmt = $conn->prepare("SELECT id, label, street, city, province, zip FROM buyer_addresses WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$resAddr = $stmt->get_result();
while ($row = $resAddr->fetch_assoc()) {
  $addresses[] = $row;
}
$stmt->close();

/* -------------------------
   LOAD ITEMS (BUY NOW / CART)
--------------------------*/
if ($mode === 'buynow' && !empty($_SESSION['buy_now'])) {
  $pid = (int)$_SESSION['buy_now']['product_id'];
  $qty = (int)$_SESSION['buy_now']['qty'];

  $stmt = $conn->prepare("
    SELECT fp.*, fp.farmer_id, fd.farmer_name, fd.phone, fd.street, fd.city, fd.province, fd.zip
    FROM farmer_products fp
    JOIN farmer_details fd ON fd.user_id = fp.farmer_id
    WHERE fp.id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $p = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$p) die("Product not found.");
  if ($qty > (int)$p['quantity']) die("Not enough stock.");

  $items[] = [
    'product_id' => (int)$p['id'],
    'farmer_id' => (int)$p['farmer_id'],
    'product_name' => $p['product_name'],
    'qty' => $qty,
    'price' => (float)$p['price'],
    'unit' => $p['unit'],
    'image' => $p['image'],
    'fulfillment_options' => $p['fulfillment_options'] ?? '',
    'farmer_name' => $p['farmer_name'],
    'farmer_phone' => $p['phone'],
    'farmer_address' => trim(($p['street'] ?? '') . " " . ($p['city'] ?? '') . " " . ($p['province'] ?? '') . " " . ($p['zip'] ?? '')),
  ];

  $total = $qty * (float)$p['price'];

} else {
  // cart mode
  $stmt = $conn->prepare("SELECT id FROM carts WHERE buyer_id=? LIMIT 1");
  $stmt->bind_param("i", $buyer_id);
  $stmt->execute();
  $cartRow = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $cart_id = (int)($cartRow['id'] ?? 0);

  $selected = $_POST['selected_items'] ?? [];
  $selected = array_map('intval', (array)$selected);
  $selected = array_values(array_filter($selected, fn($x) => $x > 0));

  if ($cart_id <= 0 || count($selected) === 0) {
    header("Location: cart.php?err=select");
    exit;
  }

  $placeholders = implode(',', array_fill(0, count($selected), '?'));
  $types = 'i' . str_repeat('i', count($selected));

  $sql = "
    SELECT 
      fp.id AS product_id,
      fp.farmer_id AS farmer_id,
      fp.product_name,
      fp.unit,
      fp.image,
      fp.fulfillment_options,
      ci.id AS cart_item_id,
      ci.qty,
      ci.price_at_add AS price,
      fd.farmer_name,
      fd.phone AS farmer_phone,
      CONCAT(fd.street,' ',fd.city,' ',fd.province,' ',fd.zip) AS farmer_address
    FROM cart_items ci
    JOIN farmer_products fp ON fp.id = ci.product_id
    JOIN farmer_details fd ON fd.user_id = fp.farmer_id
    WHERE ci.cart_id = ?
      AND ci.id IN ($placeholders)
    ORDER BY ci.id DESC
  ";

  $stmt = $conn->prepare($sql);
  $params = array_merge([$cart_id], $selected);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();

  $res = $stmt->get_result();
  $items = [];
  $total = 0;

  while ($r = $res->fetch_assoc()) {
    $items[] = $r;
    $total += ((int)$r['qty']) * ((float)$r['price']);
  }
  $stmt->close();

  if (count($items) === 0) {
    header("Location: cart.php?err=select");
    exit;
  }

  $_SESSION['checkout_selected_cart_item_ids'] = array_column($items, 'cart_item_id');
}

/* -------------------------
   GROUP BY FARMER + DELIVERY FLAGS
--------------------------*/
$groups = [];
$hasPickupOnly = false;
$hasDeliverable = false;

foreach ($items as $it) {
  $fid = (int)($it['farmer_id'] ?? 0);

  if (!isset($groups[$fid])) {
    $groups[$fid] = [
      'farmer_id' => $fid,
      'farmer_name' => $it['farmer_name'] ?? 'Farmer',
      'farmer_phone' => $it['farmer_phone'] ?? ($it['farmer_phone'] ?? ''),
      'farmer_address' => $it['farmer_address'] ?? 'Farmer location',
      'items' => [],
    ];
  }
  $groups[$fid]['items'][] = $it;

  $optStr = strtolower(trim($it['fulfillment_options'] ?? ''));
  $opts = array_filter(array_map('trim', explode(',', $optStr)));

  $supportsDelivery = in_array('delivery', $opts, true) || in_array('deliver', $opts, true);

  if ($supportsDelivery) $hasDeliverable = true;
  else $hasPickupOnly = true;
}

// IMPORTANT: allow Deliver if at least one item supports delivery
$allowDeliver = $hasDeliverable;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review order</title>
  <link rel="stylesheet" href="css/checkout.css">
  <link rel="stylesheet" href="css/mobileview.css">
</head>
<body>

<header><?php require 'header.php'?></header>

<form method="POST" action="place_order.php?mode=<?= htmlspecialchars($mode) ?>">
  <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
  <input type="hidden" name="force_fulfillment" id="forceFulfillment" value="">
  <input type="hidden" name="confirm_pickup_for_mixed" id="confirmMixed" value="0">

  <div class="checkout-container">
    <h1 class="checkout-title">Shopping Cart Review</h1>
    <h2 class="vendor-name">PLAMAL Marketplace</h2>

    <div class="checkout-layout">
      <div class="checkout-left">

        <div class="info-box">
          <h3>How do you want this order to be fulfilled?</h3>

          <div class="fulfill-row">
            <label>
              <input type="radio" id="fulfillPickup" name="fulfillment" value="pickup" checked>
              Pickup
            </label>

            <label>
              <input type="radio" id="fulfillDeliver" name="fulfillment" value="deliver">
              Deliver
            </label>
          </div>

          <div class="deliver-box" id="deliverBox" style="display:none;">
            <label style="display:block; margin-bottom:6px;">Choose delivery address</label>

            <select name="address_id" id="addressSelect">
              <option value="">-- Select address --</option>
              <?php foreach($addresses as $a): ?>
                <?php
                  $label = trim($a['label'] ?? '') ?: "Address";
                  $text = trim(($a['street'] ?? '') . ", " . ($a['city'] ?? '') . ", " . ($a['province'] ?? '') . " " . ($a['zip'] ?? ''));
                ?>
                <option value="<?= (int)$a['id'] ?>">
                  <?= htmlspecialchars($label . " - " . $text) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <?php if (count($addresses) === 0): ?>
              <p style="margin-top:10px; color:#d93025;">
                No saved addresses yet. Please add one in your Profile.
              </p>
            <?php endif; ?>

            <label style="display:block; margin:12px 0 6px;">Choose courier</label>
            <select name="delivery_provider" id="providerSelect">
              <option value="">-- Select courier --</option>
              <option value="Lalamove">Lalamove</option>
              <option value="Grab">Grab</option>
              <option value="Toktok">Toktok</option>
              <option value="J&T">J&T</option>
              <option value="LBC">LBC</option>
            </select>

            <div class="policy-warning" id="deliveryPolicyWarning" style="display: none; margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffe69c; border-radius: 8px; font-size: 13px; color: #856404;">
              <i class="fas fa-truck"></i>
              <strong>Delivery Policy:</strong> Minimum of 10 sacks required. 
              <span id="currentQuantityDisplay"></span>
            </div>

            <input type="hidden" name="delivery_fee" id="deliveryFeeInput" value="0">

          </div>
        </div>

        <div class="info-box">
          <h3>Review Cart</h3>

          <?php if (count($items) === 0): ?>
            <p>Your cart is empty.</p>
          <?php else: ?>

            <?php foreach($groups as $g): ?>
              <div class="farmer-group">
                <div class="farmer-head">
                  Farmer: <?= htmlspecialchars($g['farmer_name']) ?>
                  <?php if (!empty($g['farmer_phone'])): ?>
                    • <?= htmlspecialchars($g['farmer_phone']) ?>
                  <?php endif; ?>
                </div>

                <?php foreach($g['items'] as $it): ?>
                  <?php
                    $img = !empty($it['image']) ? "../uploads/products/" . htmlspecialchars($it['image']) : "images/sample.jpg";
                    $lineTotal = ((int)$it['qty']) * ((float)$it['price']);
                  ?>
                  <div class="cart-review-item">
                    <img src="<?php echo $img; ?>" alt="product">
                    <div>
                      <p class="item-title"><?php echo htmlspecialchars($it['product_name']); ?></p>
                      <p class="item-details">
                        <?php echo (int)$it['qty']; ?> × ₱<?php echo number_format((float)$it['price'],2); ?>
                        = Total: ₱<?php echo number_format($lineTotal,2); ?>
                      </p>
                    </div>
                  </div>
                <?php endforeach; ?>

                <div class="pickup-loc">
                  <b>Pickup Location:</b> <?= htmlspecialchars($g['farmer_address'] ?? 'Farmer location'); ?>
                </div>
              </div>
            <?php endforeach; ?>

            <div class="review-pricing">
             <p>Subtotal: <span>₱<?php echo number_format($total,2); ?></span></p>
             <p>Delivery fee: <span id="deliveryFeeText">₱0.00</span></p>
             <p class="total">Total: <span id="grandTotalText">₱<?= number_format($total,2) ?></span></p>
            </div>

          <?php endif; ?>
        </div>

      </div>

      <div class="checkout-right">
        <div class="right-box">
        <h4>Payment Option</h4>
        
        <div class="payment-options" style="margin-top: 15px;">
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding: 10px; border: 2px solid #e0f0e8; border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="payment-option" id="codOption">
                <input type="radio" name="payment_method" value="cod" checked style="width: 18px; height: 18px; accent-color: #124131;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #124131;">Cash on Delivery</div>
                    <div style="font-size: 13px; color: #666;">Pay when you receive your order</div>
                </div>
                <span style="font-size: 24px;">💵</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 2px solid #e0f0e8; border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="payment-option" id="qrOption">
                <input type="radio" name="payment_method" value="paymongo" style="width: 18px; height: 18px; accent-color: #124131;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #124131;">Pay with GCash / Card</div>
                    <div style="font-size: 13px; color: #666;">Pay instantly via GCash, Maya, or Credit Card</div>
                </div>
                <span style="font-size: 24px;">📱</span>
            </label>
        </div>
        
        <div id="paymongoInfo" style="display: none; margin-top: 15px; padding: 15px; background: #e8f4f0; border-radius: 12px;">
            <p style="margin-bottom: 10px; color: #124131;">You will be redirected to Paymongo's secure payment page after placing your order.</p>
            <p style="font-size: 13px; color: #666;">✅ Secure payment via GCash, Maya, Credit/Debit Cards</p>
        </div>
    </div>

        <div class="right-box">
          <h4 id="scheduleTitle">Schedule</h4>
          <div id="scheduleText" style="color:#333;">
            Pickup anytime (after farmer approval).
          </div>
        </div>

        <div class="right-box">
          <h4 id="locationTitle">Pickup Locations</h4>

          <div id="pickupText">
            <?php foreach($groups as $g): ?>
              <div style="margin-bottom:10px;">
                <div style="font-weight:700; color:#124131;"><?= htmlspecialchars($g['farmer_name']) ?></div>
                <div><?= htmlspecialchars($g['farmer_address'] ?? 'Farmer location') ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div id="deliverText" style="display:none; color:#333;">
            No address selected
          </div>
        </div>

        <button type="submit" class="place-order-btn" id="placeOrderBtn" <?= count($items)===0 ? "disabled" : ""; ?>>
          Place Order
        </button>

        <a href="products.php" class="continue-btn">Continue Shopping</a>
      </div>
    </div>
  </div>
</form>

<?php require 'footer.php'?>

<!-- Mixed fulfillment modal -->
<div class="modal-overlay" id="mixedModal" style="display:none;">
  <div class="modal-box">
    <h3>Delivery Notice</h3>
    <div id="mixedMsg">
      One of the products that you selected is not available for delivery.
      Are you alright with pick-up?
    </div>

    <div class="modal-actions">
      <button class="modal-ok" type="button" id="mixedYes">Yes</button>
      <button class="modal-alt" type="button" id="mixedBack">Go back to Cart</button>
    </div>
  </div>
</div>

<script>
  window.ALLOW_DELIVER   = <?= $allowDeliver ? 'true' : 'false' ?>;
  window.HAS_PICKUP_ONLY = <?= $hasPickupOnly ? 'true' : 'false' ?>;
  window.HAS_DELIVERABLE = <?= $hasDeliverable ? 'true' : 'false' ?>;
  window.SUBTOTAL = <?= json_encode((float)$total) ?>;
</script>

<script>

const providerSelect = document.getElementById("providerSelect");
const deliveryFeeText = document.getElementById("deliveryFeeText");
const deliveryFeeInput = document.getElementById("deliveryFeeInput");
const grandTotalText = document.getElementById("grandTotalText");

const radios = document.querySelectorAll('input[name="fulfillment"]');
const deliverBox = document.getElementById("deliverBox");
const addressSelect = document.getElementById("addressSelect");

const locationTitle = document.getElementById("locationTitle");
const pickupText = document.getElementById("pickupText");
const deliverText = document.getElementById("deliverText");

const pickupRadio  = document.getElementById("fulfillPickup");
const deliverRadio = document.getElementById("fulfillDeliver");

const mixedModal = document.getElementById("mixedModal");
const mixedYes = document.getElementById("mixedYes");
const mixedBack = document.getElementById("mixedBack");
const confirmMixed = document.getElementById("confirmMixed");
const forceFulfillment = document.getElementById("forceFulfillment");

const scheduleTitle = document.getElementById("scheduleTitle");
const scheduleText  = document.getElementById("scheduleText");

// Function to calculate total quantity across all items
function getTotalQuantity() {
    let totalQty = 0;
    <?php foreach($items as $item): ?>
        totalQty += <?= (int)$item['qty'] ?>;
    <?php endforeach; ?>
    return totalQty;
}

// Add this function to update the delivery policy warning
function updateDeliveryPolicyWarning() {
    const totalQty = getTotalQuantity();
    const warningEl = document.getElementById('deliveryPolicyWarning');
    const deliverRadio = document.getElementById('fulfillDeliver');
    
    if (!warningEl) return; // Exit if warning element doesn't exist
    
    if (deliverRadio && deliverRadio.checked) {
        if (totalQty < 10) {
            warningEl.style.display = 'block';
            warningEl.innerHTML = `<i class="fas fa-exclamation-triangle"></i> 
                <strong>Delivery Policy:</strong> Minimum of 10 sacks required. 
                Current total: ${totalQty} sack(s) (${10 - totalQty} more needed)`;
            warningEl.style.background = '#fff3cd';
            warningEl.style.color = '#856404';
            warningEl.style.border = '1px solid #ffe69c';
        } else if (totalQty > 50) {
            warningEl.style.display = 'block';
            warningEl.innerHTML = `<i class="fas fa-exclamation-circle"></i> 
                <strong>Delivery Policy:</strong> Maximum of 50 sacks allowed. 
                Current total: ${totalQty} sacks (exceeds limit)`;
            warningEl.style.background = '#fee2e2';
            warningEl.style.color = '#b3261e';
            warningEl.style.border = '1px solid #fecaca';
        } else {
            warningEl.style.display = 'none';
        }
    } else {
        warningEl.style.display = 'none';
    }
}

// Update the deliverRadio change event listener
if (deliverRadio) {
    deliverRadio.addEventListener("change", function() {
        const hasPickupOnly = !!window.HAS_PICKUP_ONLY;
        const hasDeliverable = !!window.HAS_DELIVERABLE;

        if (this.checked && hasPickupOnly && hasDeliverable) {
            // revert to pickup while asking
            pickupRadio.checked = true;
            setFulfillment("pickup");
            updateScheduleDisplay();
            openMixedModal();
        }
        updateDeliveryPolicyWarning();
    });
}

// Call on page load
document.addEventListener('DOMContentLoaded', function() {
    updateDeliveryPolicyWarning();
});

// Update the deliverRadio change event listener
deliverRadio?.addEventListener("change", function() {
    const hasPickupOnly = !!window.HAS_PICKUP_ONLY;
    const hasDeliverable = !!window.HAS_DELIVERABLE;

    if (this.checked && hasPickupOnly && hasDeliverable) {
        // revert to pickup while asking
        pickupRadio.checked = true;
        setFulfillment("pickup");
        updateScheduleDisplay();
        openMixedModal();
    }
    updateDeliveryPolicyWarning();
});

// Update the quantity displays when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateDeliveryPolicyWarning();
});

function estimateFeeFromProvince(province) {
  // your current heuristic idea; replace later with real distance/API quote
  const p = (province || '').toLowerCase().trim();
  if (p === 'bulacan') return 80;
  const near = ['pampanga','nueva ecija','tarlac','bataan','metro manila','manila','cavite','laguna','rizal'];
  if (near.includes(p)) return 150;
  return 220;
}

function extractProvinceFromAddressOptionText(txt) {
  // "Label - street, city, province zip"
  const parts = (txt || '').split(',');
  return (parts[2] || '').trim().split(' ')[0]; // same pattern you used
}

function updateTotals() {
  const chosen = document.querySelector('input[name="fulfillment"]:checked')?.value || "pickup";
  let fee = 0;

  if (chosen === "deliver") {
    const opt = addressSelect?.options[addressSelect.selectedIndex];
    const province = extractProvinceFromAddressOptionText(opt?.textContent || "");
    fee = estimateFeeFromProvince(province);
  }

  deliveryFeeText.textContent = "₱" + fee.toFixed(2);
  deliveryFeeInput.value = fee.toFixed(2);

  const grand = (window.SUBTOTAL || 0) + fee;
  grandTotalText.textContent = "₱" + grand.toFixed(2);
}



// call when things change
addressSelect?.addEventListener("change", updateTotals);
providerSelect?.addEventListener("change", updateTotals);
radios.forEach(r => r.addEventListener("change", updateTotals));

// Show Paymongo info when selected
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'paymongo') {
            document.getElementById('paymongoInfo').style.display = 'block';
        } else {
            document.getElementById('paymongoInfo').style.display = 'none';
        }
    });
});

function addBusinessDaysJS(dateObj, days) {
  // business day: Mon-Sat (skip Sunday)
  while (days > 0) {
    dateObj.setDate(dateObj.getDate() + 1);
    if (dateObj.getDay() !== 0) days--;
  }
  return dateObj;
}

// mirror of your server heuristic for DISPLAY only
function leadDaysFor(province) {
  province = (province || '').toLowerCase().trim();
  if (province === 'bulacan') return 1;

  const near = ['pampanga','nueva ecija','tarlac','bataan','metro manila','manila','cavite','laguna','rizal'];
  if (near.includes(province)) return 2;

  return 3;
}

function setFulfillment(mode) {
  if (mode === "deliver") {
    if (deliverBox) deliverBox.style.display = "block";
    locationTitle.textContent = "Delivery Address";
    pickupText.style.display = "none";
    deliverText.style.display = "block";
  } else {
    if (deliverBox) deliverBox.style.display = "none";
    locationTitle.textContent = "Pickup Locations";
    pickupText.style.display = "block";
    deliverText.style.display = "none";
  }
}

function updateScheduleDisplay() {
  const chosen = document.querySelector('input[name="fulfillment"]:checked')?.value || "pickup";

  if (chosen === "pickup") {
    scheduleTitle.textContent = "Schedule";
    scheduleText.textContent = "Pickup anytime (after farmer approval).";
    return;
  }

  // deliver
  const opt = addressSelect?.options[addressSelect.selectedIndex];
  const text = opt?.textContent || "";

  // crude extract province from "Label - street, city, province zip"
  const parts = text.split(',');
  const provincePart = (parts[2] || '').trim().split(' ')[0];
  const lead = leadDaysFor(provincePart);

  const d = new Date();
  addBusinessDaysJS(d, lead);

  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');

  scheduleTitle.textContent = "Estimated Delivery Date";
  scheduleText.textContent = `${yyyy}-${mm}-${dd} (business day, based on location)`;
}

function enforceFulfillmentRules() {
  if (!pickupRadio || !deliverRadio) return;

  const hasPickupOnly = !!window.HAS_PICKUP_ONLY;
  const hasDeliverable = !!window.HAS_DELIVERABLE;

  // No deliverable items at all => disable Deliver
  if (hasPickupOnly && !hasDeliverable) {
    deliverRadio.disabled = true;
    deliverRadio.checked = false;

    pickupRadio.disabled = false;
    pickupRadio.checked = true;

    setFulfillment("pickup");
    updateScheduleDisplay();
    return;
  }

  // Mixed OR all deliverable => allow Deliver (modal will handle mixed)
  deliverRadio.disabled = false;
  pickupRadio.disabled = false;
}

// Address select display text
if (addressSelect) {
  addressSelect.addEventListener("change", function() {
    const opt = this.options[this.selectedIndex];
    deliverText.textContent = opt && opt.value ? opt.textContent : "No address selected";
    updateScheduleDisplay();
  });
}

// Mixed modal helpers
function openMixedModal() {
  if (mixedModal) mixedModal.style.display = "flex";
}
function closeMixedModal() {
  if (mixedModal) mixedModal.style.display = "none";
}

// close modal clicking outside
mixedModal?.addEventListener("click", (e) => {
  if (e.target === mixedModal) closeMixedModal();
});

// When user selects Deliver in MIXED cart => show modal and revert to pickup
deliverRadio?.addEventListener("change", function() {
  const hasPickupOnly = !!window.HAS_PICKUP_ONLY;
  const hasDeliverable = !!window.HAS_DELIVERABLE;

  if (this.checked && hasPickupOnly && hasDeliverable) {
    // revert to pickup while asking
    pickupRadio.checked = true;
    setFulfillment("pickup");
    updateScheduleDisplay();
    openMixedModal();
  }
});

// YES => accept pickup and continue checkout
mixedYes?.addEventListener("click", () => {
  if (confirmMixed) confirmMixed.value = "1";
  if (forceFulfillment) forceFulfillment.value = "pickup";

  pickupRadio.checked = true;
  setFulfillment("pickup");
  updateScheduleDisplay();
  closeMixedModal();
});

// Go back => redirect to cart.php
mixedBack?.addEventListener("click", () => {
  window.location.href = "cart.php";
});

// Radio change general behavior
radios.forEach(r => {
  r.addEventListener("change", function() {
    if (this.value === "deliver") {
      setFulfillment("deliver");
    } else {
      setFulfillment("pickup");
    }
    updateScheduleDisplay();
  });
});

enforceFulfillmentRules();
setFulfillment("pickup");
updateScheduleDisplay();

// Payment option styling
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.payment-option').forEach(opt => {
            opt.style.borderColor = '#e0f0e8';
            opt.style.background = 'white';
        });
        this.style.borderColor = '#124131';
        this.style.background = '#f0f9f4';
        
        // Check the radio button inside
        this.querySelector('input[type="radio"]').checked = true;
    });
});

// Show Paymongo info when selected
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'paymongo') {
            document.getElementById('paymongoInfo').style.display = 'block';
        } else {
            document.getElementById('paymongoInfo').style.display = 'none';
        }
    });
});

// Highlight selected payment on page load
window.addEventListener('load', function() {
    const checked = document.querySelector('input[name="payment_method"]:checked');
    if (checked) {
        const parent = checked.closest('.payment-option');
        if (parent) {
            parent.style.borderColor = '#124131';
            parent.style.background = '#f0f9f4';
        }
    }
});

function storeCheckoutData() {
    // Collect all form data
    const formData = new FormData(document.querySelector('form'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Add selected items
    data.items = <?= json_encode($items) ?>;
    data.groups = <?= json_encode($groups) ?>;
    data.total = <?= $total ?>;
    data.mode = '<?= $mode ?>';
    
    // Show loading state
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const originalText = placeOrderBtn.textContent;
    placeOrderBtn.textContent = 'Processing...';
    placeOrderBtn.disabled = true;
    
    // Store in session via AJAX
    fetch('store_checkout_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Redirect to payment page
            window.location.href = 'paymongo_payment.php';
        } else {
            alert('Error: ' + result.error);
            // Reset button
            placeOrderBtn.textContent = originalText;
            placeOrderBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        // Reset button
        placeOrderBtn.textContent = originalText;
        placeOrderBtn.disabled = false;
    });
}

document.querySelector("form")?.addEventListener("submit", function(e) {

const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
const chosen = document.querySelector('input[name="fulfillment"]:checked')?.value || "pickup";
const totalQuantity = getTotalQuantity();

/* DELIVERY VALIDATION */
if (chosen === "deliver") {

    if (!addressSelect?.value) {
        e.preventDefault();
        alert("Please select an address for delivery.");
        return;
    }

    if (!providerSelect?.value) {
        e.preventDefault();
        alert("Please select a courier.");
        return;
    }

    if (totalQuantity < 10) {
        e.preventDefault();

        alert(
          "Delivery requires a minimum of 10 sacks.\n\n" +
          "Your current total is " + totalQuantity + " sack(s).\n\n" +
          "Please choose Pickup instead or add more items."
        );

        // auto switch to pickup
        pickupRadio.checked = true;
        setFulfillment("pickup");
        updateScheduleDisplay();
        updateTotals();

        return;
    }

    if (totalQuantity > 50) {
        e.preventDefault();
        alert("Delivery maximum is 50 sacks.");
        return;
    }
}
if (paymentMethod === "paymongo") {
    e.preventDefault();
    storeCheckoutData();
    return;
}
});

// Auto-select pickup when redirected from validation
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get("force_pickup") === "1") {
    pickupRadio.checked = true;
    setFulfillment("pickup");
    updateScheduleDisplay();
}
</script>

</body>
</html>
