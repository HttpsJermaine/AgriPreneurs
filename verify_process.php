<?php
require_once "db_connection.php";

function goVerify($user_id, $type, $msg) {
    header("Location: verify.php?user_id=" . urlencode($user_id) . "&$type=" . urlencode($msg));
    exit;
}

$user_id = (int)($_POST["user_id"] ?? 0);
$otp = trim($_POST["otp"] ?? "");

if ($user_id <= 0 || $otp === "") {
    goVerify($user_id, "error", "Invalid request.");
}

// latest OTP
$stmt = $conn->prepare("
    SELECT id, code_hash, expires_at, attempts
    FROM user_verifications
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$ver = $res->fetch_assoc();
$stmt->close();

if (!$ver) goVerify($user_id, "error", "No OTP found. Please resend.");
if (strtotime($ver["expires_at"]) < time()) goVerify($user_id, "error", "OTP expired. Please resend.");
if ((int)$ver["attempts"] >= 5) goVerify($user_id, "error", "Too many attempts. Please resend OTP.");

// wrong OTP
if (!password_verify($otp, $ver["code_hash"])) {
    $upd = $conn->prepare("UPDATE user_verifications SET attempts = attempts + 1 WHERE id = ?");
    $upd->bind_param("i", $ver["id"]);
    $upd->execute();
    $upd->close();
    goVerify($user_id, "error", "Incorrect OTP.");
}

// get role + current status
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$role = $user["role"] ?? "";

// Mark email verified
$upd = $conn->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
$upd->bind_param("i", $user_id);
$upd->execute();
$upd->close();

// If BUYER → auto activate right after OTP
if ($role === "buyer") {
    $upd = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $upd->bind_param("i", $user_id);
    $upd->execute();
    $upd->close();
}
elseif ($role === "farmer") {

    // Get farmer registry number from farmer_details
    $stmt = $conn->prepare("SELECT registry_num FROM farmer_details WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $farmer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $registry = $farmer["registry_num"] ?? "";

    if ($registry !== "") {

        // Check if exists in farmers_list
        $stmt = $conn->prepare("SELECT id FROM farmers_list WHERE rsbsa_no = ? LIMIT 1");
        $stmt->bind_param("s", $registry);
        $stmt->execute();
        $exists = $stmt->get_result();
        $stmt->close();

        if ($exists->num_rows > 0) {

            // AUTO APPROVE
            $upd = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $upd->bind_param("i", $user_id);
            $upd->execute();
            $upd->close();

            header("Location: login.php?success=" . urlencode("Verified! Your account is approved."));
            exit;
        }
    }

    // NOT FOUND → STILL NEED ADMIN APPROVAL
    header("Location: login.php?success=" . urlencode("Email verified! Please wait for admin approval."));
    exit;
}

// Farmers remain pending until admin approves

// delete OTP records (cleanup)
$del = $conn->prepare("DELETE FROM user_verifications WHERE user_id = ?");
$del->bind_param("i", $user_id);
$del->execute();
$del->close();

$conn->close();

if ($role === "farmer") {
    header("Location: login.php?success=" . urlencode("Email verified! Please wait for admin approval before you can log in."));
    exit;
}

header("Location: login.php?success=" . urlencode("Verified! You may now log in."));
exit;