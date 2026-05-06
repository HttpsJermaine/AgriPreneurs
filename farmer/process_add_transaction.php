<?php
session_start();
require "../db_connection.php";

if ($_SESSION['role'] !== "farmer") exit("Unauthorized");

$farmer = $_POST['farmer_id'];
$type = $_POST['type'];
$desc = $_POST['description'];
$amount = $_POST['amount'];
$date = $_POST['date'];

$stmt = $conn->prepare("INSERT INTO farmer_transactions (farmer_id, type, amount, description, date)
                        VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isdss", $farmer, $type, $amount, $desc, $date);
$stmt->execute();

header("Location: earnings.php");
exit;
