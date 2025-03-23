<?php
session_start();
// ...existing code for admin check...
// ...existing code for database connection...

$students = $conn->query("SELECT id, student_name, student_email, created_at FROM students");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Students</title>
</head>
<body>
    <h1>Students</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Created At</th>
        </tr>
        <?php while ($student = $students->fetch_assoc()): ?>
        <tr>
            <td><?php echo $student['id']; ?></td>
            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
            <td><?php echo htmlspecialchars($student['student_email']); ?></td>
            <td><?php echo $student['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
