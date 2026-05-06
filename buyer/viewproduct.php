<?php
session_start();
require_once "../db_connection.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { die("Invalid product."); }

$stmt = $conn->prepare("
  SELECT
    fp.id, fp.farmer_id, fp.product_name, fp.price, fp.unit, fp.quantity, fp.image, fp.created_at, fp.fulfillment_options,
    fd.farmer_name, fd.farm_area, fd.phone, fd.photo, fd.street, fd.city, fd.province, fd.zip
  FROM farmer_products fp
  JOIN farmer_details fd ON fd.user_id = fp.farmer_id
  WHERE fp.id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) { die("Product not found."); }

$img = !empty($p['image'])
  ? "../uploads/products/" . htmlspecialchars($p['image'])
  : "images/sample.jpg";

$full_address = trim(($p['street'] ?? '') . ", " . ($p['city'] ?? '') . ", " . ($p['province'] ?? '') . " " . ($p['zip'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>View Product</title>
  <link rel="stylesheet" href="css/products.css">
  <link rel="stylesheet" href="css/mobileview.css">
</head>
<body>

<header><?php require 'header.php'?></header>

<div style="max-width:1000px;margin:30px auto;padding:0 15px;">
  <h2 style="margin-bottom:15px;">Product Details</h2>

  <div style="display:flex;gap:20px;flex-wrap:wrap;background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(0,0,0,.08);">
    <img src="<?php echo $img; ?>" alt="Product"
         style="width:360px;max-width:100%;height:240px;object-fit:cover;border-radius:12px;">

    <div style="flex:1;min-width:280px;">
      <h3 style="margin:0 0 8px;"><?php echo htmlspecialchars($p['product_name']); ?></h3>

      <p style="margin:6px 0;"><b>Price:</b> ₱<?php echo number_format((float)$p['price'],2); ?> / <?php echo htmlspecialchars($p['unit']); ?></p>
      <p style="margin:6px 0;"><b>Available:</b> <?php echo (int)$p['quantity']; ?> <?php echo htmlspecialchars($p['unit']); ?>(s)</p>
      <p style="margin:6px 0;"><b>Fulfillment:</b> <?php echo htmlspecialchars($p['fulfillment_options']); ?></p>

      <hr style="margin:14px 0;">

      <h4 style="margin:0 0 8px;">Farmer Details</h4>
      <p style="margin:6px 0;"><b>Name:</b> <?php echo htmlspecialchars($p['farmer_name']); ?></p>
      <p style="margin:6px 0;"><b>Phone:</b> <?php echo htmlspecialchars($p['phone']); ?></p>
      <p style="margin:6px 0;"><b>Address:</b> <?php echo htmlspecialchars($full_address); ?></p>

      <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
        
        <button class="cart-btn" type="button"
          onclick="openCartModal(<?php echo (int)$p['id']; ?>,'<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($p['unit'], ENT_QUOTES); ?>',<?php echo (int)$p['quantity']; ?>)">
          Add to Cart
        </button>

        <button class="buy-btn" type="button"
          onclick="openBuyModal(<?php echo (int)$p['id']; ?>,'<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($p['unit'], ENT_QUOTES); ?>',<?php echo (int)$p['quantity']; ?>)">
          Buy Now
        </button>

        <a class="view-btn" href="products.php" style="text-decoration:none;">← Back</a>

      </div>
    </div>
  </div>
</div>

<?php include "shared/cart_buy_modals.php"; ?>
<?php require 'footer.php'?>

</body>
</html>