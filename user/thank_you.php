<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
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
            color: #007bff;
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
        .main-content-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 0 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="thank-you-container">
            <h1>
                <i class="fas fa-check-circle"></i> Thank You!
            </h1>
            <p>We appreciate your time and effort in completing the survey.</p>
            <a href="dashboard.php" class="btn-primary">Back to Dashboard</a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
