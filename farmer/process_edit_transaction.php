<?php
session_start();
require "../db_connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "farmer") {
    header("Location: ../login.php?error=Unauthorized");
    exit;
}

$id = $_POST['id'];
$farmer = $_POST['farmer_id'];
$type = $_POST['type'];
$amount = $_POST['amount'];
$desc = $_POST['description'];
$date = $_POST['date'];

// Validate ownership (only edit own data)
$stmt = $conn->prepare("UPDATE farmer_transactions 
                        SET type=?, amount=?, description=?, date=? 
                        WHERE id=? AND farmer_id=?");
$stmt->bind_param("sdssii", $type, $amount, $desc, $date, $id, $farmer);
$stmt->execute();

header("Location: earnings.php");
exit;
