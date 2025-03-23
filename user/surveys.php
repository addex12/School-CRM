<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$host = 'localhost';
$user = 'flipperschool_crm';
$pass = 'A25582067s_';
$dbname = 'flipperschool_school_crm';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$surveys = $conn->query("SELECT id, title, description FROM surveys WHERE active=1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Surveys</title>
</head>
<body>
    <h1>Active Surveys</h1>
    <?php while ($survey = $surveys->fetch_assoc()): ?>
        <h2><?php echo $survey['title']; ?></h2>
        <p><?php echo $survey['description']; ?></p>
        <a href="submit_survey.php?id=<?php echo $survey['id']; ?>">Take Survey</a>
    <?php endwhile; ?>
</body>
</html>
