<?php 
require_once "../db_connection.php";
$selected = isset($_GET['category']) ? trim($_GET['category']) : "";

$catResult = $conn->query("
  SELECT DISTINCT product_name
  FROM farmer_products
  ORDER BY product_name ASC
");

if ($selected !== "" && $selected !== "All Varieties") {

    $stmt = $conn->prepare("
      SELECT fp.*, fd.farmer_name
      FROM farmer_products fp
      JOIN farmer_details fd ON fd.user_id = fp.farmer_id
      WHERE fp.product_name = ?
        AND fp.quantity > 0
      ORDER BY fp.id DESC
    ");

    $stmt->bind_param("s", $selected);
    $stmt->execute();
    $products = $stmt->get_result();
    $stmt->close();

} else {

    $products = $conn->query("
      SELECT fp.*, fd.farmer_name
      FROM farmer_products fp
      JOIN farmer_details fd ON fd.user_id = fp.farmer_id
      WHERE fp.quantity > 0
      ORDER BY fp.id DESC
    ");
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products</title>
  <link rel="stylesheet" href="css/products.css">
  <link rel="stylesheet" href="css/mobileview.css">
  <style>
    /* Policy note styling */
    .policy-note {
      background: #fff3cd;
      border: 1px solid #ffe69c;
      color: #856404;
      padding: 12px 15px;
      border-radius: 8px;
      margin-top: 15px;
      font-size: 13px;
      line-height: 1.5;
    }
    
    .policy-note i {
      color: #856404;
      margin-right: 5px;
    }
    
    .policy-note strong {
      color: #533f03;
    }
  </style>
</head>
<body>

<header>
  <?php require 'header.php'?>
</header>
<div class="products-container">

  <aside class="filter-sidebar">
    <h3>Filters</h3>

    <div class="filter-box">
      <label>Category</label>

      <form method="GET" action="products.php">
        <select name="category" onchange="this.form.submit()">
          <option <?php echo ($selected=="" || $selected=="All Varieties") ? "selected" : ""; ?>>
            All Varieties
          </option>

          <?php while($c = $catResult->fetch_assoc()): ?>
            <?php $cat = $c['product_name']; ?>
            <option value="<?php echo htmlspecialchars($cat); ?>"
              <?php echo ($selected === $cat) ? "selected" : ""; ?>>
              <?php echo htmlspecialchars($cat); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </form>
      
      <!-- Added policy note here -->
      <div class="policy-note">
        <i class="fas fa-info-circle"></i>
        <strong>Delivery Policy:</strong> Minimum of 10 sacks and maximum of 50 sacks for delivery orders. 
        Pick-up orders have no minimum quantity.
      </div>
    </div>
  </aside>

  <section class="products-section">
    <h2>List of Products</h2>

    <div class="product-grid">
      <?php if ($products->num_rows == 0): ?>
        <p style="padding:15px;">No products found.</p>
      <?php else: ?>
        <?php while($p = $products->fetch_assoc()): ?>
          <?php
            $img = !empty($p['image'])
              ? "../uploads/products/" . htmlspecialchars($p['image'])
              : "images/sample.jpg";
          ?>

          <div class="product-card">
            <img src="<?php echo $img; ?>" alt="Product">

            <h4><?php echo htmlspecialchars($p['product_name']); ?></h4>

            <p class="price">
              ₱<?php echo number_format((float)$p['price'], 2); ?> / <?php echo htmlspecialchars($p['unit']); ?>
            </p>

            <p style="font-size: 13px; opacity: 0.8; margin-top: -6px;">
            Farmer: <?php echo htmlspecialchars($p['farmer_name']); ?>
            </p>
            
            <a class="view-btn" href="viewproduct.php?id=<?php echo (int)$p['id']; ?>">
            View Product
            </a>

            <div class="action-buttons">
              <button class="cart-btn" type="button" 
                onclick="openCartModal(<?php echo (int)$p['id']; ?>,'<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($p['unit'], ENT_QUOTES); ?>',<?php echo (int)$p['quantity']; ?>)">
                <i class="fa-solid fa-cart-plus"></i> Add to Cart
              </button>

              <button class="buy-btn" type="button"
                onclick="openBuyModal(<?php echo (int)$p['id']; ?>,'<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($p['unit'], ENT_QUOTES); ?>',<?php echo (int)$p['quantity']; ?>)">
                <i class="fa-solid fa-bolt"></i> Buy Now
              </button>
            </div>

          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

  </section>

</div>

</div>
<?php require 'footer.php'?>

<?php include "shared/cart_buy_modals.php"; ?>
</body>
</html>