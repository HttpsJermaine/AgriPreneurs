<?php
session_start();
require_once "../db_connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }

$stmt = $conn->prepare("SELECT id, product_name, price, unit, quantity, image, fulfillment_options FROM farmer_products WHERE id=? AND farmer_id=? LIMIT 1");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success'=>false,'error'=>'Not found']);
    exit;
}
$p = $res->fetch_assoc();
echo json_encode(['success'=>true,'product'=>$p]);
