<?php
session_start();
require_once "../db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  echo json_encode(['success'=>false,'error'=>'Unauthorized']);
  exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$position = trim($_POST['position'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$phone    = trim($_POST['phone'] ?? '');

if (!$fullname || !$position || !$username || !$password) {
  echo json_encode(['success'=>false,'error'=>'Missing fields']);
  exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s",$username);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
  echo json_encode(['success'=>false,'error'=>'Username exists']);
  exit;
}
$stmt->close();

$photoName = "";
if (!empty($_FILES['photo']['name'])) {
  $folder = "../uploads/admins/";
  if (!is_dir($folder)) mkdir($folder,0777,true);

  $photoName = "admin_" . time() . "_" . basename($_FILES['photo']['name']);
  move_uploaded_file($_FILES['photo']['tmp_name'], $folder.$photoName);
}

$conn->begin_transaction();

try {

  $hash = password_hash($password,PASSWORD_DEFAULT);

  $role="admin";
  $status="active";
  $email=null;

  $stmt=$conn->prepare("INSERT INTO users(role,username,email,password,status) VALUES(?,?,?,?,?)");
  $stmt->bind_param("sssss",$role,$username,$email,$hash,$status);
  $stmt->execute();
  $uid=$stmt->insert_id;
  $stmt->close();

  $stmt=$conn->prepare("INSERT INTO admin_details(user_id,full_name,position,phone,photo) VALUES(?,?,?,?,?)");
  $stmt->bind_param("issss",$uid,$fullname,$position,$phone,$photoName);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  echo json_encode(['success'=>true]);

} catch(Exception $e){
  $conn->rollback();
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
