<?php
session_start();
require_once "db_connection.php";

function back($msg) {
    header("Location: login.php?error=" . urlencode($msg));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    back("Invalid request.");
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    back("Required Missing Fields");
}

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    back("Invalid username or password.");
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user["password"])) {
    back("Invalid username or password.");
}

$status = $user["status"];

if ($status === "pending") {
    back("Your account is still pending.");
}

if ($status === "disabled") {
    back("Your account is disabled.");
}

if ($status !== "active") {
    back("Account cannot log in. Contact support.");
}

$_SESSION["user_id"] = $user["id"];
$_SESSION["username"] = $user["username"];
$_SESSION["role"] = $user["role"];

if ($user["role"] === "admin") {
    header("Location: admin_page/admin_dashboard.php");
    exit;
}

if ($user["role"] === "farmer") {
    header("Location: farmer/farmer_dashboard.php");
    exit;
}

if ($user["role"] === "buyer") {
    header("Location: buyer/buyer_dashboard.php");
    exit;
}

back("Your account role is not recognized.");
?>
