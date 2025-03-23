<?php
session_start();
// ...existing code for admin authentication...
// ...existing code for database connection...

$surveys = $conn->query("SELECT id, title, description, created_at FROM surveys");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Surveys</title>
</head>
<body>
    <h1>Surveys</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Description</th>
            <th>Created At</th>
        </tr>
        <?php while ($survey = $surveys->fetch_assoc()): ?>
        <tr>
            <td><?php echo $survey['id']; ?></td>
            <td><?php echo htmlspecialchars($survey['title']); ?></td>
            <td><?php echo htmlspecialchars($survey['description']); ?></td>
            <td><?php echo $survey['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <!-- Additional options (edit/delete) can be included for survey management -->
</body>
</html>
