<?php
require_once 'includes/auth.php';

// Destroy the session
session_destroy();

// Redirect to login page
header(header: "Location: login.php");
exit();
?>