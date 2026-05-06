<?php
require_once "db_connection.php";
require_once __DIR__ . "/smtp_config.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function createOtp() { return (string)random_int(100000, 999999); }

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
        return false;
    }
}

function goVerify($user_id, $type, $msg) {
    header("Location: verify.php?user_id=" . urlencode($user_id) . "&$type=" . urlencode($msg));
    exit;
}

$user_id = (int)($_POST["user_id"] ?? 0);
if ($user_id <= 0) goVerify($user_id, "error", "Invalid request.");

// cooldown
$stmt = $conn->prepare("
    SELECT created_at
    FROM user_verifications
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$last = $res->fetch_assoc();
$stmt->close();

if ($last && isset($last["created_at"])) {
    $diff = time() - strtotime($last["created_at"]);
    if ($diff < 60) {
        $wait = 60 - $diff;
        $conn->close();
        goVerify($user_id, "error", "Please wait {$wait} seconds before resending OTP.");
    }
}

// get email destination
$stmt = $conn->prepare("
    SELECT destination
    FROM user_verifications
    WHERE user_id = ? AND method = 'email'
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    $conn->close();
    goVerify($user_id, "error", "No email record found.");
}

$email = $row["destination"];

// save new otp
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

$conn->close();

// send
$sent = sendOtpEmailSMTP($email, $otp);

if (!$sent) {
    goVerify($user_id, "error", "OTP could not be resent. Try again later.");
}

goVerify($user_id, "success", "OTP resent. Please check your email.");