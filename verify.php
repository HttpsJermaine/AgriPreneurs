<?php
$user_id = (int)($_GET['user_id'] ?? 0);
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($user_id <= 0) {
    header("Location: login.php?error=" . urlencode("Invalid verification link."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP</title>
  <link rel="stylesheet" href="css/register.css">
</head>
<body>

<header><?php require 'header.php' ?></header>

<div class="register-bg">
<div class="register-card">

  <h2>OTP Verification</h2>
  <p class="subtitle">We sent a code to your email. Enter it below.</p>

  <?php if ($success): ?>
    <p style="color:green;text-align:center;margin-bottom:10px;"><?php echo htmlspecialchars($success); ?></p>
  <?php endif; ?>

  <?php if ($error): ?>
    <p style="color:red;text-align:center;margin-bottom:10px;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <!-- VERIFY OTP -->
  <form method="POST" action="verify_process.php">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    <input
      type="text"
      name="otp"
      class="input-field"
      placeholder="Enter 6-digit OTP"
      maxlength="6"
      required
      pattern="[0-9]{6}"
      inputmode="numeric"
      title="Please enter a 6-digit code"
    >
    <button type="submit" class="register-btn">Verify OTP</button>
  </form>

  <!-- RESEND OTP -->
  <form method="POST" action="resend_otp.php" style="margin-top:10px;">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    <button type="submit" id="resendBtn" class="register-btn" style="background:#777;">
      Resend OTP
    </button>
    <p id="resendNote" style="text-align:center; margin-top:8px; font-size:14px; color:#555;"></p>
  </form>

  <p class="bottom-text" style="text-align:center;margin-top:12px;">
    <a href="login.php">Back to Login</a>
  </p>

</div>
</div>

<?php require 'footer.php' ?>

<script>
const resendBtn = document.getElementById("resendBtn");
const resendNote = document.getElementById("resendNote");

let seconds = 60;

resendBtn.disabled = true;
resendBtn.style.opacity = "0.6";
resendBtn.style.cursor = "not-allowed";
resendNote.textContent = `You can resend OTP in ${seconds}s`;

const timer = setInterval(() => {
  seconds--;
  if (seconds <= 0) {
    clearInterval(timer);
    resendBtn.disabled = false;
    resendBtn.style.opacity = "1";
    resendBtn.style.cursor = "pointer";
    resendNote.textContent = "You can resend OTP now ✅";
  } else {
    resendNote.textContent = `You can resend OTP in ${seconds}s`;
  }
}, 1000);
</script>

</body>
</html>