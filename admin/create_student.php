<?php
session_start();
// ...existing code for admin check...
// ...existing code for database connection...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name  = $_POST['student_name'] ?? '';
    $student_email = $_POST['student_email'] ?? '';
    // ...any additional fields...

    $sql = "INSERT INTO students (student_name, student_email, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $student_name, $student_email);
    $stmt->execute();

    header('Location: dashboard.php?student_created=1');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Student</title>
</head>
<body>
    <h1>Create Student</h1>
    <form method="post">
        <label>Name:</label>
        <input type="text" name="student_name" required>
        <br>
        <label>Email:</label>
        <input type="email" name="student_email">
        <br>
        <!-- ...additional fields... -->
        <button type="submit">Create Student</button>
    </form>
</body>
</html>
