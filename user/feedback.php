<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Database connection
    $host = 'localhost';
    $user = 'flipperschool_crm';
    $pass = 'A25582067s_';
    $dbname = 'flipperschool_school_crm';

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    $user_id = $_SESSION['user_id'];
    $feedback = $_POST['feedback'];

    $conn->query("INSERT INTO feedback (user_id, feedback) VALUES ('$user_id', '$feedback')");
    $success = 'Feedback submitted successfully.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback and Inquiry</title>
</head>
<body>
    <h1>Feedback and Inquiry</h1>
    <?php if (isset($success)): ?>
        <p><?php echo $success; ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label for="feedback">Your Feedback:</label>
        <textarea id="feedback" name="feedback" required></textarea>
        <br>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
