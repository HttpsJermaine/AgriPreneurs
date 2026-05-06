<?php
session_start();

require_once "db_connection.php";
require_once __DIR__ . "/smtp_config.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function storeOldRegisterInput() {
    $old = $_POST;
    unset($old['farmer_password'], $old['farmer_confirm']);
    unset($old['buyer_password'],  $old['buyer_confirm']);
    unset($old['farmer_photo'], $old['buyer_photo']);
    $_SESSION['old_register'] = $old;
}

function goBack($type, $msg, $preserveOld = true) {
    if ($preserveOld && $_SERVER["REQUEST_METHOD"] === "POST") {
        storeOldRegisterInput();
    }
    header("Location: register.php?$type=" . urlencode($msg));
    exit;
}

function createOtp() {
    return (string)random_int(100000, 999999);
}

function sendOtpEmailSMTP($to, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = "Your OTP Verification Code";
        $mail->Body = "
            <div style='font-family:Arial,sans-serif'>
                <h2>OTP Verification</h2>
                <p>Your OTP is:</p>
                <h1 style='letter-spacing:4px;'>$otp</h1>
                <p>This code expires in <b>10 minutes</b>.</p>
            </div>
        ";
        $mail->AltBody = "Your OTP is: $otp (expires in 10 minutes).";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // error_log("SMTP Error: " . $mail->ErrorInfo);
        return false;
    }
}

function saveImage($fileInputName) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return NULL;
    }

    $file = $_FILES[$fileInputName];
    $allowed = ["image/jpeg", "image/png", "image/jpg", "image/webp"];

    if (!in_array($file["type"], $allowed)) {
        return NULL;
    }

    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newName = uniqid() . "." . $ext;
    $uploadPath = "uploads/" . $newName;

    if (!move_uploaded_file($file["tmp_name"], $uploadPath)) {
        return NULL;
    }

    return $newName;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    goBack("error", "Invalid request method.");
}

$role = $_POST["role"] ?? "";
if ($role !== "farmer" && $role !== "buyer") {
    goBack("error", "Please select a role.");
}

$username = trim($_POST[$role . "_username"] ?? "");
$password = $_POST[$role . "_password"] ?? "";
$confirm  = $_POST[$role . "_confirm"] ?? "";

// email required
$email = strtolower(trim($_POST[$role . "_email"] ?? ""));
if ($email === "") goBack("error", "Email is required.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) goBack("error", "Invalid email format.");

if ($username === "") goBack("error", "Username is required.");
if (strlen($password) < 6) goBack("error", "Password must be at least 6 characters.");
if ($password !== $confirm) goBack("error", "Passwords do not match.");

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$r = $stmt->get_result();
$stmt->close();

if ($r->num_rows > 0) {
    goBack("error", "Username already taken.");
}

// Check if email already exists in users
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$r2 = $stmt->get_result();
$stmt->close();

if ($r2->num_rows > 0) {
    goBack("error", "Email is already registered. Please use another email.");
}

$hashedPass = password_hash($password, PASSWORD_DEFAULT);

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $role, $username, $email, $hashedPass);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();

    if ($role === "farmer") {
        $photo = saveImage("farmer_photo");

        $stmt = $conn->prepare("
            INSERT INTO farmer_details 
            (user_id, farmer_name, farm_area, phone, registry_num, photo, street, barangay, full_address, city, province, zip, citymun_code, province_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $registrynum   = trim($_POST["registry_number"] ?? "");
        $province_code = trim($_POST["farmer_province_code"] ?? "");
        $citymun_code  = trim($_POST["farmer_citymun_code"] ?? "");
        $province_name = trim($_POST["farmer_province_name"] ?? "");
        $city_name     = trim($_POST["farmer_city_name"] ?? "");
        $barangay      = trim($_POST["farmer_barangay"] ?? "");
        $full_address  = trim($_POST["farmer_full_address"] ?? "");
        $zip           = trim($_POST["farmer_zip"] ?? "");
        $street_legacy = $full_address;
        
        $stmt->bind_param(
            "isssssssssssss",
            $user_id,
            $_POST["farmer_name"],
            $_POST["farm_area"],
            $_POST["farmer_phone"],
            $registrynum,
            $photo,
            $street_legacy,
            $barangay,
            $full_address,
            $city_name,
            $province_name,
            $zip,
            $citymun_code,
            $province_code
        );
        
        $stmt->execute();
        $stmt->close();        

    } else {
        $photo = saveImage("buyer_photo");

        $stmt = $conn->prepare("
            INSERT INTO buyer_details 
            (user_id, full_name, phone, photo, street, barangay, full_address, city, province, zip, citymun_code, province_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $province_code = trim($_POST["buyer_province_code"] ?? "");
        $citymun_code  = trim($_POST["buyer_citymun_code"] ?? "");
        $province_name = trim($_POST["buyer_province_name"] ?? "");
        $city_name     = trim($_POST["buyer_city_name"] ?? "");
        $barangay      = trim($_POST["buyer_barangay"] ?? "");
        $full_address  = trim($_POST["buyer_full_address"] ?? "");
        $zip           = trim($_POST["buyer_zip"] ?? "");

        // keep old "street" useful for old pages
        $street_legacy = $full_address;

        $stmt->bind_param(
            "isssssssssss",
            $user_id,
            $_POST["buyer_name"],
            $_POST["buyer_phone"],
            $photo,
            $street_legacy,
            $barangay,
            $full_address,
            $city_name,
            $province_name,
            $zip,
            $citymun_code,
            $province_code
          );
        $stmt->execute();
        $stmt->close();
    }

    // OTP record
    $otp = createOtp();
    $expires = date("Y-m-d H:i:s", time() + 10 * 60);
    $hash = password_hash($otp, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO user_verifications (user_id, method, destination, code_hash, expires_at)
        VALUES (?, 'email', ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $email, $hash, $expires);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    goBack("error", "Registration failed. Please try again.");
}

$conn->close();

// send OTP using Hostinger SMTP
$sent = sendOtpEmailSMTP($email, $otp);

if (!$sent) {
    header("Location: verify.php?user_id=" . urlencode($user_id) . "&error=" . urlencode("OTP could not be sent. Please contact admin."));
    exit;
}

header("Location: verify.php?user_id=" . urlencode($user_id) . "&success=" . urlencode("OTP sent to your email."));
exit;