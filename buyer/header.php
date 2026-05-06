<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../db_connection.php";

$cartCount = 0;
$usernameDisplay = "Guest";
if (!empty($_SESSION['username'])) {
  $usernameDisplay = htmlspecialchars($_SESSION['username']);
}

if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'buyer') {
  $buyer_id = (int)$_SESSION['user_id'];

  $stmt = $conn->prepare("
    SELECT COALESCE(COUNT(DISTINCT ci.product_id), 0) AS cnt
    FROM carts c
    LEFT JOIN cart_items ci ON ci.cart_id = c.id
    WHERE c.buyer_id = ?
  ");
  $stmt->bind_param("i", $buyer_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $cartCount = (int)($row['cnt'] ?? 0);
}
?>

<link rel="stylesheet" href="css/header.css">

<h1>🌿 PLAMAL Marketplace</h1>

<nav>
  <a href="buyer_dashboard.php">Home</a>
  <a href="products.php">Products</a>
</nav>

<div class="search-cart">
  <a href="cart.php" class="cart-icon">
    🛒<span class="cart-count"><?php echo $cartCount; ?></span>
  </a>

  <nav>
    <a href="profile.php">|   <?php echo $usernameDisplay; ?></a>
  </nav>
</div>