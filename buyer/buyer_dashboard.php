<?php
require_once "../db_connection.php";

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
    <title>Welcome to buyers dashboard</title>
    <link rel="stylesheet" href="css/buyer_dashboard.css">
    <link rel="stylesheet" href="css/mobileview.css">
    
</head>
<body>
    <header>
        <?php require 'header.php' ?>
    </header>
   
    <div class="hero">
        <h2>Welcome, buyer! <br>
            You can now start - <br>
            browsing for products!
        </h2> 
        <div class="hero-buttons">
            <a href="products.php"><button type="submit" class="farmer-button">Browse Products</button></a>
            <a href="profile.php"><button type="submit" class="buyer-button">See Profile</button></a>
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
              ? '../uploads/products/' . htmlspecialchars($p['image'])
              : '../images/no-image.png'; ?>"
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

          <a class="view-btn" href="viewproduct.php?id=<?php echo (int)$p['id']; ?>">
            View Product
          </a>
        </div>

      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

</section>


    <section class="about" id="how-it-works">
    <h2>How It Works</h2>

    <div class="about-container">

        <div class="about-info">
            <img src="../images/Upload.png">
            <h3>Farmers upload <br> products</h3>
        </div>

        <div class="about-info">
            <img src="../images/Browse.png">
            <h3>Buyers browse <br> and purchase</h3>
        </div>

        <div class="about-info">
            <img src="../images/Deliver.png">
            <h3>Farmers deliver <br> / Buyers receive</h3>
        </div>

    </div>
</section>
    <?php require 'footer.php' ?>
</body>
</html>