<?php
session_start();
require "../db_connection.php";

if ($_SESSION['role'] !== "farmer") exit("Unauthorized");

$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM farmer_transactions WHERE id=? AND farmer_id=?");
$stmt->bind_param("ii", $id, $_SESSION["user_id"]);
$stmt->execute();

header("Location: earnings.php");
exit;
