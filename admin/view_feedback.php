<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$feedbacks = $conn->query("SELECT id, parent_id, feedback, created_at FROM feedback");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Feedback and Concerns</title>
</head>
<body>
    <h1>Feedback and Concerns</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Parent ID</th>
            <th>Feedback</th>
            <th>Created At</th>
        </tr>
        <?php while ($feedback = $feedbacks->fetch_assoc()): ?>
        <tr>
            <td><?php echo $feedback['id']; ?></td>
            <td><?php echo $feedback['parent_id']; ?></td>
            <td><?php echo htmlspecialchars($feedback['feedback']); ?></td>
            <td><?php echo $feedback['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
