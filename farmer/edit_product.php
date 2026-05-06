<?php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$farmerId = (int)$_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$riceVariety = trim($_POST['riceVariety'] ?? '');
$price = floatval($_POST['price'] ?? 0);

if (!$productId || $riceVariety === '' || $price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

# Verify ownership
$stmt = $conn->prepare("SELECT product_image FROM product_requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $productId, $farmerId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$productImage = $product['product_image'];

# Handle image upload
if (!empty($_FILES['productImage']['name'])) {

    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success'=>false,'error'=>'Invalid image type']);
        exit;
    }

    $uploadDir = "../uploads/products/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir,0755,true);
    }

    # Delete old image
    if ($productImage && file_exists($uploadDir.$productImage)) {
        unlink($uploadDir.$productImage);
    }

    $fileName = "product_".$farmerId."_".time().".".$ext;
    $path = $uploadDir.$fileName;

    if (move_uploaded_file($_FILES['productImage']['tmp_name'],$path)) {
        $productImage = $fileName;
    }
}

# Update product and resubmit for review
$stmt = $conn->prepare("
UPDATE product_requests
SET rice_variety=?, price_per_sack=?, product_image=?, status='pending', rejection_reason=NULL
WHERE id=? AND user_id=?
");

$stmt->bind_param("sdsii", $riceVariety, $price, $productImage, $productId, $farmerId);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}

$stmt->close();
?>