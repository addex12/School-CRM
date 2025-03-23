<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */
?>
<link rel="stylesheet" type="text/css" href="../style.css">
<?php
include('../includes/header.php'); 
?>
<h1>Admin Dashboard</h1>
<nav>
    <ul>
        <li><a href="users.php">Manage Users</a></li>
        <li><a href="surveys.php">Manage Surveys</a></li>
        <li><a href="communications.php">Communications & Chat Setup</a></li>
        <li><a href="settings.php">Settings</a></li>
        <!-- Add other navigation links as necessary -->
    </ul>
</nav>
<!-- Add necessary JavaScript files -->
<script src="../js/dashboard.js"></script>
<?php include('../includes/footer.php'); ?>