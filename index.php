<?php
session_start();
require_once "db_connection.php";

$products = $conn->query("
  SELECT fp.*, fd.farmer_name
  FROM farmer_products fp
  JOIN farmer_details fd ON fd.user_id = fp.farmer_id
  ORDER BY fp.id DESC
  LIMIT 8
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page - Marketplace</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/mobileview.css">
</head>
<body>
    <header>
        <?php require 'header.php' ?>
    </header>
   
    <div class="hero">
        <h2>Connecting Farmers <br>
            and Buyers - <br>
            Fresh, Local, Trusted.
        </h2> 
        <div class="hero-buttons">
            <a href="register.php"><button type="submit" class="farmer-button">Register Now</button></a>
            
        </div>
    </div>

<section class="products-section">
  <h2>Latest Products</h2>

  <div class="product-grid">
  <?php if ($products->num_rows == 0): ?>
    <p>No products yet.</p>
  <?php else: ?>
    <?php while($p = $products->fetch_assoc()): ?>
      <div class="product-card">

        <div class="product-img">
          <img src="<?php echo !empty($p['image'])
              ? 'uploads/products/' . htmlspecialchars($p['image'])
              : 'images/no-image.png'; ?>"
              alt="Product">
        </div>

        <div class="product-info">
          <h3><?php echo htmlspecialchars($p['product_name']); ?></h3>

          <p class="price">
            ₱<?php echo number_format((float)$p['price'],2); ?> / <?php echo htmlspecialchars($p['unit']); ?>
          </p>

          <p style="font-size: 13px; opacity: 0.8; margin-top: -6px;">
            Farmer: <?php echo htmlspecialchars($p['farmer_name']); ?>
          </p>

          <a class="view-btn" href="#" onclick="checkLogin(); return false;">
          View Product
          </a>
        </div>

      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

</section>

    <section class="about">
    <h2>How It Works</h2>

    <div class="about-container">

        <div class="about-info">
            <img src="images/Upload.png">
            <h3>Farmers upload <br> products</h3>
        </div>

        <div class="about-info">
            <img src="images/Browse.png">
            <h3>Buyers browse <br> and purchase</h3>
        </div>

        <div class="about-info">
            <img src="images/Deliver.png">
            <h3>Farmers approve <br> / Buyers receives</h3>
        </div>

    </div>
</section>
    <?php require 'footer.php' ?>

    <script>
function checkLogin() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert("Please log in first to view product details.");
        window.location.href = "login.php";
    <?php else: ?>
        window.location.href = "buyer/products.php";
    <?php endif; ?>
}
</script>
</body>
</html>