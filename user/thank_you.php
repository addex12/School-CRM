<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="survey-container">
        <h1>Thank You!</h1>
        <p>We appreciate your time and effort in completing the survey.</p>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
</body>
<?php require_once '../includes/footer.php';?>
</html>
