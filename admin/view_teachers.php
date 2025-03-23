<?php
session_start();
// ...existing code for admin check...
// ...existing code for database connection...

$teachers = $conn->query("SELECT id, teacher_name, teacher_email, created_at FROM teachers");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Teachers</title>
</head>
<body>
    <h1>Teachers</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Created At</th>
        </tr>
        <?php while ($teacher = $teachers->fetch_assoc()): ?>
        <tr>
            <td><?php echo $teacher['id']; ?></td>
            <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
            <td><?php echo htmlspecialchars($teacher['teacher_email']); ?></td>
            <td><?php echo $teacher['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
