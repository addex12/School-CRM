/**
 * Developer: Adugna Gizaw
 * Email: Gizawadugna@gmail.com
 * Phone: +251925582067
 * LinkedIn: eleganceict
 * Twitter: eleganceict1
 * GitHub: addex12
 *
 * File: functions.php
 * Description: Contains utility functions for the system.
 */

<?php
require_once 'db.php';

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
    define('SITE_NAME', getSystemSetting('site_name', 'Survey System'));
    
    // Theme color
    define('THEME_COLOR', getSystemSetting('theme_color', '#3498db'));
}

?>