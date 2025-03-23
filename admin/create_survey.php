<?php
session_start();
// ...existing code for admin authentication...

// ...existing code for database connection...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    // Additional fields can be added here...
    // $active      = isset($_POST['active']) ? '1' : '0';

    $sql = "INSERT INTO surveys (title, description, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $title, $description);
    $stmt->execute();

    header('Location: dashboard.php?survey_created=1');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Survey</title>
</head>
<body>
    <h1>Create a New Survey</h1>
    <form method="post">
        <label>Title:</label>
        <input type="text" name="title" required>
        <br>
        <label>Description:</label>
        <textarea name="description"></textarea>
        <br>
        <!-- Add more fields or customization as needed -->
        <button type="submit">Create Survey</button>
    </form>
</body>
</html>
