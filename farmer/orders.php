<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}
$farmer_id = (int)$_SESSION['user_id'];
$farmerId = (int)$_SESSION['user_id'];
$farmerName = htmlspecialchars($_SESSION['username']);

$stmt = $conn->prepare("SELECT photo FROM farmer_details WHERE user_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$farmerPhoto = !empty($row['photo']) ? "../uploads/" . $row['photo'] : "../images/default-avatar.png";

$stmt->close();

function money($n){ return "₱" . number_format((float)$n, 2); }

/* -------------------------
   GET FILTERS (HISTORY ONLY)
--------------------------*/
$historyType = $_GET['history'] ?? 'completed';
$historyYear = $_GET['history_year'] ?? 'all';

$historyMap = [
  'completed' => 'completed',
  'cancelled' => 'cancelled',
  'declined'  => 'declined'
];
$historyStatus = $historyMap[$historyType] ?? 'completed';

/* -------------------------
   Fetch orders (NO YEAR FILTER)
--------------------------*/
function fetchOrdersNoYear($conn, $farmer_id, $status) {
  $stmt = $conn->prepare("
    SELECT
      o.*,
      bd.full_name AS buyer_name,
      bd.phone AS buyer_phone
    FROM orders o
    LEFT JOIN buyer_details bd ON bd.user_id = o.buyer_id
    WHERE o.farmer_id = ?
      AND o.status = ?
    ORDER BY o.id DESC
  ");
  $stmt->bind_param("is", $farmer_id, $status);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while($r = $res->fetch_assoc()) $rows[] = $r;
  $stmt->close();
  return $rows;
}

/* -------------------------
   Fetch history (WITH optional YEAR FILTER)
--------------------------*/
function fetchHistory($conn, $farmer_id, $status, $year) {
  if ($year === 'all') {
    $stmt = $conn->prepare("
      SELECT
        o.*,
        bd.full_name AS buyer_name,
        bd.phone AS buyer_phone
      FROM orders o
      LEFT JOIN buyer_details bd ON bd.user_id = o.buyer_id
      WHERE o.farmer_id = ?
        AND o.status = ?
      ORDER BY o.id DESC
    ");
    $stmt->bind_param("is", $farmer_id, $status);
  } else {
    $y = (int)$year;
    $stmt = $conn->prepare("
      SELECT
        o.*,
        bd.full_name AS buyer_name,
        bd.phone AS buyer_phone
      FROM orders o
      LEFT JOIN buyer_details bd ON bd.user_id = o.buyer_id
      WHERE o.farmer_id = ?
        AND o.status = ?
        AND YEAR(o.created_at) = ?
      ORDER BY o.id DESC
    ");
    $stmt->bind_param("isi", $farmer_id, $status, $y);
  }

  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while($r = $res->fetch_assoc()) $rows[] = $r;
  $stmt->close();
  return $rows;
}

/* -------------------------
   Fetch order items
--------------------------*/
function fetchItems($conn, $order_id) {
  $stmt = $conn->prepare("SELECT product_name, qty, price, unit FROM order_items WHERE order_id=?");
  $stmt->bind_param("i", $order_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $items = [];
  while($r = $res->fetch_assoc()) $items[] = $r;
  $stmt->close();
  return $items;
}

/* -------------------------
   Shipment status helper
--------------------------*/
function shipLabel($s){
  $s = strtolower($s ?? '');
  if ($s === 'preparing') return 'Preparing';
  if ($s === 'out_for_delivery') return 'Out for Delivery';
  if ($s === 'delivered') return 'Delivered';
  return 'Preparing';
}
function shipPillClass($s){
  $s = strtolower($s ?? '');
  if ($s === 'preparing') return 'pill';
  if ($s === 'out_for_delivery') return 'pill pill-yellow';
  if ($s === 'delivered') return 'pill pill-green';
  return 'pill';
}

/* -------------------------
   Payment badge helper
--------------------------*/
function paymentBadge($method, $status) {
  $method = strtolower($method ?? 'cod');
  $status = strtolower($status ?? 'unpaid');
  
  if ($method === 'paymongo' && $status === 'paid') {
    return '<span class="payment-badge paid"><i class="fas fa-check-circle"></i> Paid via GCash/Card</span>';
  } elseif ($method === 'paymongo' && $status === 'unpaid') {
    return '<span class="payment-badge pending"><i class="fas fa-clock"></i> Payment Pending</span>';
  } elseif ($method === 'cod') {
    return '<span class="payment-badge cod"><i class="fas fa-money-bill-wave"></i> Cash on Delivery</span>';
  }
  return '<span class="payment-badge">Unknown</span>';
}

/* -------------------------
   Load sections
--------------------------*/
$pendingOrders  = fetchOrdersNoYear($conn, $farmer_id, 'pending');
$approvedOrders = fetchOrdersNoYear($conn, $farmer_id, 'approved');
$historyOrders  = fetchHistory($conn, $farmer_id, $historyStatus, $historyYear);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Order Management</title>
  <link rel="stylesheet" href="css/orders.css">
  <link rel="stylesheet" href="css/mobileview.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Additional fixes for table alignment */
    .orders-table th, .orders-table td {
      text-align: left;
      vertical-align: top;
    }
    .orders-table th:last-child, .orders-table td:last-child {
      text-align: center;
    }
    .action-cell {
      display: flex;
      flex-direction: column;
      gap: 8px;
      align-items: center;
      justify-content: center;
    }
    .modal-bg {
      display: none;
    }
    .modal-bg.show {
      display: flex;
    }
  </style>
</head>
<body>

<div class="sidebar">
    
    
 <div class="mobile-topbar">
    <span class="mobile-logo">🌿 PLAMAL</span>
   <button class="mobile-toggle" onclick="toggleTopbar()">
  <i class="fas fa-bars"></i>
</button>
      </div>
    
  <h2 class="logo">🌿 PLAMAL</h2>

  <div class="farmer-info">
    <img src="<?php echo $farmerPhoto; ?>" alt="Farmers" class="avatar">
    <p class="farmer-name">Hello, <?php echo $farmerName; ?>!</p>
  </div>

  <nav class="menu">
        <a href="farmer_dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="crops.php" class="menu-item"> <i class="fas fa-calendar-check"></i> Plans</a>
        <a href="orders.php" class="menu-item active"> <i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="stocks.php" class="menu-item"> <i class="fas fa-boxes"></i> Stocks</a>
        <a href="earnings.php" class="menu-item"><i class="fas fa-exchange-alt"></i> Transactions</a>
        <a href="profile.php" class="menu-item"><i class="fas fa-user-circle"></i>  Profile</a>
        <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<div class="orders-container">

  <?php
  $flashType = $_SESSION['flash_type'] ?? '';
  $flashMsg  = $_SESSION['flash_msg'] ?? '';
  unset($_SESSION['flash_type'], $_SESSION['flash_msg']);
  ?>

  <?php if ($flashMsg !== ''): ?>
    <div class="flash-message <?= $flashType === 'success' ? 'success' : 'error' ?>">
      <?= htmlspecialchars($flashMsg) ?>
    </div>
  <?php endif; ?>

  <div class="orders-header">
    <h1>Order Management</h1>
  </div>

  <!-- PENDING -->
  <h2 class="table-title">Pending Orders</h2>
  <div class="table-responsive">
    <table class="orders-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Buyer</th>
          <th>Payment</th>
          <th>Fulfillment</th>
          <th>Items</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
      <?php if (count($pendingOrders) === 0): ?>
        <tr><td colspan="6" class="empty-message">No pending orders.</td></tr>
      <?php else: ?>
        <?php foreach($pendingOrders as $o): ?>
          <?php
            $items = fetchItems($conn, (int)$o['id']);
          ?>
          <tr>
            <td><span class="order-id"><?= (int)$o['id'] ?></span></td>

            <td class="buyer-info">
              <strong><?= htmlspecialchars($o['buyer_name'] ?? 'Buyer') ?></strong><br>
              <span class="small"><?= htmlspecialchars($o['buyer_phone'] ?? '') ?></span>
            </td>

            <td>
              <?= paymentBadge($o['payment_method'] ?? 'cod', $o['payment_status'] ?? 'unpaid') ?>
            </td>

            <td>
              <span class="pill fulfillment"><?= htmlspecialchars(strtoupper($o['fulfillment'])) ?></span>
              <?php if (($o['fulfillment'] ?? '') === 'deliver'): ?>
                <div class="delivery-address">
                  <i class="fas fa-map-marker-alt"></i>
                  <?= htmlspecialchars($o['delivery_address'] ?? 'No address') ?>
                </div>
              <?php endif; ?>
            </td>

            <td class="items-list">
            <?php foreach($items as $it): 
              $item_total = (int)$it['qty'] * (float)$it['price'];
            ?>
              <div class="item-row">
                <span class="item-name"><?= htmlspecialchars($it['product_name']) ?></span>
                <span class="item-qty"><?= (int)$it['qty'] ?> <?= htmlspecialchars($it['unit'] ?? 'sack') ?></span>
                <span class="item-price"><?= money($item_total) ?></span>
              </div>
            <?php endforeach; ?>
            </td>

            <td class="actions-col">
            <div class="action-cell">
              <form method="POST" action="orders_action.php" class="action-form">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <button type="submit" name="action" value="approve" class="btn btn-approve">
                  <i class="fas fa-check"></i> Approve
                </button>
              </form>

              <!--<button type="button" class="btn btn-decline" onclick="openDeclineModal(<?= (int)$o['id'] ?>)">
                <i class="fas fa-times"></i> Decline
              </button> -->
            </div>
          </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- APPROVED -->
  <h2 class="table-title">Approved Orders</h2>
  <div class="table-responsive">
    <table class="orders-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Buyer</th>
          <th>Payment</th>
          <th>Fulfillment</th>
          <th>Items</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
      <?php if (count($approvedOrders) === 0): ?>
        <tr><td colspan="6" class="empty-message">No approved orders.</td></tr>
      <?php else: ?>
        <?php foreach($approvedOrders as $o): ?>
          <?php
            $items = fetchItems($conn, (int)$o['id']);
            $ful = strtolower($o['fulfillment'] ?? '');

            // shipment status (deliver only)
            $shipStatus = '';
            if ($ful === 'deliver') {
              $st = $conn->prepare("SELECT status FROM shipments WHERE order_id=? ORDER BY id DESC LIMIT 1");
              $st->bind_param("i", $o['id']);
              $st->execute();
              $shipStatus = ($st->get_result()->fetch_assoc()['status'] ?? 'preparing');
              $st->close();
            }
          ?>
          <tr>
            <td><span class="order-id"><?= (int)$o['id'] ?></span></td>

            <td class="buyer-info">
              <strong><?= htmlspecialchars($o['buyer_name'] ?? 'Buyer') ?></strong><br>
              <span class="small"><?= htmlspecialchars($o['buyer_phone'] ?? '') ?></span>
            </td>

            <td>
              <?= paymentBadge($o['payment_method'] ?? 'cod', $o['payment_status'] ?? 'unpaid') ?>
            </td>

            <td>
              <span class="pill fulfillment"><?= htmlspecialchars(strtoupper($o['fulfillment'])) ?></span>

              <?php if ($ful === 'deliver'): ?>
                <span class="<?= shipPillClass($shipStatus) ?>" style="margin-left:8px;">
                  <?= htmlspecialchars(shipLabel($shipStatus)) ?>
                </span>

                <div class="delivery-address">
                  <i class="fas fa-map-marker-alt"></i>
                  <?= htmlspecialchars($o['delivery_address'] ?? 'No address') ?>
                </div>
              <?php endif; ?>
            </td>

            <td class="items-list">
            <?php foreach($items as $it): 
              $item_total = (int)$it['qty'] * (float)$it['price'];
            ?>
              <div class="item-row">
                <span class="item-name"><?= htmlspecialchars($it['product_name']) ?></span>
                <span class="item-qty"><?= (int)$it['qty'] ?> <?= htmlspecialchars($it['unit'] ?? 'sack') ?></span>
                <span class="item-price"><?= money($item_total) ?></span>
              </div>
            <?php endforeach; ?>
          </td>

            <td class="actions-col">
            <div class="action-cell">
              <?php if ($ful === 'deliver'): ?>
                <button type="button"
                        class="btn btn-tracking"
                        onclick="openTrackingModal(<?= (int)$o['id'] ?>)">
                  <i class="fas fa-truck"></i> Add Tracking
                </button>

                <form method="POST" action="orders_action.php" class="action-form">
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">

                  <?php if (strtolower($shipStatus) === 'preparing'): ?>
                    <button type="submit" name="action" value="ship_out" class="btn btn-out">
                      <i class="fas fa-shipping-fast"></i> Out for Delivery
                    </button>

                  <?php elseif (strtolower($shipStatus) === 'out_for_delivery'): ?>
                    <button type="submit" name="action" value="ship_delivered" class="btn btn-complete">
                      <i class="fas fa-check-circle"></i> Mark Delivered
                    </button>

                  <?php else: ?>
                    <span class="delivered-badge"><i class="fas fa-check"></i> Delivered</span>
                  <?php endif; ?>
                </form>

              <?php else: ?>
                <form method="POST" action="orders_action.php" class="action-form">
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <button type="submit" name="action" value="complete" class="btn btn-ready">
                    <i class="fas fa-hand-holding-heart"></i> Mark Ready
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- HISTORY -->
  <h2 class="table-title">Order History</h2>

  <form method="GET" class="history-filter">
    <div class="filter-group">
      <label>SORT BY:</label>
      <select name="history" class="filter-select">
        <option value="completed" <?= ($historyType==='completed'?'selected':'') ?>>Completed</option>
        <option value="cancelled" <?= ($historyType==='cancelled'?'selected':'') ?>>Cancelled</option>
        <option value="declined"  <?= ($historyType==='declined'?'selected':'')  ?>>Declined</option>
      </select>
    </div>

    <div class="filter-group">
      <label>YEAR:</label>
      <select name="history_year" class="filter-select">
        <option value="all" <?= ($historyYear==='all'?'selected':'') ?>>All Years</option>
        <?php
          $currentY = (int)date("Y");
          for($y = $currentY - 2; $y <= $currentY + 3; $y++):
        ?>
          <option value="<?= $y ?>" <?= (string)$historyYear === (string)$y ? 'selected' : ''?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <button type="submit" class="filter-btn">
      <i class="fas fa-filter"></i> Sort
    </button>
  </form>

  <div class="table-responsive history-table">
    <table class="orders-table">
      <thead>
        <tr>
          <th>Buyer</th>
          <th>Products</th>
          <th>Payment</th>
          <th>Fulfillment</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($historyOrders) === 0): ?>
        <tr><td colspan="5" class="empty-message">No history orders.</td></tr>
      <?php else: ?>
        <?php foreach($historyOrders as $o): ?>
          <?php
            $items = fetchItems($conn, (int)$o['id']);
          ?>
          <tr>
            <td class="buyer-info">
              <strong><?= htmlspecialchars($o['buyer_name'] ?? 'Buyer') ?></strong><br>
              <span class="small"><?= htmlspecialchars($o['buyer_phone'] ?? '') ?></span>
            </td>
            
            <td class="items-list">
            <?php foreach($items as $it): 
              $item_total = (int)$it['qty'] * (float)$it['price'];
            ?>
              <div class="item-row">
                <span class="item-name"><?= htmlspecialchars($it['product_name']) ?></span>
                <span class="item-qty"><?= (int)$it['qty'] ?> <?= htmlspecialchars($it['unit'] ?? 'sack') ?></span>
                <span class="item-price"><?= money($item_total) ?></span>
              </div>
            <?php endforeach; ?>
            </td>
      
            <td>
              <?= paymentBadge($o['payment_method'] ?? 'cod', $o['payment_status'] ?? 'unpaid') ?>
            </td>
            
            <td>
              <span class="pill fulfillment"><?= htmlspecialchars(strtoupper($o['fulfillment'])) ?></span>
            </td>
            
            <td>
              <span class="status-badge status-<?= strtolower($o['status'] ?? '') ?>">
                <?= htmlspecialchars(strtoupper($o['status'] ?? '')) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Tracking Modal (Hidden by default) -->
<div class="modal-bg" id="trackingModal">
  <div class="modal-box">
    <div class="modal-header">
      <h2>Add Tracking Information</h2>
      <button type="button" class="modal-close" onclick="closeTrackingModal()">×</button>
    </div>

    <form method="POST" action="add_tracking.php" id="trackingForm">
      <input type="hidden" name="order_id" id="trackingOrderId" value="">

      <div class="form-group">
        <label class="modal-label">Courier / Provider</label>
        <select name="provider" class="modal-select" required>
          <option value="">-- Select Courier --</option>
          <option value="Lalamove">Lalamove</option>
          <option value="Toktok">Toktok</option>
          <option value="Grab">Grab</option>
          <option value="J&T">J&T</option>
          <option value="LBC">LBC</option>
          <option value="2GO">2GO</option>
        </select>
      </div>

      <div class="form-group">
        <label class="modal-label">Tracking Reference</label>
        <input type="text" name="tracking_ref" class="modal-input" placeholder="e.g. LM-123456">
      </div>

      <div class="form-group">
        <label class="modal-label">Tracking URL</label>
        <input type="url" name="tracking_url" class="modal-input" placeholder="https://...">
      </div>

      <p class="modal-note">
        <i class="fas fa-info-circle"></i> Provide at least a Tracking Ref or a Tracking URL if you already book a delivery. But for testing, you can leave it blank
      </p>

      <div class="modal-actions">
        <button type="button" class="btn btn-light" onclick="closeTrackingModal()">Cancel</button>
        <button type="submit" class="btn btn-approve">Save Tracking</button>
      </div>
    </form>
  </div>
</div>

<!-- Decline Modal (Hidden by default) -->
<div class="modal-bg" id="declineModal">
  <div class="modal-box">
    <div class="modal-header">
      <h2>Decline Order</h2>
      <button type="button" class="modal-close" onclick="closeDeclineModal()">×</button>
    </div>

    <form method="POST" action="orders_action.php" id="declineForm">
      <input type="hidden" name="action" value="decline">
      <input type="hidden" name="order_id" id="declineOrderId" value="">

      <div class="form-group">
        <label class="modal-label">Reason for Declining</label>
        <textarea
          name="reason"
          id="declineReason"
          class="modal-textarea"
          placeholder="Please provide a reason..."
          required></textarea>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-light" onclick="closeDeclineModal()">Cancel</button>
        <button type="submit" class="btn btn-decline">
          <i class="fas fa-times-circle"></i> Decline Order
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const declineModal = document.getElementById("declineModal");
const declineOrderId = document.getElementById("declineOrderId");
const declineReason = document.getElementById("declineReason");

function openDeclineModal(orderId){
  declineOrderId.value = orderId;
  declineReason.value = "";
  declineModal.classList.add("show");
  declineReason.focus();
}

function closeDeclineModal(){
  declineModal.classList.remove("show");
}

declineModal.addEventListener("click", function(e){
  if (e.target === declineModal) closeDeclineModal();
});

document.addEventListener("keydown", function(e){
  if (e.key === "Escape") closeDeclineModal();
});

//Tracking Modal
const trackingModal = document.getElementById("trackingModal");
const trackingOrderId = document.getElementById("trackingOrderId");

function openTrackingModal(orderId){
  trackingOrderId.value = orderId;
  trackingModal.classList.add("show");
}
function closeTrackingModal(){
  trackingModal.classList.remove("show");
}

trackingModal?.addEventListener("click", function(e){
  if (e.target === trackingModal) closeTrackingModal();
});
document.addEventListener("keydown", function(e){
  if (e.key === "Escape") closeTrackingModal();
});

 function toggleTopbar() {
  document.querySelector('.sidebar').classList.toggle('active');
                    }
</script>
</body>
</html>