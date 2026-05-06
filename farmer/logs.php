<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs</title>
    <link rel="stylesheet" href="css/logs.css">
</head>
<body>

<div class="sidebar">
    <h2 class="logo">🌿 PLAMAL</h2>

    <div class="farmer-info">
        <img src="images/farmer-icon.png" alt="Farmer" class="avatar">
        <p class="farmer-name">Farmer Jerman</p>
    </div>

    <nav class="menu">
        <a href="farmer_dashboard.php" class="menu-item">🏚️ Dashboard</a>
        <a href="orders.php" class="menu-item">🧾 Orders</a>
        <a href="stocks.php" class="menu-item">📊 Stocks</a>
        <a href="earnings.php" class="menu-item">💰 Earnings</a>
        <a href="logout.php" class="menu-item logout">🚪 Logout</a>
    </nav>
</div>

    <div class="logs-container">

    <h2 class="page-title">Activity Logs</h2>
    <p class="page-subtitle">History ng pag gamit ng plataforma</p>

    <!-- Filter Options -->
    <div class="logs-filters">
        
        <label>Pumili ng Petsa: <input type="date"></label>
    </div>

    <!-- Logs Table -->
    <div class="logs-table">
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>

            <tbody>
                <tr class="logins">
                    <td class="log-icon">Login</td>
                    <td>User logged into the system</td>
                    <td>Nov 18, 2025</td>
                    <td>08:42 AM</td>
                </tr>

                <tr class="logouts">
                    <td class="log-icon">Logout</td>
                    <td>Added new product: "Fresh Tomatoes"</td>
                    <td>Nov 17, 2025</td>
                    <td>04:15 PM</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>