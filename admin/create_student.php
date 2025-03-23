<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle create student logic here
    // Example: Save student data to the database
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Student</title>
    <link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
    <div class="container">
        <h1>Create Student</h1>
        <form method="post">
            <!-- Add form fields for creating a student -->
            <button type="submit">Create Student</button>
        </form>
    </div>
</body>
</html>
