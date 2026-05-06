<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}
$farmerId = (int)$_SESSION['user_id'];
$farmerName = htmlspecialchars($_SESSION['username']);

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2000 || $year > 2100) $year = (int)date('Y');


$startDate = sprintf('%04d-01-01', $year);
$endDate = sprintf('%04d-01-01', $year + 1);

// Get farmer photo from farmer_details table
$stmt = $conn->prepare("SELECT photo FROM farmer_details WHERE user_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$farmerPhoto = !empty($row['photo']) ? "../uploads/" . $row['photo'] : "../images/default-avatar.png";

$stmt->close();

$stmt = $conn->prepare("SELECT * FROM farmer_products WHERE farmer_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
  SELECT so.*, fp.product_name
  FROM stock_outflows so
  JOIN farmer_products fp ON fp.id = so.product_id
  WHERE so.farmer_id = ?
    AND so.date >=?
    AND so.date < ?
  ORDER BY so.date DESC, so.created_at DESC
");
$stmt->bind_param("iss", $farmerId, $startDate, $endDate);
$stmt->execute();
$outflows = $stmt->get_result();
$stmt->close();

//Gets product from the table product_requests
$approvedStmt = $conn->prepare("
  SELECT id, rice_variety, price_per_sack, product_image
  FROM product_requests
  WHERE user_id = ? AND status = 'active'
  ORDER BY rice_variety ASC
");
$approvedStmt->bind_param("i", $farmerId);
$approvedStmt->execute();
$approvedRes = $approvedStmt->get_result();

$approvedProducts = [];
while ($row = $approvedRes->fetch_assoc()) {
  $approvedProducts[] = $row;
}
$approvedStmt->close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Stocks List</title>
<link rel="stylesheet" href="css/stocks.css">
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
        <a href="stocks.php" class="menu-item active"> <i class="fas fa-boxes"></i> Stocks</a>
        <a href="earnings.php" class="menu-item"><i class="fas fa-exchange-alt"></i> Transactions</a>
        <a href="profile.php" class="menu-item"><i class="fas fa-user-circle"></i>  Profile</a>
        <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<div class="stocks-container">
    <h1 class="page-title">Stock Management</h1>

    <div class="inflows-header">
        <h2>Stock Inflows</h2>
        <button class="add-stock-btn" onclick="openAddStock()">+ Add Stock</button>
    </div>

    <div class="stock-cards">
    <?php if ($products->num_rows === 0): ?>
        <p style="padding:20px;">No products yet. Add your first stock.</p>
    <?php else: ?>
        <?php while ($p = $products->fetch_assoc()): ?>
        <div class="stock-card" data-id="<?php echo $p['id']; ?>">
            <div class="stock-image">
            <?php 
            // Fix the image path
            $imagePath = '../images/no-image.png'; // Default image
            
            if (!empty($p['image'])) {
                $imageName = $p['image'];
                
                // Check different path formats
                if (strpos($imageName, 'products/') === 0) {
                    // Path starts with 'products/' - remove it
                    $imageName = substr($imageName, 9); // Remove 'products/'
                    $imagePath = '../uploads/products/' . $imageName;
                } elseif (strpos($imageName, '../uploads/') === 0) {
                    // Already has full path
                    $imagePath = $imageName;
                } elseif (strpos($imageName, 'uploads/') === 0) {
                    // Starts with 'uploads/'
                    $imagePath = '../' . $imageName;
                } else {
                    // Just the filename
                    $imagePath = '../uploads/products/' . $imageName;
                }
                
                // Also check if the file exists in the uploads folder directly
                if (!file_exists($imagePath) && file_exists('../uploads/' . $imageName)) {
                    $imagePath = '../uploads/' . $imageName;
                }
            }
            ?>
            <img src="<?php echo $imagePath; ?>" 
                 alt="<?php echo htmlspecialchars($p['product_name']); ?>"
                 onerror="this.src='../images/no-image.png'; this.onerror=null;">
            </div>

            <div class="stock-info">
                <h3><?php echo htmlspecialchars($p['product_name']); ?></h3>
                <p class="price">₱<?php echo number_format($p['price'],2); ?> per <?php echo htmlspecialchars($p['unit']); ?></p>

                <div class="stock-control">
                    <button class="stock-btn minus" onclick="openMinusModal(<?php echo $p['id']; ?>)">−</button>
                    <span class="stock-count" id="stock-count-<?php echo $p['id']; ?>"><?php echo (int)$p['quantity']; ?></span>
                    <span class="unit-label"> <?php echo htmlspecialchars($p['unit']); ?></span>
                    <button class="stock-btn plus" onclick="openIncreaseModal(<?php echo $p['id']; ?>)">+</button>
                </div>
            </div>
            <div class="stock-actions">
                <button class="edit-btn" onclick="openEditStock(<?php echo $p['id']; ?>)">Edit</button>
                <button class="delete-btn" onclick="deleteStock(<?php echo $p['id']; ?>)">Delete</button>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>
    </div>
    
    <div style="display:flex; align-items:center; gap:10px; margin: 18px 0 10px;">
    <h2 class="outflows-title" style="margin:0;">Stock Outflows</h2>
    <form method="GET" action="stocks.php" style="margin-left:auto;">
    <label for="year" style="margin-right:6px; font-weight:600;">Year:</label>
    <select id="year" name="year" onchange="this.form.submit()">
        <?php for ($y = 2026; $y <= 2031; $y++): ?>
        <option value="<?= $y ?>" <?= ($y === $year) ? 'selected' : '' ?>>
            <?= $y ?>
        </option>
        <?php endfor; ?>
    </select>
    </form>
    </div>

    <p style="margin:0 0 10px; color:#444;">
    Showing stock outflows for year <strong><?= (int)$year ?></strong>
    </p>
    <table class="outflow-table">
        <thead>
            <tr><th>Rice Variety</th><th>Quantity</th><th>Activity</th><th>Date</th></tr>
        </thead>
        <tbody>
            <?php if ($outflows->num_rows === 0): ?>
                <tr><td colspan="4" style="text-align:center">No outflow records.</td></tr>
            <?php else: ?>
                <?php while ($o = $outflows->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($o['product_name']); ?></td>
                        <td><?php echo (int)$o['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($o['reason']); ?></td>
                        <td><?php echo date("m/d/Y", strtotime($o['date'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-bg" id="addStockModal" style="display:none">
  <div class="modal-box">
    <h2>Add Stock</h2>

    <form id="addStockForm" action="process_add_stock.php" method="POST">
      <label>Select Approved Product</label>
      <select name="product_request_id" id="product_request_id" class="field" required>
        <option value="" disabled selected>-- Select rice variety --</option>
        <?php foreach ($approvedProducts as $ap): ?>
          <option
            value="<?= (int)$ap['id'] ?>"
            data-name="<?= htmlspecialchars($ap['rice_variety']) ?>"
            data-price="<?= htmlspecialchars($ap['price_per_sack']) ?>"
            data-image="<?= htmlspecialchars($ap['product_image'] ?? '') ?>"
          >
            <?= htmlspecialchars($ap['rice_variety']) ?> — ₱<?= number_format((float)$ap['price_per_sack'], 2) ?> / sack
          </option>
        <?php endforeach; ?>
      </select>

      <label>Quantity (sack)</label>
      <input type="number" name="quantity" class="field" min="1" required>

      <input type="hidden" name="unit" value="sack">
      <input type="hidden" name="farmer_id" value="<?php echo $farmerId; ?>">

      <label>Fulfillment Options</label>
      <div class="checkbox-row">
        <label class="checkbox-item">
          <input type="checkbox" name="fulfillment_options[]" value="pickup" checked> Pick-up
        </label>
        <label class="checkbox-item">
          <input type="checkbox" name="fulfillment_options[]" value="delivery"> Delivery
        </label>
      </div>

      <div class="modal-buttons">
        <button type="button" class="cancel-btn" onclick="closeAddStock()">Cancel</button>
        <button type="submit" class="save-btn">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal-bg" id="editStockModal" style="display:none">
    <div class="modal-box">
        <h2>Edit Stock</h2>
        <form id="editStockForm" action="process_edit_stock.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_id">
            
            <label>Product Image (leave empty to keep)</label>
            <input type="file" name="product_image" class="field" accept="image/*">

            <label>Rice Variety <span style="color: #999; font-size: 12px; margin-left: 5px;">(Cannot be changed)</span></label>
            <input type="text" name="product_name" id="edit_name" class="field" required 
                   style="background-color: #f5f5f5; cursor: not-allowed; opacity: 0.7;" disabled>

            <label>Price <span style="color: #999; font-size: 12px; margin-left: 5px;">(Cannot be changed)</span></label>
            <input type="number" name="price" id="edit_price" class="field" step="0.01" required 
                   style="background-color: #f5f5f5; cursor: not-allowed; opacity: 0.7;" disabled>

            <label>Quantity</label>
            <input type="number" name="quantity" id="edit_quantity" class="field" min="0" required>

            <label>Unit <span style="color: #999; font-size: 12px; margin-left: 5px;">(Cannot be changed)</span></label>
            <input type="text" name="unit" id="edit_unit" class="field" required 
                   style="background-color: #f5f5f5; cursor: not-allowed; opacity: 0.7;" disabled>

            <label>Fulfillment Options</label>
            <div class="checkbox-row">
                <label class="checkbox-item">
                    <input type="checkbox" id="edit_pickup" name="fulfillment_options[]" value="pickup">
                    Pick-up
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" id="edit_delivery" name="fulfillment_options[]" value="delivery">
                    Delivery
                </label>
            </div>

            <p style="font-size: 12px; color: #666; margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px;">
                <i class="fas fa-info-circle"></i> Only quantity and fulfillment options can be updated. 
                To change variety, price, or unit, please delete this product and add a new one.
            </p>

            <div class="modal-buttons">
                <button type="button" class="cancel-btn" onclick="closeEditStock()">Cancel</button>
                <button type="submit" class="save-btn">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-bg" id="changeStockModal" style="display:none">
    <div class="modal-box small-modal">
        <h3 id="changeStockTitle">Change Stock</h3>
        <form id="changeStockForm" action="process_change_stock.php" method="POST">
            <input type="hidden" name="product_id" id="change_product_id">
            <input type="hidden" name="farmer_id" value="<?php echo $farmerId; ?>">
            <label>Action</label>
            <select name="action" id="change_action" class="field" required>
                <option value="increase">Increase</option>
                <option value="decrease">Decrease</option>
            </select>

            <label>Quantity</label>
            <input type="number" name="qty" id="change_qty" class="field" min="1" required>
            
            <div id="reasonWrap">
            <label for="change_reason">Reason (for decreases)</label>
            <select name="reason" id="change_reason" class="field">
            <option value="">Select reason</option>
            <option value="Sold">Sold</option>
            <option value="Spoiled">Spoiled</option>
            <option value="Personal Use">Personal Use</option>
            </select>
            </div>

            <label>Date</label>
            <input type="date" name="date" id="change_date" class="field" required value="<?php echo date('Y-m-d'); ?>">

            <div class="modal-buttons">
                <button type="button" class="cancel-btn" onclick="closeChangeModal()">Cancel</button>
                <button type="submit" class="save-btn">Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Stock Notice -->
<div class="modal-bg" id="errorModal" style="display:none">
  <div class="modal-box small-modal">
    <h3>Notice</h3>
    <p id="errorText"></p>
    <div class="modal-buttons">
      <button class="save-btn" onclick="closeErrorModal()">OK</button>
    </div>
  </div>
</div>


<script>
function openAddStock(){ document.getElementById('addStockModal').style.display='flex'; }
function closeAddStock(){ document.getElementById('addStockModal').style.display='none'; }
function openEditStock(id){
    fetch('process_get_product.php?id=' + encodeURIComponent(id))
    .then(r=>r.json())
    .then(data=>{
        if (!data.success) return alert(data.error || 'Failed to load product.');
        const p = data.product;
        const opt = (p.fulfillment_options || '').split(',');
        document.getElementById('edit_id').value = p.id;
        document.getElementById('edit_name').value = p.product_name;
        document.getElementById('edit_price').value = p.price;
        document.getElementById('edit_quantity').value = p.quantity;
        document.getElementById('edit_unit').value = p.unit;
        document.getElementById('editStockModal').style.display='flex';
        document.getElementById('edit_pickup').checked = opt.includes('pickup');
        document.getElementById('edit_delivery').checked = opt.includes('delivery');
    }).catch(e=>alert('Network error'));
}
function closeEditStock(){ document.getElementById('editStockModal').style.display='none'; }

function openIncreaseModal(id){
document.getElementById('change_product_id').value = id;
document.getElementById('change_action').value = 'increase';
document.getElementById('changeStockTitle').innerText = 'Increase Stock';

updateChangeForm();
document.getElementById('changeStockModal').style.display='flex';

}

function openMinusModal(id){
document.getElementById('change_product_id').value = id;
document.getElementById('change_action').value = 'decrease';
document.getElementById('changeStockTitle').innerText = 'Deduct Stock';

updateChangeForm();
document.getElementById('changeStockModal').style.display='flex';

}

function closeErrorModal(){
    document.getElementById("errorModal").style.display = "none";
    window.history.replaceState({}, document.title, "stocks.php");
}

function closeChangeModal(){ document.getElementById('changeStockModal').style.display='none'; }

function updateChangeForm(){
  const actionEl = document.getElementById('change_action');
  const wrap = document.getElementById('reasonWrap');
  const reasonEl = document.getElementById('change_reason');

  if (!actionEl || !wrap || !reasonEl) return;

  if (actionEl.value === 'decrease') {
    wrap.style.display = '';
    reasonEl.disabled = false;
    reasonEl.required = true;
  } else {
    wrap.style.display = 'none';
    reasonEl.value = '';
    reasonEl.disabled = true;
    reasonEl.required = false;
  }
}

document.getElementById('change_action')?.addEventListener('change', function () {
  document.getElementById('changeStockTitle').innerText =
    (this.value === 'decrease') ? 'Deduct Stock' : 'Increase Stock';

  updateChangeForm();
});
updateChangeForm();

function deleteStock(id){
    if (!confirm('Delete this product? This will remove all history for this product.')) return;
    fetch('process_delete_stock.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id)
    }).then(r=>r.json()).then(j=>{
        if (j.success) location.reload();
        else alert(j.error || 'Delete failed');
    }).catch(()=>alert('Network error'));
}

function setCardCount(productId, newQty){
    const el = document.getElementById('stock-count-' + productId);
    if(el) el.textContent = newQty;
}

document.addEventListener('click', function(e){
    if (e.target.classList.contains('modal-bg')) e.target.style.display='none';
});

 function toggleTopbar() {
  document.querySelector('.sidebar').classList.toggle('active');
                    }
</script>

<!-- Warning Message -->
<?php if (isset($_GET['error'])): ?>
<script>
    document.getElementById("errorText").innerText =
        "<?php echo htmlspecialchars($_GET['error'], ENT_QUOTES); ?>";
    document.getElementById("errorModal").style.display = "flex";
</script>
<?php endif; ?>

</body>
</html>
