<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="login.css">
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: url("images/login-banner.png") center/cover no-repeat;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.register-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    position: relative;
    z-index: 2;
}

.register-box {
    background: rgba(10, 40, 25, 0.88);
    padding: 40px 35px;
    width: 340px;
    border-radius: 22px;
    text-align: center;
    color: #fff;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
}
.icon-circle img {
    width: 70px;
    margin-bottom: 15px;
    filter: drop-shadow(0 0 5px rgba(0, 255, 100, 0.4));
}

h2 {
    font-size: 22px;
    margin-bottom: 5px;
}

.subtitle {
    font-size: 15px;
    opacity: 0.8;
    margin-bottom: 25px;
}

.input-field {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    outline: none;
}
.register-btn {
    width: 100%;
    padding: 14px 0;
    border: none;
    background: #32b768;   
    color: #002d12;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.25s ease-out;
    box-shadow: 0 4px 12px rgba(50, 183, 104, 0.5);
}
.register-btn:hover {
    background: #3ece75;
    box-shadow: 0 4px 16px rgba(70, 220, 130, 0.7);
}
.arrow-btn {
    margin-top: 15px;
    width: 80px;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 20px;
    background: #32b768;
    color: #fff;
    cursor: pointer;
    transition: 0.2s ease;
    box-shadow: 0 4px 12px rgba(50, 183, 104, 0.5);
}

.arrow-btn:hover {
    background: #3ece75;
}
.helper-text {
    margin-top: 20px;
    font-size: 13px;
    opacity: 0.9;
}

.create-link {
    font-size: 13px;
    color: #8fe4ab;
    text-decoration: none;
}

.create-link:hover {
    text-decoration: underline;
}
.overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 30, 10, 0.75);
    backdrop-filter: blur(2px);
}
.show-password {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 10px 0 20px;
    color: #d4e8d9;
    font-size: 14px;
}

.show-password input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.show-password label {
    cursor: pointer;
    user-select: none;
}
    </style>
</head>

<body>
<div class="register-container">
        <div class="register-box">
            <div class="icon-circle">
                <img src="images/icon.png" alt="Icon">
            </div>

            <h2>Create Account</h2>
            <p class="subtitle">Register to continue</p>

            <input type="text" class="input-field" placeholder="Fullname" required>
            <!--<input type="text" class="input-field" placeholder="Username" required>-->
            <input type="text" class="input-field" placeholder="Email" required>
            <input type="password" class="input-field" id="password" placeholder="Password" required>
            <input type="password" class="input-field" id="confirmPassword" placeholder="Confirm Password" required>

            <div class="show-password">
            <input type="checkbox" id="togglePassword">
            <label for="togglePassword">Show Password</label>
            </div>

            <a href="register_process.php"><button class="register-btn">Register</button></a>

            <p class="helper-text">Already have an account?</p>
            <a href="login.php" class="create-link">Sign In</a>

        </div>

    </div>
<div class="overlay"></div>

<script>
    const toggle = document.getElementById("togglePassword");
    const pass1 = document.getElementById("password");
    const pass2 = document.getElementById("confirmPassword");

    toggle.addEventListener("change", function () {
        const type = toggle.checked ? "text" : "password";
        pass1.type = type;
        pass2.type = type;
    });
</script>
</body>
</html>
