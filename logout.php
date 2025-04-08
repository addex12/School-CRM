<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

require_once 'includes/auth.php';

// Destroy the session
session_destroy();

// Redirect to login page
header(header: "Location: login.php");
exit();
?>