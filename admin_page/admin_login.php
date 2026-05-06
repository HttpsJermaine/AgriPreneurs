<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="admin-green.css">
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: url('images/login-banner.png') center/cover no-repeat;
                /* #0c2f1c; */  
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
}
.overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 30, 10, 0.75);
    backdrop-filter: blur(2px);
}
.login-card {
    position: relative;
    z-index: 10;
    width: 360px;
    padding: 40px 30px;
    background: rgba(10, 40, 25, 0.85);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0, 40, 10, 0.5);
    text-align: center;
    color: #fff;
    animation: fadeIn 0.6s ease-out;
}
.logo img {
    width: 70px;
    margin-bottom: 15px;
    filter: drop-shadow(0 0 5px rgba(0, 255, 100, 0.4));
}
.login-card h2 {
    font-size: 26px;
    margin-bottom: 5px;
    font-weight: 600;
}
.login-card p {
    font-size: 14px;
    opacity: 0.85;
    margin-bottom: 25px;
}
.login-btn {
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
.login-btn:hover {
    background: #3ece75;
    box-shadow: 0 4px 16px rgba(70, 220, 130, 0.7);
}
.register-text {
    margin-top: 18px;
    font-size: 13px;
}

.register-text a {
    color: #8fe4ab;
    text-decoration: none;
}

.register-text a:hover {
    text-decoration: underline;
}
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(10px)}
    to {opacity: 1; transform: translateY(0)}
}
    </style>
</head>

<body>
    <div class="overlay"></div>
    <div class="login-card">
        <div class="logo">
            <img src="images/icon.png" alt="Logo">
        </div>

        <h2>Welcome, Admin!</h2>
        <p>Please log in to continue.</p>

        <a href="login.php"><button class="login-btn">Login</button></a>

        <p class="register-text">
            No account?
            <a href="register.php">Register here</a>
        </p>
    </div>
    
</body>
</html>
