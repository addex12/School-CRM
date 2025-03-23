<?php
/**
 * Parent Survey Management
 * 
 * This script handles the management and setup of parent surveys.
 * 
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin/login.php");
    exit();
}

require_once '../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surveyTitle = $_POST['surveyTitle'];
    $surveyDescription = $_POST['surveyDescription'];

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO parent_surveys (title, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $surveyTitle, $surveyDescription);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    $success = "Survey created successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parent Survey Management</title>
    <link rel="stylesheet" type="text/css" href="/public/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Parent Survey Management</h1>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="surveyTitle">Survey Title:</label>
            <input type="text" id="surveyTitle" name="surveyTitle" required><br>
            <label for="surveyDescription">Survey Description:</label>
            <textarea id="surveyDescription" name="surveyDescription" required></textarea><br>
            <button type="submit">Create Survey</button>
        </form>
        <button onclick="window.location.href='/admin/dashboard.php'">Back to Dashboard</button>
    </div>
</body>
</html>
