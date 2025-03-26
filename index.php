<?php
// ...existing code...

// Include the file where the Auth class is defined
require_once 'auth.php';

// Initialize $auth
$auth = new Auth();

// Check if the user is logged in
if ($auth->isLoggedIn()) {
    // ...existing code...
} else {
    header("Location: login.php");
    exit();
}

// ...existing code...
?>
