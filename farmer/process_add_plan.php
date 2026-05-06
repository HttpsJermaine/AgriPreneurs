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
$rice_variety = trim($_POST['rice_variety'] ?? '');
$planting_date = $_POST['planting_date'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (!in_array($season, ['wet','dry'], true)) {
  echo json_encode(["success"=>false,"error"=>"Please choose a season."]);
  exit;
}
if ($rice_variety === '' || $planting_date === '') {
  echo json_encode(["success"=>false,"error"=>"Rice variety and planting date are required."]);
  exit;
}

// Keep compatibility: assign a default quarter
$quarter = ($season === 'wet') ? 3 : 1;

/* image upload (optional) */
$image_path = null;
if (!empty($_FILES['image']['name'])) {
  $dir = "uploads/";
  if (!is_dir($dir)) mkdir($dir, 0777, true);

  $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
  $safeName = "plan_" . $farmerId . "_" . time() . "." . $ext;
  $target = $dir . $safeName;

  if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    echo json_encode(["success"=>false,"error"=>"Image upload failed."]);
    exit;
  }
  $image_path = $target;
}

$stmt = $conn->prepare("
  INSERT INTO farmer_plans (farmer_id, plan_year, quarter, season, rice_variety, planting_date, notes, image_path)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iiisssss", $farmerId, $plan_year, $quarter, $season, $rice_variety, $planting_date, $notes, $image_path);

$ok = $stmt->execute();
$stmt->close();

echo json_encode(["success"=>$ok, "error"=>$ok ? null : "Failed to save plan."]);
