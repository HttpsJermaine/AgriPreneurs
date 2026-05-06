<?php
session_start();
require_once "../db_connection.php";

// Only Farmers can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}

$farmerId   = (int)$_SESSION['user_id'];
$farmerName = htmlspecialchars($_SESSION['username']);

// Year filter (2026–2031) optional for dashboard display
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2026 || $year > 2031) $year = 2026;

$startDate = sprintf('%04d-01-01', $year);
$endDate   = sprintf('%04d-01-01', $year + 1);

// Get farmer photo from farmer_details table
$stmt = $conn->prepare("SELECT photo FROM farmer_details WHERE user_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$farmerPhoto = !empty($row['photo']) ? "../uploads/" . $row['photo'] : "../images/default-avatar.png";

$stmt->close();

// 1) Total Products of farmer
$stmt = $conn->prepare("SELECT COUNT(*) AS total_products FROM farmer_products WHERE farmer_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$totalProducts = (int)($stmt->get_result()->fetch_assoc()['total_products'] ?? 0);
$stmt->close();

// 2) Pending Orders of farmer (based on orders+order_items+farmer_products)
$pendingOrders = 0;
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id) AS pending_orders
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN farmer_products fp ON fp.id = oi.product_id
    WHERE fp.farmer_id = ?
      AND o.status = 'pending'
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$pendingOrders = (int)($stmt->get_result()->fetch_assoc()['pending_orders'] ?? 0);
$stmt->close();

// 3) Manual Transactions Summary (Income/Expense)
$stmt = $conn->prepare("
    SELECT
      COALESCE(SUM(CASE WHEN type='Income' THEN amount ELSE 0 END),0) AS total_income,
      COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END),0) AS total_expense
    FROM farmer_transactions
    WHERE farmer_id = ?
      AND date >= ?
      AND date < ?
");
$stmt->bind_param("iss", $farmerId, $startDate, $endDate);
$stmt->execute();
$sumRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalIncomeManual = (float)($sumRow['total_income'] ?? 0);
$totalExpense      = (float)($sumRow['total_expense'] ?? 0);

// 4) Marketplace Income from completed orders
$marketIncome = 0.0;
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(oi.qty * oi.price), 0) AS market_income
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN farmer_products fp ON fp.id = oi.product_id
    WHERE fp.farmer_id = ?
      AND o.status = 'completed'
      AND o.created_at >= ?
      AND o.created_at < ?
");
$stmt->bind_param("iss", $farmerId, $startDate, $endDate);
$stmt->execute();
$marketIncome = (float)($stmt->get_result()->fetch_assoc()['market_income'] ?? 0);
$stmt->close();

// FINAL: Net Result (This becomes dashboard "Income")
$totalIncome = $totalIncomeManual + $marketIncome;
$netResult   = $totalIncome - $totalExpense;

// 5) Order Status Breakdown (for dashboard cards)
$ordersPending = 0;
$ordersCompleted = 0;
$ordersCancelled = 0;
$ordersDeclined = 0;

$stmt = $conn->prepare("
    SELECT
      SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) AS pending_cnt,
      SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed_cnt,
      SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled_cnt,
      SUM(CASE WHEN status='declined'  THEN 1 ELSE 0 END) AS declined_cnt
    FROM orders
    WHERE farmer_id = ?
      AND created_at >= ?
      AND created_at < ?
");
$stmt->bind_param("iss", $farmerId, $startDate, $endDate);
$stmt->execute();
$or = $stmt->get_result()->fetch_assoc();
$stmt->close();

$ordersPending   = (int)($or['pending_cnt'] ?? 0);
$ordersCompleted = (int)($or['completed_cnt'] ?? 0);
$ordersCancelled = (int)($or['cancelled_cnt'] ?? 0);
$ordersDeclined  = (int)($or['declined_cnt'] ?? 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farmer Dashboard</title>
<link rel="stylesheet" href="css/farmers_dashboard.css">
<link rel="stylesheet" href="css/mobileview.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="farmer_dashboard.php" class="menu-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="crops.php" class="menu-item"> <i class="fas fa-calendar-check"></i> Plans</a>
        <a href="orders.php" class="menu-item"> <i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="stocks.php" class="menu-item"> <i class="fas fa-boxes"></i> Stocks</a>
        <a href="earnings.php" class="menu-item"><i class="fas fa-exchange-alt"></i> Transactions</a>
        <a href="profile.php" class="menu-item"><i class="fas fa-user-circle"></i>  Profile</a>
        <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<div class="content">
    <h1>Welcome Back, <?php echo $farmerName; ?>!</h1>
    <p></p>

    <div class="dashboard-boxes">
        <div class="box">
            <h3>Total Products</h3>
            <p><?php echo $totalProducts; ?></p>
        </div>

        <div class="box">
            <h3>Pending Orders</h3>
            <p><?php echo $pendingOrders; ?></p>
        </div>

        <div class="box">
            <h3>Income</h3>
            <p>₱ <?php echo number_format($netResult, 2); ?></p>
        </div>
    </div>

    <div class="dash-panels">

    <div class="dash-panel">
    <div class="panel-head">
        <h3>💹 Earnings Breakdown</h3>
        <p>Manual transactions vs Marketplace transactions (<?php echo (int)$year; ?>)</p>
    </div>

    <div class="panel-canvas">
        <canvas id="earningsBreakdownChart"></canvas>
    </div>
    </div>

    <div class="dash-panel">
    <div class="panel-head">
        <h3>📦 Order Status Breakdown</h3>
        <p>Detailed breakdown of order statuses (<?php echo (int)$year; ?>)</p>
    </div>

    <div class="status-grid">
        <div class="status-box s-completed">
        <div class="status-icon">✅</div>
        <div class="status-num"><?php echo (int)$ordersCompleted; ?></div>
        <div class="status-label">Completed</div>
        </div>

        <div class="status-box s-pending">
        <div class="status-icon">🕒</div>
        <div class="status-num"><?php echo (int)$ordersPending; ?></div>
        <div class="status-label">Pending</div>
        </div>

        <div class="status-box s-cancelled">
        <div class="status-icon">❌</div>
        <div class="status-num"><?php echo (int)$ordersCancelled; ?></div>
        <div class="status-label">Cancelled</div>
        </div>
        
        <!--
        <div class="status-box s-declined">
        <div class="status-icon">⚠️</div>
        <div class="status-num"><?php echo (int)$ordersDeclined; ?></div>
        <div class="status-label">Declined</div> -->
        </div>
    </div>
    </div>

    </div>

    <script>
document.addEventListener("DOMContentLoaded", () => {
  const el = document.getElementById("earningsBreakdownChart");
  if (!el) {
    console.log("Canvas not found: earningsBreakdownChart");
    return;
  }

  const manualIncome = <?php echo (float)$totalIncomeManual; ?>;
  const marketIncome = <?php echo (float)$marketIncome; ?>;

  new Chart(el, {
    type: "bar",
    data: {
      labels: ["Manual Transactions", "Marketplace Transactions"],
      datasets: [{
        label: "Income (₱)",
        data: [manualIncome, marketIncome],
        borderRadius: 10,
        backgroundColor: [
          "rgba(46, 158, 91, 0.85)",
          "rgba(37, 99, 235, 0.80)"
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "bottom" },
        tooltip: {
          callbacks: {
            label: (ctx) =>
              "Income (₱): ₱" + Number(ctx.raw).toLocaleString(undefined, { minimumFractionDigits: 2 })
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (v) => "₱" + Number(v).toLocaleString()
          }
        }
      }
    }
  });
});

 function toggleTopbar() {
  document.querySelector('.sidebar').classList.toggle('active');
                    }

</script>

    
    </div>

</body>
</html>