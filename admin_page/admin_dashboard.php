<?php
session_start();
require_once "../db_connection.php";

// Only admin can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}

$adminName = htmlspecialchars($_SESSION['username']);

/* -----------------------------
   TOP SUMMARY COUNTS
------------------------------*/
// Total Farmers
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role='farmer' AND status='active'");
$stmt->execute();
$totalFarmers = ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

// Total Buyers
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role='buyer'");
$stmt->execute();
$totalBuyers = ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

// Active Users
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE status='active'");
$stmt->execute();
$activeUsers = ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

/* -----------------------------
   REGISTRATION STATUS COUNTS
   (for bar chart)
------------------------------*/
$pendingCount  = 0;
$activeCount   = 0;
$disabledCount = 0;

// If your system only uses: pending, active, archived — this still works.
// If you also use "disabled", it will count it too.
$stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) AS pending_cnt,
        SUM(CASE WHEN status='active'   THEN 1 ELSE 0 END) AS active_cnt,
        SUM(CASE WHEN status='disabled' THEN 1 ELSE 0 END) AS disabled_cnt
    FROM users
");
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$pendingCount  = (int)($row['pending_cnt']  ?? 0);
$activeCount   = (int)($row['active_cnt']   ?? 0);
$disabledCount = (int)($row['disabled_cnt'] ?? 0);

/* -----------------------------
   USER ROLE COUNTS (for list)
------------------------------*/
$adminCount = 0;
$farmerCount = 0;
$buyerCount = 0;

$stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN role='admin' AND status='active' THEN 1 ELSE 0 END) AS admin_cnt,
        SUM(CASE WHEN role='farmer' AND status='active' THEN 1 ELSE 0 END) AS farmer_cnt,
        SUM(CASE WHEN role='buyer' AND status='active' THEN 1 ELSE 0 END) AS buyer_cnt
    FROM users
");
$stmt->execute();
$roleRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$adminCount  = (int)($roleRow['admin_cnt']  ?? 0);
$farmerCount = (int)($roleRow['farmer_cnt'] ?? 0);
$buyerCount  = (int)($roleRow['buyer_cnt']  ?? 0);

// For safe percentages
$totalRoleUsers = max(1, ($adminCount + $farmerCount + $buyerCount));
$adminPct  = round(($adminCount  / $totalRoleUsers) * 100);
$farmerPct = round(($farmerCount / $totalRoleUsers) * 100);
$buyerPct  = round(($buyerCount  / $totalRoleUsers) * 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="css/admin_dashboard.css">
<link rel="stylesheet" href="css/mobileview.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="sidebar">
    <h2 class="logo">🌿 PLAMAL</h2>

    <div class="farmer-info">
        <img src="images/icon.png" alt="Admin Avatar" class="avatar">
        <p class="farmer-name">Hello, <?php echo $adminName; ?></p>
    </div>

    <nav class="menu">
        <a href="admin_dashboard.php" class="menu-item active">🏚️ Dashboard</a>
        <a href="user.php" class="menu-item">👥 Manage Users</a>
        <a href="registration.php" class="menu-item">📃 User Registration</a>
        <a href="farmers_list.php" class="menu-item">👩‍🌾 Farmers</a>
        <a href="logout.php" class="menu-item logout">🚪 Logout</a>
    </nav>
</div>

<div class="content">
    <h1>Welcome Back, <?php echo $adminName; ?>!</h1>
    <p>Here is a quick overview of user statistics.</p>

    <div class="dashboard-boxes">
        <div class="box">
            <h3>Total Farmers</h3>
            <p><?php echo $totalFarmers; ?></p>
        </div>

        <div class="box">
            <h3>Total Buyers</h3>
            <p><?php echo $totalBuyers; ?></p>
        </div>

        <div class="box">
            <h3>Active Users</h3>
            <p><?php echo $activeUsers; ?></p>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-head">
                <h3>Registration Status Distribution</h3>
                <p>Overview of all registration submissions across the platform</p>
            </div>
            <div class="chart-wrap">
                <canvas id="regStatusChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-head">
                <h3>Users by Role</h3>
                <p>List of different users across the platform</p>
            </div>

            <div class="role-list">
                <div class="role-item">
                    <div class="role-left">
                        <span class="dot dot-admin"></span>
                        <span class="role-name">Admin</span>
                    </div>
                    <div class="role-right">
                        <span class="role-count"><?php echo $adminCount; ?></span>
                        <span class="role-pct"><?php echo $adminPct; ?>%</span>
                    </div>
                </div>

                <div class="role-item">
                    <div class="role-left">
                        <span class="dot dot-farmer"></span>
                        <span class="role-name">Farmer</span>
                    </div>
                    <div class="role-right">
                        <span class="role-count"><?php echo $farmerCount; ?></span>
                        <span class="role-pct"><?php echo $farmerPct; ?>%</span>
                    </div>
                </div>

                <div class="role-item">
                    <div class="role-left">
                        <span class="dot dot-buyer"></span>
                        <span class="role-name">Buyer</span>
                    </div>
                    <div class="role-right">
                        <span class="role-count"><?php echo $buyerCount; ?></span>
                        <span class="role-pct"><?php echo $buyerPct; ?>%</span>
                    </div>
                </div>

                <div class="role-footnote">
                    Total users counted: <?php echo ($adminCount + $farmerCount + $buyerCount); ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const regCounts = {
  pending:  <?php echo (int)$pendingCount; ?>,
  active:   <?php echo (int)$activeCount; ?>,
  disabled: <?php echo (int)$disabledCount; ?>
};

const labels = ["Pending", "Active", "Disabled"];
const dataValues = [regCounts.pending, regCounts.active, regCounts.disabled];

const ctx = document.getElementById("regStatusChart").getContext("2d");
new Chart(ctx, {
  type: "bar",
  data: {
    labels: labels,
    datasets: [{
      label: "Users",
      data: dataValues,
      borderRadius: 10,
      backgroundColor: [
        "rgba(255, 193, 7, 0.9)",   // Pending
        "rgba(76, 175, 80, 0.9)",   // Active
        "rgba(156, 39, 176, 0.9)"   // Disabled
      ]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    }
  }
});
</script>

</body>
</html>
