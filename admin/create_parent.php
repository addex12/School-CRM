<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle create parent logic here
    // Example: Save parent data to the database
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Parent</title>
    <link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
    <div class="container">
        <h1>Create Parent</h1>
        <form method="post">
            <!-- Add form fields for creating a parent -->
            <button type="submit">Create Parent</button>
        </form>
    </div>
</body>
</html>
