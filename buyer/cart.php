<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

$buyer_id = (int)$_SESSION['user_id'];

// Find cart_id
$stmt = $conn->prepare("SELECT id FROM carts WHERE buyer_id=? LIMIT 1");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cartRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cart_id = (int)($cartRow['id'] ?? 0);

$items = [];
$total = 0;

if ($cart_id > 0) {
  $stmt = $conn->prepare("
    SELECT 
      ci.id AS cart_item_id,
      ci.qty,
      ci.price_at_add,
      fp.id AS product_id,
      fp.product_name,
      fp.unit,
      fp.image,
      fp.quantity AS stock_available
    FROM cart_items ci
    JOIN farmer_products fp ON fp.id = ci.product_id
    WHERE ci.cart_id = ?
    ORDER BY ci.id DESC
  ");
  $stmt->bind_param("i", $cart_id);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
    $items[] = $row;

    // Only count totals if product still has stock and qty <= stock (optional)
    $stockAvail = (int)($row['stock_available'] ?? 0);
    $qty = (int)$row['qty'];
    if ($stockAvail > 0) {
      $total += $qty * ((float)$row['price_at_add']);
    }
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart</title>
  <link rel="stylesheet" href="css/cart.css">

  <style>
    .modal-overlay{
      position:fixed; inset:0; background:rgba(0,0,0,.45);
      display:none; align-items:center; justify-content:center; z-index:9999;
    }
    .modal-box{
      background:#fff; width:min(420px, 92vw); border-radius:12px;
      padding:18px; box-shadow:0 10px 30px rgba(0,0,0,.18);
    }
    .modal-box h3{ margin:0 0 10px; }
    .modal-actions{ display:flex; justify-content:flex-end; margin-top:14px; }
    .modal-ok{
      border:none; padding:10px 14px; border-radius:10px; cursor:pointer;
      background:#1b5e20; color:#fff; font-weight:600;
    }

    .stock-note{ margin-top:6px; font-size:13px; }
    .stock-ok{ color:#1b5e20; font-weight:600; }
    .stock-zero{ color:#d93025; font-weight:700; }

    .disabled-row{ opacity:.55; }
  </style>
</head>
<body>
<header><?php require 'header.php'?></header>

<div class="cart-container">
  <h1 class="cart-title">Shopping Cart</h1>

  <div class="cart-layout">

    <div class="cart-items">
      <h2 class="vendor-title">PLAMAL Marketplace</h2>

      <?php if (count($items) === 0): ?>
        <p style="padding:12px;">Your cart is empty.</p>
        <div class="cart-buttons">
          <a href="products.php" class="back-btn">← Continue Shopping</a>
        </div>
      <?php else: ?>

        <!-- Select All -->
        <div class="select-all-row">
          <input type="checkbox" id="selectAll" checked>
          <label for="selectAll">Select All</label>
        </div>

        <form method="POST" action="checkout.php" id="checkoutForm">

        <?php foreach ($items as $it): ?>
          <?php
            $img = !empty($it['image'])
              ? "../uploads/products/" . htmlspecialchars($it['image'])
              : "images/sample.jpg";

            $stockAvail = (int)($it['stock_available'] ?? 0);
            $qty = (int)$it['qty'];

            $isOut = ($stockAvail <= 0);
            $lineTotal = $qty * ((float)$it['price_at_add']);
          ?>

          <div class="cart-card <?= $isOut ? 'disabled-row' : '' ?>">

            <div class="product-info">
              <img src="<?php echo $img; ?>" class="product-img">
              <div class="product-text">
                <h3 class="product-name"><?php echo htmlspecialchars($it['product_name']); ?></h3>
                <p class="details">Unit: <?php echo htmlspecialchars($it['unit']); ?></p>

                <!-- ✅ Stock display -->
                <div class="stock-note">
                  Stock available:
                  <?php if ($isOut): ?>
                    <span class="stock-zero">0 (Out of stock)</span>
                  <?php else: ?>
                    <span class="stock-ok"><?php echo $stockAvail; ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="cart-middle">
              <label class="qty-label">Quantity</label>

              <div class="qty-row">
                <input type="hidden" name="cart_item_id_temp[<?php echo (int)$it['cart_item_id']; ?>]" value="<?php echo (int)$it['cart_item_id']; ?>">

                <!-- Disable qty change if out of stock (optional) -->
                <input
                  type="number"
                  value="<?php echo $qty; ?>"
                  class="qty-input"
                  min="1"
                  data-id="<?php echo (int)$it['cart_item_id']; ?>"
                  <?php echo $isOut ? "disabled" : ""; ?>
                >

              </div>
            </div>

            <div class="cart-right">
              <div class="cart-price">
                <p class="price-main">₱ <?php echo number_format($lineTotal, 2); ?></p>
                <p class="price-sub">₱ <?php echo number_format((float)$it['price_at_add'],2); ?> / <?php echo htmlspecialchars($it['unit']); ?></p>
              </div>

              <button type="submit" 
                      class="delete-btn"
                      title="Remove"
                      formaction="cart_remove.php"
                      formmethod="POST"
                      name="cart_item_id"
                      value="<?php echo (int)$it['cart_item_id']; ?>">
                🗑️
              </button>

              <div class="cart-check">
                <input
                  type="checkbox"
                  class="item-check"
                  name="selected_items[]"
                  value="<?php echo (int)$it['cart_item_id']; ?>"
                  data-stock="<?php echo $stockAvail; ?>"
                  <?php echo $isOut ? "disabled" : "checked"; ?>
                >
              </div>
            </div>

          </div>
        <?php endforeach; ?>

        </form>

        <div class="cart-buttons">
          <a href="products.php" class="back-btn">← Continue Shopping</a>

          <button type="submit" form="checkoutForm" class="checkout-btn" id="checkoutBtn">
            Make Purchase →
          </button>
        </div>

      <?php endif; ?>
    </div>

    <div class="cart-summary">
      <h3>Total Price</h3>
      <p class="summary-price">₱ <?php echo number_format($total, 2); ?></p>

      <div class="summary-message">
        <p>🌱 Thank you for supporting our local farmers!</p>
      </div>
    </div>

  </div>
</div>

<?php require 'footer.php'?>

<!-- ✅ Out of stock modal -->
<div class="modal-overlay" id="outStockModal">
  <div class="modal-box">
    <h3>Out of Stock</h3>
    <div id="outStockMsg">This product is out of stock.</div>
    <div class="modal-actions">
      <button class="modal-ok" type="button" onclick="closeOutStock()">OK</button>
    </div>
  </div>
</div>

<script>
const selectAll = document.getElementById('selectAll');
const itemChecks = document.querySelectorAll('.item-check');
const checkoutBtn = document.getElementById('checkoutBtn');

const modal = document.getElementById('outStockModal');
const modalMsg = document.getElementById('outStockMsg');

function openOutStock(msg){
  modalMsg.textContent = msg || "This product is out of stock.";
  modal.style.display = "flex";
}
function closeOutStock(){
  modal.style.display = "none";
}

// Select all (only affects enabled checkboxes)
if (selectAll) {
  selectAll.addEventListener('change', () => {
    itemChecks.forEach(ch => {
      if (!ch.disabled) ch.checked = selectAll.checked;
    });
  });

  itemChecks.forEach(ch => ch.addEventListener('change', () => {
    const enabled = [...itemChecks].filter(c => !c.disabled);
    const allChecked = enabled.length ? enabled.every(c => c.checked) : false;
    const noneChecked = enabled.length ? enabled.every(c => !c.checked) : true;

    selectAll.checked = allChecked;
    selectAll.indeterminate = (!allChecked && !noneChecked);
  }));
}

// If there is ANY out-of-stock item in the cart, show modal once on page load
(function(){
  const out = [...itemChecks].some(ch => (parseInt(ch.dataset.stock || "0", 10) <= 0));
  if (out) {
    openOutStock("Some items in your cart are out of stock. You can remove them or wait for restock.");
  }
})();

// Prevent checkout if somehow a 0-stock item gets selected
document.getElementById('checkoutForm')?.addEventListener('submit', function(e){
  const selected = [...itemChecks].filter(c => c.checked && !c.disabled);
  if (selected.length === 0) {
    e.preventDefault();
    openOutStock("Please select at least one in-stock item to checkout.");
    return;
  }

  const hasZero = selected.some(c => parseInt(c.dataset.stock || "0", 10) <= 0);
  if (hasZero) {
    e.preventDefault();
    openOutStock("You selected an out-of-stock item. Please unselect or remove it.");
  }
});

// close modal when clicking outside
modal?.addEventListener('click', function(e){
  if (e.target === modal) closeOutStock();
});

async function autoUpdateQty(cartItemId, qty) {
const res = await fetch("cart_auto_update.php", {
method: "POST",
headers: {"Content-Type": "application/x-www-form-urlencoded"},
body: new URLSearchParams({ cart_item_id: cartItemId, qty: qty })
});
return res.json();
}


document.querySelectorAll(".qty-input").forEach(inp => {
inp.addEventListener("change", async () => {
const id = inp.dataset.id;
const qty = parseInt(inp.value || "1", 10);


const j = await autoUpdateQty(id, qty);


if (!j.success) {
alert(j.error || "Failed to update cart.");
// if backend gave corrected qty, apply it
if (j.qty) inp.value = j.qty;
return;
}


inp.value = j.qty; // backend may clamp to stock
location.reload(); // simplest way to update totals
});
});
</script>

</body>
</html>