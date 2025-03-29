<?php
require_once 'db.php';
require_once 'vendor/autoload.php'; // Ensure PHPMailer is loaded

// Register new user
function registerUser($username, $email, $password, $role = 'parent') {
    global $pdo;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $hashed_password, $role]);
}

// Login user
function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Get all active surveys
function getActiveSurveys() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM surveys WHERE is_active = TRUE");
    return $stmt->fetchAll();
}

// Get survey questions
function getSurveyQuestions($survey_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ?");
    $stmt->execute([$survey_id]);
    return $stmt->fetchAll();
}

// Get a system setting
function getSystemSetting($key, $default = '') {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    
    return $result !== false ? $result : $default;
}

// Apply system settings to the application
function applySystemSettings() {
    // Timezone
    $timezone = getSystemSetting('timezone', 'UTC');
    date_default_timezone_set($timezone);
    
    // Site name
    define('SITE_NAME', getSystemSetting('site_name', 'School CRM System'));
    
    // Theme color
    define('THEME_COLOR', getSystemSetting('theme_color', '#3498db'));
    
    // Base URL
    define('BASE_URL', getSystemSetting('base_url', 'http://crm.flipperschool.com'));
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit();
}

function formatDate($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}

function sendResetPasswordEmail($email, $resetToken) {
    global $pdo;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Fetch SMTP settings from the database
        $smtpHost = getSystemSetting('smtp_host', 'smtp.gmail.com');
        $smtpPort = getSystemSetting('smtp_port', 587);
        $smtpUsername = getSystemSetting('smtp_username', 'your-email@gmail.com');
        $smtpPassword = getSystemSetting('smtp_password', 'your-email-password');
        $smtpSecure = getSystemSetting('smtp_secure', 'tls');

        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = $smtpSecure;
        $mail->Port = $smtpPort;

        // Recipients
        $mail->setFrom($smtpUsername, 'School CRM');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $mail->Body = "Click the link below to reset your password:<br><br>
                       <a href='" . BASE_URL . "/reset_password.php?token=$resetToken'>Reset Password</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

?>