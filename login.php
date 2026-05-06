<?php 
session_start();

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: "Poppins", sans-serif;
  background-color: white;
  color: #1a1a1a;
}
.container{ 
    width: 100%;
    padding: 60px 0;
    display: flex;
    justify-content: center;
}
.login-card {
    background: rgba(255, 255, 255, 0.95);
    width: 380px;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.4s ease-out;
}

.login-card h2 {
    text-align: center;
    font-size: 26px;
    font-weight: 600;
    color: #0f4128;
}

.login-sub {
    text-align: center;
    font-size: 14px;
    opacity: 0.7;
    margin-bottom: 25px;
}

.input-field {
    width: 100%;
    padding: 12px;
    margin: 8px 0 10px;
    border-radius: 8px;
    border: 1px solid #cfcfcf;
    font-size: 15px;
    outline: none;
}

.input-field:focus {
    border-color: #32b768;
}

.show-pass {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.show-pass input {
    width: 18px;
    height: 18px;
}

.login-btn {
    width: 100%;
    padding: 12px 0;
    background: #32b768;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 17px;
    cursor: pointer;
    transition: 0.2s ease;
    box-shadow: 0 4px 10px rgba(50, 183, 104, 0.4);
}

.login-btn:hover {
    background: #28985a;
}

.small-text {
    text-align: center;
    margin-top: 20px;
    font-size: 13px;
}

.small-text a {
    color: #116f3b;
    font-weight: 600;
    text-decoration: none;
}

.small-text a:hover {
    text-decoration: underline;
}
.logo{
    display: flex;
    justify-content: center;
}
.logo img {
    width: 70px;
    margin-bottom: 15px;
    filter: drop-shadow(0 0 5px rgba(0, 255, 100, 0.4));
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(10px);}
    to {opacity: 1; transform: translateY(0);}
}

#popupModal {
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.45);
  z-index:9999;
  align-items:center;
  justify-content:center;
}
#popupBox {
  width:min(420px, 92%);
  background:#fff;
  border-radius:14px;
  padding:18px 18px 14px;
  box-shadow:0 12px 30px rgba(0,0,0,0.18);
}
#popupTitle {
  margin:0;
  font-size:18px;
  color:#0f4128;
}
#popupMsg {
  margin:8px 0 0;
  color:#444;
  line-height:1.4;
}
#popupActions {
  margin-top:14px;
  display:flex;
  justify-content:flex-end;
}
#popupOk {
  border:none;
  padding:10px 14px;
  border-radius:10px;
  cursor:pointer;
  background:#32b768;
  color:#fff;
  font-weight:600;
}
#popupOk:hover { background:#28985a; }
    </style>
</head>
<body>
    
    <header>
        <?php require 'header.php' ?>
    </header>

    <div id="popupModal">
      <div id="popupBox">
          <h3 id="popupTitle"></h3>
          <p id="popupMsg"></p>

          <div id="popupActions">
            <button type="button" id="popupOk">OK</button>
          </div>
      </div>
    </div>

    <div class="container">
      <form class="login-card" action="login_process.php" method="POST">
        <div class="logo">
          <img src="images/icon.png" alt="Logo">
        </div>

        <h2>Welcome!</h2>
        <p class="login-sub">Please login to continue</p>
        <input type="text" class="input-field" placeholder="Username" name="username" required>
        <input type="password" id="password" class="input-field" name="password" placeholder="Password">

        <div class="show-pass">
            <input type="checkbox" id="showPassword">
            <label for="showPassword">Show Password</label>
        </div>

        <button type="submit" class="login-btn">Login</button>

        <p class="small-text">
          Don't have an account?
          <a href="register.php">Register here</a>
        </p>
      </form>
    </div>

    <?php require 'footer.php' ?>

<script>
    const toggle = document.getElementById("showPassword");
    const pass1 = document.getElementById("password");
    toggle.addEventListener("change", function () {
        pass1.type = toggle.checked ? "text" : "password";
    });

    (function(){
      const success = <?php echo json_encode($success); ?>;
      const error = <?php echo json_encode($error); ?>;

      const modal = document.getElementById("popupModal");
      const title = document.getElementById("popupTitle");
      const msg = document.getElementById("popupMsg");
      const okBtn = document.getElementById("popupOk");

      function openPopup(type, text){
        if (!text) return;
        modal.style.display = "flex";
        title.textContent = (type === "success") ? "Success ✅" : "Notice ⚠️";
        msg.textContent = text;
      }

      function closePopup(){
        modal.style.display = "none";
        const url = new URL(window.location.href);
        url.searchParams.delete("success");
        url.searchParams.delete("error");
        window.history.replaceState({}, "", url.toString());
      }

      okBtn.addEventListener("click", closePopup);
      modal.addEventListener("click", (e) => { if (e.target === modal) closePopup(); });

      if (success) openPopup("success", success);
      else if (error) openPopup("error", error);
    })();
</script>

</body>
</html>