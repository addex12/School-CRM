<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle create teacher logic here
    // Example: Save teacher data to the database
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Teacher</title>
    <link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
    <div class="container">
        <h1>Create Teacher</h1>
        <form method="post">
            <!-- Add form fields for creating a teacher -->
            <button type="submit">Create Teacher</button>
        </form>
    </div>
</body>
</html>
