<?php
// backend/login_handler_debug.php
session_start();
require_once __DIR__.'/../includes/db.php';

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

file_put_contents('login_debug.log', "Script started\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents('login_debug.log', "POST request received\n", FILE_APPEND);
    
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    file_put_contents('login_debug.log', "Email: $email\n", FILE_APPEND);

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            file_put_contents('login_debug.log', "User found: ".print_r($user, true)."\n", FILE_APPEND);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role_id' => $user['role_id'],
                    'full_name' => $user['full_name']
                ];

                file_put_contents('login_debug.log', "Session data set\n", FILE_APPEND);
                file_put_contents('login_debug.log', "Role ID: ".$user['role_id']."\n", FILE_APPEND);

                // Immediate JavaScript redirect for debugging
                echo '<script>';
                if ($user['role_id'] == 1) {
                    echo 'console.log("Redirecting to admin dashboard");';
                    echo 'window.location.href = "../admin/dashboard.php";';
                } else {
                    echo 'console.log("Redirecting to user dashboard");';
                    echo 'window.location.href = "../user/dashboard.php";';
                }
                echo '</script>';
                exit();
            } else {
                file_put_contents('login_debug.log', "Password verification failed\n", FILE_APPEND);
            }
        } else {
            file_put_contents('login_debug.log', "User not found\n", FILE_APPEND);
        }

        $_SESSION['error'] = "Invalid email or password";
        echo '<script>console.log("Redirecting back to login"); window.location.href = "../login.php";</script>';
        exit();
    } catch (PDOException $e) {
        file_put_contents('login_debug.log', "Database error: ".$e->getMessage()."\n", FILE_APPEND);
        $_SESSION['error'] = "System error. Please try again later.";
        echo '<script>console.log("Database error occurred"); window.location.href = "../login.php";</script>';
        exit();
    }
}

echo '<script>console.log("Non-POST request"); window.location.href = "../login.php";</script>';
exit();