<?php
session_start();
require_once "../db_connection.php";

function back($msg) {
    header("Location: stocks.php?error=" . urlencode($msg));
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    back("Unauthorized");
}

$farmerId = (int)$_SESSION['user_id'];

$id = (int)($_POST['id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);

if ($id <= 0) back("Invalid product.");
if ($quantity < 0) back("Quantity cannot be negative.");

//  fulfillment options
$options = $_POST['fulfillment_options'] ?? [];
if (!is_array($options) || count($options) === 0) $options = ['pickup'];

$allowedOpts = ['pickup', 'delivery'];
$options = array_values(array_intersect($options, $allowedOpts));
if (count($options) === 0) $options = ['pickup'];

$fulfillment = implode(',', $options);

// Get the existing product to preserve name, price, and unit
$stmt = $conn->prepare("SELECT product_name, price, unit, image FROM farmer_products WHERE id=? AND farmer_id=? LIMIT 1");
$stmt->bind_param("ii", $id, $farmerId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    back("Product not found");
}
$old = $res->fetch_assoc();
$stmt->close();

// Use existing values (ignore what was submitted)
$product_name = $old['product_name'];
$price = (float)$old['price'];
$unit = $old['unit'];
$filename = $old['image'];

// handle new image (optional)
if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['product_image']['tmp_name'];
    $orig = basename($_FILES['product_image']['name']);
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (in_array($ext, $allowed)) {
        $newname = uniqid('prod_') . '.' . $ext;

        // stocks.php displays: ../uploads/products/<image>
        $destDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        if (move_uploaded_file($tmp, $destDir . $newname)) {
            if ($filename && file_exists($destDir . $filename)) unlink($destDir . $filename);
            $filename = $newname;
        }
    } else {
        back("Invalid image format. Only JPG, JPEG, PNG, and WEBP are allowed.");
    }
}

// Update only quantity, image, and fulfillment_options
// Preserve product_name, price, and unit from database
$stmt = $conn->prepare("
  UPDATE farmer_products
  SET quantity=?, image=?, fulfillment_options=?
  WHERE id=? AND farmer_id=?
");
$stmt->bind_param(
  "issii",
  $quantity,
  $filename,
  $fulfillment,
  $id,
  $farmerId
);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    $stmt->close();
    header("Location: stocks.php?success=" . urlencode("Stock updated successfully."));
    exit;
} else {
    $stmt->close();
    back("Database error: " . $conn->error);
}
?>