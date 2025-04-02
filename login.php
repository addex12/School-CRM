<?php
// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define root path if not defined
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__));

// Check if required files exist before including
$required_files = [
    ROOT_PATH . '/includes/db.php',
    ROOT_PATH . '/includes/functions.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("Critical error: Missing required file - " . basename($file));
    }
}

// Include required files
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';

// Initialize variables
$error = '';
$username = '';
$page_title = "Login - School System";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . getDashboardPath($_SESSION['role_id']));
    exit();
}

// Process login form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Check database connection
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new Exception("Database connection failed");
            }
            
            // Prepare SQL statement
            $stmt = $pdo->prepare("
                SELECT u.*, r.dashboard_path 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE (u.username = :username OR u.email = :username) 
                LIMIT 1
            ");
            
            // Execute with named parameters
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    if ($user['is_active'] == 1) {
                        // Regenerate session ID to prevent fixation
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role_id'] = $user['role_id'];
                        $_SESSION['dashboard_path'] = $user['dashboard_path'];
                        
                        // Update last login
                        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                        $updateStmt->execute([':id' => $user['id']]);
                        
                        // Redirect to dashboard
                        header("Location: " . $user['dashboard_path']);
                        exit();
                    } else {
                        $error = 'Your account is inactive. Please contact support.';
                    }
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            error_log("Database error in login.php: " . $e->getMessage());
            $error = 'A system error occurred. Please try again later.';
        } catch (Exception $e) {
            error_log("General error in login.php: " . $e->getMessage());
            $error = 'A system error occurred. Please try again later.';
        }
    }
}

// Function to get dashboard path
function getDashboardPath($role_id) {
    // Default path if lookup fails
    $default_path = '/user/dashboard.php';
    
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT dashboard_path FROM roles WHERE id = :role_id");
        $stmt->execute([':role_id' => $role_id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $role && !empty($role['dashboard_path']) ? $role['dashboard_path'] : $default_path;
    } catch (Exception $e) {
        error_log("Error getting dashboard path: " . $e->getMessage());
        return $default_path;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #7f8c8d;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .input-group {
            position: relative;
        }
        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
        .form-control {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .error-message {
            color: #e74c3c;
            padding: 10px;
            margin-bottom: 1rem;
            background-color: #fadbd8;
            border-radius: 4px;
            text-align: center;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c8d;
        }
        .login-footer a {
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>School System</h1>
            <p>Please sign in to continue</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            
            <button type="submit" class="btn">Sign In</button>
        </form>
        
        <div class="login-footer">
            <p><a href="forgot-password.php">Forgot your password?</a></p>
        </div>
    </div>
</body>
</html>