<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>School CRM</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="home.php">Home</a>
            <?php if(isLoggedIn()){ ?>
                <a href="parent_dashboard.php">Dashboard</a>
            <?php } ?>
        </nav>
    </header>
