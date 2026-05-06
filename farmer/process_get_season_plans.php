<?php
session_start();
header('Content-Type: application/json');
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
  echo json_encode(["success"=>false, "error"=>"Access denied"]);
  exit;
}

$farmerId = (int)$_SESSION['user_id'];
$year = (int)($_GET['year'] ?? date("Y"));
$season = $_GET['season'] ?? '';

if (!in_array($season, ['wet','dry'], true)) {
  echo json_encode(["success"=>false, "error"=>"Invalid season"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT id, rice_variety, planting_date, notes, image_path, season, quarter
  FROM farmer_plans
  WHERE farmer_id = ? AND plan_year = ? AND season = ?
  ORDER BY planting_date DESC, id DESC
");
$stmt->bind_param("iis", $farmerId, $year, $season);
$stmt->execute();
$res = $stmt->get_result();

$plans = [];
while($row = $res->fetch_assoc()){
  $plans[] = $row;
}
$stmt->close();

echo json_encode(["success"=>true, "plans"=>$plans]);
