<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  http_response_code(403);
  echo json_encode(["success"=>false,"error"=>"Access denied"]);
  exit;
}

$farmerId = (int)$_SESSION['user_id'];

$id = (int)($_POST['id'] ?? 0);
$riceVariety = trim($_POST['rice_variety'] ?? '');
$plantingDate = $_POST['planting_date'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if ($id <= 0 || $riceVariety === '' || $plantingDate === '') {
  echo json_encode(["success"=>false,"error"=>"Missing fields"]);
  exit;
}

$today = date("Y-m-d");
if ($plantingDate < $today) {
  echo json_encode(["success"=>false,"error"=>"Planting date must be today or in the future."]);
  exit;
}

$imagePath = null;
$replaceImage = false;

if (!empty($_FILES['image']['name'])) {
  $allowed = ['image/jpeg','image/png','image/webp'];
  if (!in_array($_FILES['image']['type'], $allowed)) {
    echo json_encode(["success"=>false,"error"=>"Invalid image type. Use JPG/PNG/WEBP."]);
    exit;
  }

  $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
  $newName = "plan_{$farmerId}_" . time() . "_" . mt_rand(1000,9999) . "." . $ext;

  $uploadDir = __DIR__ . "/../uploads/plans/";
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

  $dest = $uploadDir . $newName;

  if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
    echo json_encode(["success"=>false,"error"=>"Failed to upload image."]);
    exit;
  }

  $imagePath = "../uploads/plans/" . $newName;
  $replaceImage = true;
}

if ($replaceImage) {
  $stmt = $conn->prepare("
    UPDATE farmer_plans
    SET rice_variety=?, planting_date=?, notes=?, image_path=?
    WHERE id=? AND farmer_id=?
  ");
  $stmt->bind_param("ssssii", $riceVariety, $plantingDate, $notes, $imagePath, $id, $farmerId);
} else {
  $stmt = $conn->prepare("
    UPDATE farmer_plans
    SET rice_variety=?, planting_date=?, notes=?
    WHERE id=? AND farmer_id=?
  ");
  $stmt->bind_param("sssii", $riceVariety, $plantingDate, $notes, $id, $farmerId);
}

$stmt->execute();
$stmt->close();

echo json_encode(["success"=>true]);