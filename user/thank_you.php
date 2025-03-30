<?php
require_once 'includes/header.php';
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .thank-you-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .thank-you-container h1 {
            font-size: 2em;
            color: #333;
            margin-bottom: 20px;
        }
        .thank-you-container p {
            font-size: 1.2em;
            color: #555;
            margin-bottom: 30px;
        }
        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>
    <main>
        <div class="thank-you-container">
            <h1>Thank You!</h1>
            <p>We appreciate your time and effort in completing the survey.</p>
            <a href="dashboard.php" class="btn-primary">Back to Dashboard</a>
        </div>
    </main>
    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>
</body>
</html>
