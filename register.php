<?php
require_once 'includes/auth.php';
require_once 'includes/Database.php';

session_start();
// Initialize $pdo
$db = new Database();
$pdo = $db->getConnection();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'parent';
    
    // Validation
    if (empty($username)) $errors['username'] = "Username is required";
    if (empty($email)) $errors['email'] = "Email is required";
    if (!filter_var(value: $email, filter: FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format";
    if (empty($password)) $errors['password'] = "Password is required";
    if (strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors['confirm_password'] = "Passwords do not match";
    
    // Check if username or email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare(query: "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute(params: [$username, $email]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $errors['general'] = "Username or email already exists";
        }
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash(password: $password, algo: PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(query: "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute(params: [$username, $email, $hashed_password, $role])) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header(header: "Location: login.php");
            exit();
        } else {
            $errors['general'] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Survey System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .register-title {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .register-logo {
            text-align: center;
            margin-bottom: 20px;
            font-size: 48px;
            color: #3498db;
        }
        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .role-option {
            flex: 1;
            text-align: center;
        }
        .role-option input {
            display: none;
        }
        .role-option label {
            display: block;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-option input:checked + label {
            background: #3498db;
            color: white;
        }
        .role-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-logo">
            <i class="fas fa-user-plus"></i>
        </div>
        <h1 class="register-title">Create an Account</h1>
        
        <?php if (isset($errors['general'])): ?>
            <div class="error-message"><?php echo $errors['general']; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                <?php if (isset($errors['username'])): ?>
                    <div class="field-error"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="field-error"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="field-error"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="field-error"><?php echo $errors['confirm_password']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Account Type:</label>
                <div class="role-selector">
                    <div class="role-option">
                        <input type="radio" id="role_parent" name="role" value="parent" checked>
                        <label for="role_parent">
                            <div class="role-icon"><i class="fas fa-user-friends"></i></div>
                            Parent
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="role_teacher" name="role" value="teacher">
                        <label for="role_teacher">
                            <div class="role-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            Teacher
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="role_student" name="role" value="student">
                        <label for="role_student">
                            <div class="role-icon"><i class="fas fa-user-graduate"></i></div>
                            Student
                        </label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        
        <div class="login-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>