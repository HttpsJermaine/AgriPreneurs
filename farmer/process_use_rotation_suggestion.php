<?php
session_start();
header('Content-Type: application/json');
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  echo json_encode(["success"=>false,"error"=>"Access denied"]);
  exit;
}

$farmerId = (int)$_SESSION['user_id'];

$plan_year = (int)($_POST['plan_year'] ?? date("Y"));
$season = $_POST['season'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);
$rice_variety = trim($_POST['rice_variety'] ?? '');
$planting_date = $_POST['planting_date'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (!in_array($season, ['wet','dry'], true)) {
  echo json_encode(["success"=>false,"error"=>"Invalid season."]);
  exit;
}
if ($rice_variety === '' || $planting_date === '') {
  echo json_encode(["success"=>false,"error"=>"Rice variety and planting date are required."]);
  exit;
}

// keep quarter compatibility
$quarter = ($season === 'wet') ? 3 : 1;

// auto-fill a default note if empty
if ($notes === '') {
  $notes = "Inserted from rotation suggestion.";
}

// get product image from farmer_products
$image_path = null;

$stmt = $conn->prepare("SELECT image FROM farmer_products WHERE id=? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!empty($row['image'])) {
  $image_path = "uploads/products/" . $row['image']; // ✅ correct path
}


// 2) insert plan including image_path
$stmt = $conn->prepare("
  INSERT INTO farmer_plans (farmer_id, plan_year, quarter, season, rice_variety, planting_date, notes, image_path)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iiisssss", $farmerId, $plan_year, $quarter, $season, $rice_variety, $planting_date, $notes, $image_path);


$ok = $stmt->execute();
$err = $ok ? null : $stmt->error;
$stmt->close();

echo json_encode(["success"=>$ok, "error"=>$ok ? null : "Failed to insert suggestion."]);

