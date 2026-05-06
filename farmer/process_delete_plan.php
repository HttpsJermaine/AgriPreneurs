<?php
session_start();
header('Content-Type: application/json');
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  http_response_code(403);
  echo json_encode(["success"=>false,"error"=>"Access denied"]);
  exit;
}

$farmerId = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT image_path FROM farmer_plans WHERE id=? AND farmer_id=?");
$stmt->bind_param("ii", $id, $farmerId);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plan) {
  echo json_encode(["success"=>false,"error"=>"Plan not found"]);
  exit;
}

$stmt = $conn->prepare("DELETE FROM farmer_plans WHERE id=? AND farmer_id=?");
$stmt->bind_param("ii", $id, $farmerId);
$stmt->execute();
$stmt->close();

// optional: remove uploaded file (only if it's inside farmer/uploads)
if (!empty($plan['image_path'])) {
  $path = str_replace(["..", "\\"], ["", "/"], $plan['image_path']); // sanitize
  $path = ltrim($path, "/");

  if (!str_contains($path, "/")) {
    $path = "uploads/" . $path;
  }

  $uploadsDir = realpath(__DIR__ . "/uploads");
  $fileAbs = realpath(__DIR__ . "/" . $path);

  if ($uploadsDir && $fileAbs && str_starts_with($fileAbs, $uploadsDir) && is_file($fileAbs)) {
    @unlink($fileAbs);
  }
}

echo json_encode(["success"=>true]);
