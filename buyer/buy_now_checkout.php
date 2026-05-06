<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
  header("Location: ../login.php?error=Access denied");
  exit;
}

if (!isset($_SESSION['buy_now'])) {
  header("Location: products.php");
  exit;
}

$product_id = (int)$_SESSION['buy_now']['product_id'];
$qty = (int)$_SESSION['buy_now']['qty'];

$stmt = $conn->prepare("
  SELECT
    fp.id, fp.farmer_id, fp.product_name, fp.price, fp.unit, fp.quantity, fp.image, fp.fulfillment_options,
    fd.farmer_name, fd.phone, fd.street, fd.city, fd.province, fd.zip
  FROM farmer_products fp
  JOIN farmer_details fd ON fd.user_id = fp.farmer_id
  WHERE fp.id = ?
  LIMIT 1
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) { die("Product not found."); }
if ($qty > (int)$p['quantity']) { die("Not enough stock anymore."); }

$img = !empty($p['image'])
  ? "../uploads/products/" . htmlspecialchars($p['image'])
  : "images/sample.jpg";

$address = trim(($p['street'] ?? '') . ", " . ($p['city'] ?? '') . ", " . ($p['province'] ?? '') . " " . ($p['zip'] ?? ''));
$total = $qty * (float)$p['price'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buy Now Checkout</title>
  <link rel="stylesheet" href="css/cart.css">
</head>
<body>
<header><?php require 'header.php'?></header>

<div class="cart-container">
  <h1 class="cart-title">Buy Now</h1>

  <div class="cart-layout">
    <div class="cart-items">
      <h2 class="vendor-title">Order Details</h2>

      <div class="cart-card">
        <div class="product-info">
          <img src="<?php echo $img; ?>" class="product-img">
          <div>
            <h3 class="product-name"><?php echo htmlspecialchars($p['product_name']); ?></h3>
            <p class="details">Unit: <?php echo htmlspecialchars($p['unit']); ?></p>
            <p class="details">Fulfillment: <?php echo htmlspecialchars($p['fulfillment_options']); ?></p>
            <p class="details">Farmer: <?php echo htmlspecialchars($p['farmer_name']); ?></p>
            <p class="details">Phone: <?php echo htmlspecialchars($p['phone']); ?></p>
            <p class="details">Address: <?php echo htmlspecialchars($address); ?></p>
          </div>
        </div>

        <div class="cart-middle">
          <label class="qty-label">Quantity</label>
          <input class="qty-input" value="<?php echo (int)$qty; ?>" disabled>
        </div>

        <div class="cart-price">
          <p class="price-main">₱ <?php echo number_format($total, 2); ?></p>
          <p class="price-sub">₱ <?php echo number_format((float)$p['price'],2); ?> / <?php echo htmlspecialchars($p['unit']); ?></p>
        </div>
      </div>

      <div class="cart-buttons">
        <a href="products.php" class="back-btn">← Back to Products</a>
        <!-- next step: place order -->
        <form method="POST" action="buy_now_place_order.php" style="display:inline;">
          <button class="checkout-btn" type="submit">Place Order →</button>
        </form>
      </div>

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
</body>
</html>