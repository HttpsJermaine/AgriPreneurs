<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "farmer") {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}

$farmerId = (int)$_SESSION['user_id'];
$farmerName = htmlspecialchars($_SESSION['username']);

// Get farmer photo from farmer_details table
$stmt = $conn->prepare("SELECT photo FROM farmer_details WHERE user_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$farmerPhoto = !empty($row['photo']) ? "../uploads/" . $row['photo'] : "../images/default-avatar.png";

$stmt->close();

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2026 || $year > 2031) $year = 2026;

$startDate = sprintf('%04d-01-01', $year);
$endDate   = sprintf('%04d-01-01', $year + 1);

$stmt = $conn->prepare("
    SELECT *
    FROM farmer_transactions
    WHERE farmer_id = ?
      AND date >= ?
      AND date < ?
    ORDER BY date DESC, id DESC
");
$stmt->bind_param("iss", $farmerId, $startDate, $endDate);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

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

$totalIncomeManual  = (float)$sumRow['total_income'];
$totalExpense       = (float)$sumRow['total_expense'];

$marketIncome = 0.0;
$marketSql = "
    SELECT COALESCE(SUM(oi.qty * oi.price), 0) AS market_income
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN farmer_products fp ON fp.id = oi.product_id
    WHERE fp.farmer_id = ?
      AND o.status = 'completed'
      AND o.created_at >= ?
      AND o.created_at < ?
";
$stmt = $conn->prepare($marketSql);
if ($stmt) {
    $stmt->bind_param("iss", $farmerId, $startDate, $endDate);
    $stmt->execute();
    $marketRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $marketIncome = (float)($marketRow['market_income'] ?? 0);
}

$totalIncome = $totalIncomeManual + $marketIncome;
$netResult   = $totalIncome - $totalExpense;
$stmt = $conn->prepare("
    SELECT *
    FROM farmer_transactions
    WHERE farmer_id = ?
      AND date >= ?
      AND date < ?
    ORDER BY date DESC, id DESC
");
$stmt->bind_param("iss", $farmerId, $startDate, $endDate);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

// Marketplace transactions 
$mpStmt = $conn->prepare("
    SELECT order_id, amount, description, txn_date
    FROM marketplace_transactions
    WHERE farmer_id = ?
      AND txn_date >= ?
      AND txn_date < ?
    ORDER BY txn_date DESC, id DESC
");
$mpStmt->bind_param("iss", $farmerId, $startDate, $endDate);
$mpStmt->execute();
$marketRows = $mpStmt->get_result();
$mpStmt->close();

// Marketplace income total
$mpSumStmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS market_income
    FROM marketplace_transactions
    WHERE farmer_id = ?
      AND txn_date >= ?
      AND txn_date < ?
");
$mpSumStmt->bind_param("iss", $farmerId, $startDate, $endDate);
$mpSumStmt->execute();
$marketIncome = (float)($mpSumStmt->get_result()->fetch_assoc()['market_income'] ?? 0);
$mpSumStmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings</title>
    <link rel="stylesheet" href="css/earnings.css">
    <link rel="stylesheet" href="css/mobileview.css">
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
        <a href="farmer_dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="crops.php" class="menu-item"> <i class="fas fa-calendar-check"></i> Plans</a>
        <a href="orders.php" class="menu-item"> <i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="stocks.php" class="menu-item"> <i class="fas fa-boxes"></i> Stocks</a>
        <a href="earnings.php" class="menu-item active"><i class="fas fa-exchange-alt"></i> Transactions</a>
        <a href="profile.php" class="menu-item"><i class="fas fa-user-circle"></i>  Profile</a>
        <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<div class="earnings-container">

    <div class="topbar">
        <h1 class="page-title">Finance Dashboard</h1>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Manual Transactions (<?php echo (int)$year; ?>)</h2>

            <div class="right-controls" style="display:flex; align-items:center; gap:12px;">
                <form method="GET" action="earnings.php" style="margin:0;">
                    <label for="year" style="font-weight:600; margin-right:6px;">Year:</label>
                    <select id="year" name="year" onchange="this.form.submit()">
                        <?php for ($y = 2026; $y <= 2031; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($y === $year) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </form>

                <div style="font-weight: 600;">
                    Currency: <span style="color:#1a7f37;">₱ PHP</span>
                </div>

                <button class="add-btn" onclick="openModal()">+ Add Transaction</button>
            </div>
        </div>
        
        <div class="table-scroll">
        <table class="earnings-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($transactions->num_rows === 0): ?>
                    <tr><td colspan="5" style="text-align:center;">No transactions yet for this year.</td></tr>
                <?php else: ?>
                    <?php while ($row = $transactions->fetch_assoc()): ?>
                        <tr class="<?php echo strtolower($row['type']); ?>">
                            <td><?php echo date("M d, Y", strtotime($row['date'])); ?></td>
                            <td><?php echo $row['type']; ?></td>
                            <td>₱<?php echo number_format((float)$row['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <!--<button class="edit-btn"
                                    onclick="openEditModal(
                                        <?php echo (int)$row['id']; ?>,
                                        '<?php echo $row['type']; ?>',
                                        '<?php echo (float)$row['amount']; ?>',
                                        '<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>',
                                        '<?php echo $row['date']; ?>'
                                    )">
                                    Edit
                                </button>-->

                                <form action="process_delete_transaction.php" method="POST" onsubmit="return confirm('Do you want to delete this transaction?');" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Marketplace Transactions Panel -->
    <div class="panel" style="margin-top: 22px;">
        <div class="panel-header">
            <h2>Marketplace Transactions (<?php echo (int)$year; ?>)</h2>
            <div class="mp-note"></div>
        </div>
        
        <div class="table-scroll">
        <table class="earnings-table marketplace-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <!--<th>Order ID</th>-->
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($marketRows->num_rows === 0): ?>
                    <tr><td colspan="4" style="text-align:center;">No marketplace transactions yet for this year.</td></tr>
                <?php else: ?>
                    <?php while ($m = $marketRows->fetch_assoc()): ?>
                        <tr class="income">
                            <td><?php echo date("M d, Y", strtotime($m['txn_date'])); ?></td>
                            <!--<td>#<?php echo (int)$m['order_id']; ?></td>-->
                            <td>₱<?php echo number_format((float)$m['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($m['description']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>


    <div class="quarterly-summary-container">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <h2 style="margin:0;">Financial Summary for <?php echo (int)$year; ?></h2>
        </div>

        <div class="summary-panel">
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Total Income</th>
                        <th>Total Expense</th>
                        <th>Net Result</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>
                            ₱<?php echo number_format($totalIncome, 2); ?>
                            <div style="font-size:12px; color:#555; margin-top:4px;">
                                Manual Transactions: ₱<?php echo number_format($totalIncomeManual, 2); ?><br>
                                Marketplace Transactions: ₱<?php echo number_format($marketIncome, 2); ?>
                            </div>
                        </td>
                        <td>₱<?php echo number_format($totalExpense, 2); ?></td>
                        <td class="<?php echo ($netResult >= 0 ? 'net-positive' : 'net-negative'); ?>">
                            ₱<?php echo number_format($netResult, 2); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-bg" id="modalBg">
        <div class="modal-box">
            <form action="process_add_transaction.php" method="POST">
                <h2>Add Transaction</h2>

                <input type="hidden" name="farmer_id" value="<?php echo $farmerId; ?>">

                <label>Type</label>
                <select class="field" name="type" required>
                    <option value="">Select Type</option>
                    <option>Income</option>
                    <option>Expense</option>
                </select>

                <label>Amount</label>
                <input type="number" name="amount" class="field" step="0.01" required>

                <label>Description</label>
                <input type="text" name="description" class="field" required>

                <label>Date</label>
                <input type="date" name="date" class="field" required>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-bg" id="editModalBg" style="display:none;">
        <div class="modal-box">
            <form action="process_edit_transaction.php" method="POST" id="editForm">
                <h2>Edit Transaction</h2>

                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="farmer_id" value="<?php echo $farmerId; ?>">

                <label>Type</label>
                <select class="field" name="type" id="edit_type" required>
                    <option>Income</option>
                    <option>Expense</option>
                </select>

                <label>Amount</label>
                <input type="number" name="amount" id="edit_amount" class="field" required step="0.01">

                <label>Description</label>
                <input type="text" name="description" id="edit_description" class="field" required>

                <label>Date</label>
                <input type="date" name="date" id="edit_date" class="field" required>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="save-btn">Update</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function openModal() {
    document.getElementById('modalBg').style.display = "flex";
}
function closeModal() {
    document.getElementById('modalBg').style.display = "none";
}
function openEditModal(id, type, amount, description, date) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_date').value = date;

    document.getElementById('editModalBg').style.display = "flex";
}
function closeEditModal() {
    document.getElementById('editModalBg').style.display = "none";
}
 function toggleTopbar() {
  document.querySelector('.sidebar').classList.toggle('active');
                    }
</script>

</body>
</html>