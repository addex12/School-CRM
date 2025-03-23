<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .header-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            padding: 10px 20px;
            color: white;
        }
        .header-container h1 {
            margin: 0;
            font-size: 1.5em;
        }
        .header-container nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
        }
        .header-container nav ul li {
            margin-left: 20px;
        }
        .header-container nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }
        .header-container nav ul li a:hover {
            text-decoration: underline;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }
            .header-container nav ul {
                flex-direction: column;
                width: 100%;
            }
            .header-container nav ul li {
                margin-left: 0;
                margin-bottom: 10px;
            }
            .header-container nav ul li a {
                font-size: 18px;
            }
        }
    </style>
    <title>Admin Dashboard</title>
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <img src="/path/to/your/logo.png" alt="Logo">
            <h1>Admin Dashboard</h1>
        </div>
        <nav>
            <ul>
                <li><a href="/admin/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/admin/users"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="/admin/settings"><i class="fas fa-cogs"></i> Settings</a></li>
                <li><a href="/admin/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <li><a href="users.php" class="nav-link"><i class="fas fa-user-cog"></i> Manage Users</a></li>
                <li><a href="surveys.php" class="nav-link"><i class="fas fa-poll"></i> Manage Surveys</a></li>
                <li><a href="communications.php" class="nav-link"><i class="fas fa-envelope"></i> Communications Setup</a></li>
                <li><a href="settings.php" class="nav-link"><i class="fas fa-tools"></i> Settings</a></li>
                <!-- Add other navigation links as necessary -->
            </ul>
        </nav>
    </div>
</header>
<!-- ...existing code... -->