<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id = $_POST['parent_id'] ?? 0;
    $feedback = $_POST['feedback'] ?? '';

    $sql = "INSERT INTO feedback (parent_id, feedback, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $parent_id, $feedback);
    $stmt->execute();

    header('Location: dashboard.php?feedback_created=1');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Feedback</title>
</head>
<body>
    <h1>Submit Feedback</h1>
    <form method="post">
        <label>Parent ID:</label>
        <input type="number" name="parent_id" required>
        <br>
        <label>Feedback:</label>
        <textarea name="feedback" required></textarea>
        <br>
        <button type="submit">Submit Feedback</button>
    </form>
</body>
</html>
