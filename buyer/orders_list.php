<?php
session_start();

// Check session FIRST
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

require_once "../db_connection.php";

// CRITICAL: Check if this order is already marked as paid
// This handles the case when user returns manually after payment
if (isset($_GET['order_id']) && !isset($_GET['payment'])) {
    $check_order_id = (int)$_GET['order_id'];
    $buyer_id = (int)$_SESSION['user_id'];
    
    // Check if this order exists and is still pending
    $stmt = $conn->prepare("SELECT id, payment_status FROM orders WHERE id = ? AND buyer_id = ?");
    $stmt->bind_param("ii", $check_order_id, $buyer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    // If order exists and is still unpaid, ask if they want to mark as paid
    if ($order && $order['payment_status'] === 'unpaid') {
        echo "<script>
            if (confirm('Did you complete the payment? Click OK to mark this order as paid.')) {
                window.location.href = 'orders_list.php?payment=success&order_id=$check_order_id';
            }
        </script>";
    }
}

// Handle Paymongo redirects
if (isset($_GET['payment'])) {
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    $buyer_id = (int)$_SESSION['user_id'];
    
    if ($_GET['payment'] === 'success' && $order_id > 0) {
        // First check if already paid to avoid duplicate messages
        $check_stmt = $conn->prepare("SELECT payment_status FROM orders WHERE id = ? AND buyer_id = ?");
        $check_stmt->bind_param("ii", $order_id, $buyer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $current_status = $check_result->fetch_assoc()['payment_status'] ?? 'unpaid';
        $check_stmt->close();
        
        if ($current_status === 'unpaid') {
            // Update order status to paid
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND buyer_id = ?");
            $stmt->bind_param("ii", $order_id, $buyer_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $_SESSION['flash_msg'] = "Payment successful! Your order has been placed.";
                $_SESSION['flash_type'] = "success";
                
                // Also update payment_transactions
                $stmt2 = $conn->prepare("UPDATE payment_transactions SET status = 'paid' WHERE order_id = ?");
                $stmt2->bind_param("i", $order_id);
                $stmt2->execute();
                $stmt2->close();
            }
            $stmt->close();
        } else {
            $_SESSION['flash_msg'] = "This order was already marked as paid.";
            $_SESSION['flash_type'] = "info";
        }
        
        // Redirect to remove query parameters
        header("Location: orders_list.php");
        exit;
        
    } elseif ($_GET['payment'] === 'failed' && $order_id > 0) {
        $_SESSION['flash_msg'] = "Payment failed. Please try again.";
        $_SESSION['flash_type'] = "error";
        header("Location: orders_list.php");
        exit;
        
    } elseif ($_GET['payment'] === 'cancelled' && $order_id > 0) {
        $_SESSION['flash_msg'] = "Payment was cancelled.";
        $_SESSION['flash_type'] = "info";
        header("Location: orders_list.php");
        exit;
    }
}

$buyer_id = (int)$_SESSION['user_id'];
$status = $_GET['status'] ?? 'pending';

$tabs = [
  'pending'   => 'Pending Orders',
  'awaiting'  => 'To Receive',
  'completed' => 'Completed',
  'cancelled' => 'Cancelled'
  
];

$imgBase = "../uploads/products/";

/* ---------- Flash message (clean banners) ---------- */
$flashType = $_SESSION['flash_type'] ?? ($_GET['type'] ?? '');
$flashMsg  = $_SESSION['flash_msg']  ?? ($_GET['msg'] ?? '');
unset($_SESSION['flash_type'], $_SESSION['flash_msg']);

function badgeClass($status) {
  $s = strtolower($status);
  if ($s === 'pending') return 'badge badge-pending';
  if ($s === 'approved') return 'badge badge-approved';
  if ($s === 'completed') return 'badge badge-completed';
  if ($s === 'cancelled') return 'badge badge-cancelled';
  if ($s === 'declined') return 'badge badge-declined';
  if ($s === 'awaiting') return 'badge badge-approved';
  return 'badge';
}

function statusLabel($status) {
  $s = strtolower($status);
  if ($s === 'approved') return 'to receive';
  if ($s === 'awaiting') return 'to receive';
  return $s;
}

function shipLabel($s){
  $s = strtolower($s ?? '');
  if ($s === 'preparing') return 'Preparing';
  if ($s === 'out_for_delivery') return 'Out for Delivery';
  if ($s === 'delivered') return 'Delivered';
  return 'Preparing';
}

/* ---------- Build WHERE clause by tab ---------- */
/*
  IMPORTANT:
  - "awaiting" tab should show BOTH approved and awaiting.
  - Confirm button should ONLY show when o.status='awaiting'
*/
$whereSql = "";
$bindTypes = "i";
$bindParams = [$buyer_id];

if ($status === 'awaiting') {
  $whereSql = "AND (o.status='approved' OR o.status='awaiting')";
} else {
  $map = [
    'pending'   => 'pending',
    'completed' => 'completed',
    'cancelled' => 'cancelled',
    'declined'  => 'declined'
  ];
  $dbStatus = $map[$status] ?? 'pending';
  $whereSql = "AND o.status = ?";
  $bindTypes .= "s";
  $bindParams[] = $dbStatus;
}

// Fixed SQL query - removed the PHP comment
$sql = "
  SELECT
    o.id AS order_id,
    o.status,
    o.created_at,
    o.fulfillment,
    o.date_needed,
    o.decline_reason,
    o.delivery_address,
    o.payment_method,
    o.payment_status,

    s.status AS ship_status,
    s.updated_at AS ship_updated_at,
    s.provider AS ship_provider,
    s.tracking_ref AS ship_tracking_ref,
    s.tracking_url AS ship_tracking_url,

    oi.product_id,
    oi.product_name,
    oi.qty,
    oi.price,
    oi.unit,

    fp.image AS product_image,

    totals.order_total
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  LEFT JOIN farmer_products fp ON fp.id = oi.product_id
  LEFT JOIN shipments s ON s.order_id = o.id
  LEFT JOIN (
    SELECT order_id, SUM(qty * price) AS order_total
    FROM order_items
    GROUP BY order_id
  ) totals ON totals.order_id = o.id
  WHERE o.buyer_id = ?
    $whereSql
  ORDER BY o.id DESC, oi.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($bindTypes, ...$bindParams);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
  $oid = (int)$row['order_id'];

  if (!isset($orders[$oid])) {
    $orders[$oid] = [
      'order_id'        => $oid,
      'status'          => $row['status'],
      'fulfillment'     => $row['fulfillment'] ?? '',
      'decline_reason'  => $row['decline_reason'] ?? '',
      'delivery_address'=> $row['delivery_address'] ?? 'Standard delivery address',
      'payment_method'  => $row['payment_method'] ?? 'cod',
      'payment_status'  => $row['payment_status'] ?? 'unpaid',

      'ship_status'     => $row['ship_status'] ?? '',
      'ship_updated_at' => $row['ship_updated_at'] ?? '',
      'ship_provider'   => $row['ship_provider'] ?? '',
      'ship_tracking_ref'=> $row['ship_tracking_ref'] ?? '',
      'ship_tracking_url'=> $row['ship_tracking_url'] ?? '',

      'order_total'     => (float)($row['order_total'] ?? 0),
      'items'           => []
    ];
  }

  $orders[$oid]['items'][] = [
    'product_id'    => (int)$row['product_id'],
    'product_name'  => $row['product_name'],
    'qty'           => (int)$row['qty'],
    'product_image' => $row['product_image'] ?? ''
  ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders</title>
  <link rel="stylesheet" href="css/orders_list.css">
  <link rel="stylesheet" href="css/mobileview.css">
  <style>
    /* Additional styles for payment badges */
    .payment-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 12px;
      border-radius: 40px;
      font-size: 12px;
      font-weight: 600;
      margin-left: 8px;
    }
    
    .payment-badge.cod {
      background: #fff3e0;
      color: #b85c00;
    }
    
    .payment-badge.paid {
      background: #e8f4f0;
      color: #124131;
    }
    
    .payment-badge.unpaid {
      background: #fee2e2;
      color: #b3261e;
    }
  </style>
</head>
<body>
<header>
  <?php require 'header.php' ?>
</header>

<div class="profile-container">
  <aside class="profile-sidebar">
    <h3 class="sidebar-title">Account Overview</h3>

    <ul class="sidebar-menu">
      <li><a href="orders_list.php" class="active">My Orders</a></li>
      <li><a href="profile.php">Profile</a></li>
    </ul>

    <button class="logout-btn" onclick="openLogout()">
      <span>⏻</span> Log Out
    </button>
  </aside>

  <section class="orders-content">
    <h2 class="orders-title">My Orders</h2>

    <?php if ($flashMsg !== ''): ?>
      <div style="
        margin: 12px 0 18px;
        padding: 12px 14px;
        border-radius: 12px;
        font-weight: 700;
        border: 1px solid <?= ($flashType === 'success') ? '#b7e4c7' : '#f3b8b8' ?>;
        background: <?= ($flashType === 'success') ? '#ecfff2' : '#fff2f2' ?>;
        color: <?= ($flashType === 'success') ? '#1b5e20' : '#b3261e' ?>;
      ">
        <?= htmlspecialchars($flashMsg) ?>
      </div>
    <?php endif; ?>

    <div class="orders-tabs">
      <?php foreach ($tabs as $key => $label): ?>
        <a href="orders_list.php?status=<?= $key ?>"
           class="<?= ($status === $key) ? 'active' : '' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="orders-box">
      <?php if (count($orders) === 0): ?>
        <div class="empty-orders">
          <img src="images/shopping-trolley.png" alt="No orders">
          <p>No orders yet</p>
        </div>
      <?php else: ?>

        <?php foreach ($orders as $o): ?>
          <?php
            $st = strtolower($o['status']);
            $ful = strtolower($o['fulfillment'] ?? '');
            $shipSt = strtolower($o['ship_status'] ?? '');
            $paymentMethod = $o['payment_method'] ?? 'cod';
            $paymentStatus = $o['payment_status'] ?? 'unpaid';
          ?>
          <div class="order-card" data-order-id="<?= $o['order_id'] ?>">

            <div class="order-head">
              <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <div class="<?= badgeClass($o['status']) ?>">
                  <?= htmlspecialchars(statusLabel($o['status'])) ?>
                </div>
                
                <?php if ($paymentMethod === 'paymongo' && $paymentStatus === 'paid'): ?>
                  <div class="payment-badge paid">
                    <span>✅</span> Paid via GCash
                  </div>
                <?php elseif ($paymentMethod === 'paymongo' && $paymentStatus === 'unpaid'): ?>
                  <div class="payment-badge unpaid">
                    <span>⏳</span> Payment Pending
                  </div>
                <?php elseif ($paymentMethod === 'cod'): ?>
                  <div class="payment-badge cod">
                    <span>💵</span> Cash on Delivery
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <?php if (in_array($st, ['approved','awaiting'], true) && $ful === 'deliver'): ?>
              <div style="margin:10px 0; padding:10px; border-radius:12px; background:#f1fbf4; border:1px solid #cfead6;">
                <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                  <div>
                    <b>Delivery Status:</b>
                    <?= htmlspecialchars(shipLabel($shipSt ?: 'preparing')) ?>
                    <?php if (!empty($o['ship_provider'])): ?>
                      <div style="font-size:13px; color:#333; margin-top:4px;">
                        <b>Courier:</b> <?= htmlspecialchars($o['ship_provider']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div style="color:#555; font-size:13px;">
                    <b>Last update:</b>
                    <?php if (!empty($o['ship_updated_at'])): ?>
                      <?= htmlspecialchars(date("M d, Y h:i A", strtotime($o['ship_updated_at']))) ?>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </div>
                </div>

                <?php if (!empty($o['ship_tracking_ref']) || !empty($o['ship_tracking_url'])): ?>
                  <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                    <?php if (!empty($o['ship_tracking_ref'])): ?>
                      <button type="button"
                        class="btn btn-approve"
                        style="background:#124131;"
                        onclick="openTrackingModal(
                          <?= (int)$o['order_id'] ?>,
                          '<?= htmlspecialchars($o['ship_status'] ?? 'preparing', ENT_QUOTES) ?>',
                          '<?= htmlspecialchars($o['ship_updated_at'] ?? '', ENT_QUOTES) ?>',
                          '<?= htmlspecialchars($o['fulfillment'] ?? '', ENT_QUOTES) ?>',
                          '<?= htmlspecialchars($o['delivery_address'] ?? 'Dropoff address not available', ENT_QUOTES) ?>',
                          '<?= htmlspecialchars($o['ship_provider'] ?? 'Lalamove', ENT_QUOTES) ?>',
                          '<?= htmlspecialchars($o['ship_tracking_ref'] ?? '', ENT_QUOTES) ?>'
                        )">
                        <span style="display:flex; align-items:center; gap:5px; color:white;">
                          <span>📍</span> Track Delivery
                        </span>
                      </button>
                    <?php endif; ?>
                    <?php if (!empty($o['ship_tracking_url'])): ?>
                      <a href="<?= htmlspecialchars($o['ship_tracking_url']) ?>" target="_blank"
                         class="btn btn-buyagain" style="padding:8px 12px; border-radius:10px;">
                        Open Tracking Link
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($st === 'declined'): ?>
              <div style="margin:10px 0; padding:10px; border-radius:10px; background:#fff3f3; color:#b3261e; font-size:14px;">
                <b>Decline Reason:</b>
                <?= htmlspecialchars($o['decline_reason'] ?: 'No reason provided.') ?>
              </div>
            <?php endif; ?>

            <?php foreach ($o['items'] as $it): ?>
              <?php
                $imgFile = trim($it['product_image'] ?? '');
                if ($imgFile !== '') {
                  $imgSrc = (strpos($imgFile, '/') !== false) ? $imgFile : ($imgBase . $imgFile);
                } else {
                  $imgSrc = "images/sample.jpg";
                }
              ?>
              <div class="order-item">
                <img class="thumb" src="<?= htmlspecialchars($imgSrc) ?>" alt="Product">
                <div class="item-info">
                  <div class="item-name"><?= htmlspecialchars($it['product_name']) ?></div>
                  <div class="item-qty">Qty: <?= (int)$it['qty'] ?></div>
                </div>
              </div>
            <?php endforeach; ?>

            <div class="order-foot">
              <div class="order-total">Total: ₱<?= number_format((float)$o['order_total'], 2) ?></div>

              <div class="actions">
                <?php if ($st === 'pending'): ?>
                  <form method="POST" action="cancel_order.php" style="margin:0;"
                        onsubmit="return confirm('Cancel this order?');">
                    <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                    <button type="submit" class="btn btn-cancel">Cancel Order</button>
                  </form>

                <?php elseif ($st === 'awaiting'): ?>
                  <!-- ONLY awaiting can be confirmed -->
                  <form method="POST" action="confirm_received.php" style="margin:0;"
                        onsubmit="return confirm('Confirm that you have received/picked up this order?');">
                    <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                    <button type="submit" class="btn btn-approve">Confirm Received</button>
                  </form>

                <?php elseif ($st === 'approved' && $status === 'awaiting'): ?>
                  <span class="muted" style="font-size:13px; color:#666;">
                    Waiting for farmer to mark ready/out for delivery.
                  </span>

                <?php elseif ($st === 'cancelled'): ?>
                  <?php $pid = (int)($o['items'][0]['product_id'] ?? 0); ?>
                  <?php if ($pid > 0): ?>
                    <a class="btn btn-buyagain" href="viewproduct.php?id=<?= $pid ?>">Buy Again</a>
                  <?php else: ?>
                    <span class="muted">No action available</span>
                  <?php endif; ?>

                <?php else: ?>
                  <span class="muted">No action available</span>
                <?php endif; ?>
              </div>
            </div>

          </div>
        <?php endforeach; ?>

      <?php endif; ?>

    </div>
  </section>
</div>

<?php require 'footer.php' ?>

<!-- Tracking Modal -->
<div class="tracking-overlay" id="trackingModal">
  <div class="tracking-modal">
    <div class="tracking-header">
      <div>
        <h2>Live Delivery Tracking
          <small id="modalOrderId">Order #123</small>
        </h2>
      </div>
      <button class="close-btn" onclick="closeTrackingModal()">×</button>
    </div>

    <div class="tracking-content">
      <!-- Map Container -->
      <div class="map-container">
        <div class="map-title">
          <h3>📍 Real-time Location</h3>
          <span class="live-badge">LIVE</span>
        </div>

        <div class="track-map" id="liveMap">
          <div class="map-grid"></div>
          
          <!-- Route Line -->
          <div class="route-line">
            <svg>
              <path d="M 15% 40% Q 40% 35%, 65% 45% T 75% 60%" class="route-path" />
            </svg>
          </div>

          <!-- Pickup Pin -->
          <div class="map-pin pin-pickup" style="left: 15%; top: 40%;">
            <span>🏠</span>
          </div>

          <!-- Dropoff Pin -->
          <div class="map-pin pin-dropoff" style="left: 75%; top: 60%;">
            <span>📍</span>
          </div>

          <!-- Rider Marker (will be positioned dynamically) -->
          <div class="rider-marker" id="riderMarker" style="left: 30%; top: 42%;">
            <span>🚴</span>
          </div>
        </div>

        <!-- Map Legend -->
        <div class="map-legend">
          <div class="legend-item"><span class="legend-dot dot-pickup"></span> Pickup</div>
          <div class="legend-item"><span class="legend-dot dot-rider"></span> Rider</div>
          <div class="legend-item"><span class="legend-dot dot-dropoff"></span> Dropoff</div>
        </div>
      </div>

      <!-- Delivery Info Grid -->
      <div class="info-grid">
        <div class="info-card">
          <div class="info-label">Status</div>
          <div class="info-value" id="modalStatus">Preparing</div>
          <div class="info-small" id="modalLastUpdate">Last update: —</div>
        </div>

        <div class="info-card">
          <div class="info-label">Estimated Arrival</div>
          <div class="info-value" id="modalEta">45 mins</div>
          <div class="info-small" id="modalDistance">4.2 km away</div>
        </div>
      </div>

      <!-- Progress Timeline -->
      <div class="timeline">
        <h4>Delivery Progress</h4>
        <div class="timeline-steps">
          <div class="step" id="stepPreparing">
            <div class="step-marker">1</div>
            <div class="step-label">Preparing</div>
            <div class="step-time" id="timePreparing">—</div>
          </div>
          <div class="step" id="stepOut">
            <div class="step-marker">2</div>
            <div class="step-label">Out for Delivery</div>
            <div class="step-time" id="timeOut">—</div>
          </div>
          <div class="step" id="stepDelivered">
            <div class="step-marker">3</div>
            <div class="step-label">Delivered</div>
            <div class="step-time" id="timeDelivered">—</div>
          </div>
        </div>
      </div>

      <!-- Courier Info -->
      <div class="courier-info" id="courierInfo">
        <div class="courier-avatar">👨</div>
        <div class="courier-details">
          <div class="courier-name" id="riderName">Juan Dela Cruz</div>
          <div class="courier-contact" id="riderPhone">+63 912 345 6789</div>
          <div class="courier-rating">
            <span>★★★★★</span> <span style="opacity:0.8;">4.9</span>
          </div>
        </div>
      </div>

      <!-- Tracking Reference -->
      <div class="tracking-ref" id="trackingRef">
        <strong>Tracking Ref:</strong> 
        <span id="refNumber">LML-1234-5678</span>
        <button class="copy-btn" onclick="copyTrackingRef()" title="Copy tracking number">📋</button>
      </div>
    </div>
  </div>
</div>

<!-- Logout modal -->
<div class="logout-overlay" id="logoutModal">
  <div class="logout-modal">
    <div class="logout-icon">⏻</div>
    <h3>Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="logout-actions">
      <button class="btn-cancel" onclick="closeLogout()">Cancel</button>
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </div>
</div>

<script>

  // Check if there's a pending payment when this page loads
document.addEventListener('DOMContentLoaded', function() {
    // Check URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const payment = urlParams.get('payment');
    const orderId = urlParams.get('order_id');
    
    if (payment === 'success' && orderId) {
        // Show a success message (your PHP already handles this)
        console.log('Payment successful for order:', orderId);
    }
    
    // Look for any unpaid Paymongo orders and show a reminder
    const unpaidBadges = document.querySelectorAll('.payment-badge.unpaid');
    unpaidBadges.forEach(badge => {
        // Find the parent order card
        const orderCard = badge.closest('.order-card');
        if (orderCard) {
            // Add a reminder button
            const actionsDiv = orderCard.querySelector('.actions');
            if (actionsDiv && !actionsDiv.querySelector('.complete-payment-btn')) {
                const orderIdElement = orderCard.querySelector('[data-order-id]');
                if (orderIdElement) {
                    const orderId = orderIdElement.getAttribute('data-order-id');
                    const reminderBtn = document.createElement('a');
                    reminderBtn.href = 'paymongo_payment.php?order_id=' + orderId;
                    reminderBtn.className = 'btn btn-approve';
                    reminderBtn.style.marginRight = '10px';
                    reminderBtn.innerHTML = '<span>💰</span> Complete Payment';
                    actionsDiv.insertBefore(reminderBtn, actionsDiv.firstChild);
                }
            }
        }
    });
});
// Logout functions
function openLogout() {
  document.getElementById("logoutModal").style.display = "flex";
}
function closeLogout() {
  document.getElementById("logoutModal").style.display = "none";
}

// Tracking Modal Functions
const trackingModal = document.getElementById("trackingModal");
let riderInterval = null;

function openTrackingModal(orderId, shipStatus, shipUpdatedAt, fulfillment, dropoffAddress, provider, trackingRef) {
  // Set basic info
  document.getElementById("modalOrderId").textContent = `Order #${orderId}`;
  document.getElementById("modalStatus").textContent = formatStatus(shipStatus);
  document.getElementById("modalLastUpdate").textContent = `Last update: ${formatDateTime(shipUpdatedAt)}`;
  
  // Set tracking ref
  document.getElementById("refNumber").textContent = trackingRef || 'LML-' + Math.floor(Math.random() * 10000) + '-' + Math.floor(Math.random() * 10000);
  
  // Set courier info based on provider
  const courierName = document.getElementById("riderName");
  const courierPhone = document.getElementById("riderPhone");
  
  if (provider && provider.toLowerCase().includes('lalamove')) {
    courierName.textContent = "Lalamove Rider";
    courierPhone.textContent = "Assigned shortly";
  } else if (provider) {
    courierName.textContent = provider + " Rider";
    courierPhone.textContent = "Contact via app";
  } else {
    courierName.textContent = "Rider being assigned";
    courierPhone.textContent = "Will update soon";
  }
  
  // Set ETA and distance based on status
  const etaEl = document.getElementById("modalEta");
  const distanceEl = document.getElementById("modalDistance");
  
  if (shipStatus === 'delivered') {
    etaEl.textContent = "Delivered";
    distanceEl.textContent = "Arrived";
  } else if (shipStatus === 'out_for_delivery') {
    const eta = Math.floor(Math.random() * 25) + 15; // 15-40 mins
    etaEl.textContent = eta + " mins";
    distanceEl.textContent = (Math.random() * 5 + 1).toFixed(1) + " km away";
  } else {
    etaEl.textContent = "Preparing";
    distanceEl.textContent = "—";
  }
  
  // Update timeline
  updateTimeline(shipStatus, shipUpdatedAt);
  
  // Position rider marker based on status
  positionRiderMarker(shipStatus);
  
  // Show modal
  trackingModal.style.display = "flex";
}

function closeTrackingModal() {
  trackingModal.style.display = "none";
  if (riderInterval) {
    clearInterval(riderInterval);
    riderInterval = null;
  }
}

function formatStatus(status) {
  status = (status || 'preparing').toLowerCase();
  if (status === 'out_for_delivery') return 'Out for Delivery';
  if (status === 'delivered') return 'Delivered';
  return 'Preparing';
}

function formatDateTime(dtStr) {
  if (!dtStr) return "—";
  const d = new Date(dtStr.replace(' ', 'T'));
  if (isNaN(d.getTime())) return dtStr;
  return d.toLocaleString();
}

function updateTimeline(status, updatedAt) {
  // Reset all steps
  const steps = ['stepPreparing', 'stepOut', 'stepDelivered'];
  steps.forEach(id => {
    document.getElementById(id).classList.remove('active', 'done');
  });
  
  // Set times
  const baseTime = updatedAt ? new Date(updatedAt.replace(' ', 'T')) : new Date();
  const preparingTime = new Date(baseTime.getTime() - 45 * 60000);
  const outTime = new Date(baseTime.getTime() - 15 * 60000);
  
  document.getElementById("timePreparing").textContent = formatTime(preparingTime);
  
  if (status === 'preparing') {
    document.getElementById("stepPreparing").classList.add('active');
  } else if (status === 'out_for_delivery') {
    document.getElementById("stepPreparing").classList.add('done');
    document.getElementById("stepOut").classList.add('active');
    document.getElementById("timeOut").textContent = formatTime(outTime);
  } else if (status === 'delivered') {
    document.getElementById("stepPreparing").classList.add('done');
    document.getElementById("stepOut").classList.add('done');
    document.getElementById("stepDelivered").classList.add('active');
    document.getElementById("timeOut").textContent = formatTime(outTime);
    document.getElementById("timeDelivered").textContent = formatTime(baseTime);
  }
}

function formatTime(date) {
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function positionRiderMarker(status) {
  const marker = document.getElementById("riderMarker");
  
  // Initial position based on status
  let left = 15, top = 40; // Start at pickup
  
  if (status === 'out_for_delivery') {
    left = 45;
    top = 45;
    
    // Animate rider moving toward dropoff
    if (riderInterval) clearInterval(riderInterval);
    riderInterval = setInterval(() => {
      left += (Math.random() * 3);
      top += (Math.random() * 2 - 1);
      
      // Cap at dropoff area
      if (left > 70) left = 70;
      if (top > 58) top = 58;
      if (top < 42) top = 42;
      
      marker.style.left = left + "%";
      marker.style.top = top + "%";
    }, 1500);
    
  } else if (status === 'delivered') {
    left = 75;
    top = 60;
  }
  
  marker.style.left = left + "%";
  marker.style.top = top + "%";
}

function copyTrackingRef() {
  const ref = document.getElementById("refNumber").textContent;
  navigator.clipboard.writeText(ref).then(() => {
    alert("Tracking number copied!");
  });
}

// Close modal when clicking outside
trackingModal.addEventListener("click", (e) => {
  if (e.target === trackingModal) closeTrackingModal();
});

// ESC key to close
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && trackingModal.style.display === "flex") {
    closeTrackingModal();
  }
});

</script>
</body>
</html>