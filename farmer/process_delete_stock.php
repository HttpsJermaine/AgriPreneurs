<?php
// farmer/process_delete_stock.php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }

// remove image file
$stmt = $conn->prepare("SELECT image FROM farmer_products WHERE id=? AND farmer_id=? LIMIT 1");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows) {
    $row = $res->fetch_assoc();
    if ($row['image']) {
        $path = __DIR__ . '/../uploads/products/' . $row['image'];
        if (file_exists($path)) @unlink($path);
    }
}
$stmt->close();

// delete product
$stmt = $conn->prepare("DELETE FROM farmer_products WHERE id=? AND farmer_id=?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success'=>(bool)$ok]);
