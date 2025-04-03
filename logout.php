<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

require_once 'includes/auth.php';

session_start();
session_unset();
session_destroy();

header("Location: /login.php");
exit();
?>