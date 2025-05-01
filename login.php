<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
// Start output buffering
ob_start();

// Start session before any session usage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration first
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Show public announcements on the login page (even before login)
try {
    $announcements = $pdo->query("SELECT title, content, created_at FROM announcements WHERE is_public = 1 ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $announcements = [];
}

// Check if user is already logged in
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

// Ensure error is only set after form submission and validation failure
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            // Get user from database
            $stmt = $pdo->prepare("SELECT id, username, password, role_id, active FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['active'] != 1) {
                    $error = "Your account is not active. Please contact the administrator.";
                } else {
                    // Update last_login, last_active, and set online=1
                    $pdo->prepare("UPDATE users SET last_login = NOW(), last_active = NOW(), online = 1 WHERE id = ?")->execute([$user['id']]);

                    // Log the login action to audit_logs
                    try {
                        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $stmt->execute([
                            $user['id'],
                            'login',
                            'User logged in',
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                        ]);
                    } catch (Exception $e) {
                        error_log('Audit log insert failed (login): ' . $e->getMessage());
                    }

                    // --- Log to file ---
                    $logDir = __DIR__ . '/logs';
                    if (!is_dir($logDir)) {
                        mkdir($logDir, 0777, true);
                    }
                    $logFile = $logDir . '/user_activity.log';
                    $logEntry = sprintf(
                        "[%s] LOGIN: user_id=%s, username=%s, ip=%s\n",
                        date('Y-m-d H:i:s'),
                        $user['id'],
                        $user['username'],
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    );
                    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
                    // --- End log to file ---

                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['activity_tracking'] = true; // Enable activity tracking

                    // Set remember me cookie if checked
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                        setcookie('remember_token', $token, $expiry, '/');

                        // Store token in database
                        $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?")
                            ->execute([$token, date('Y-m-d H:i:s', $expiry), $user['id']]);
                    }

                    // Redirect based on role and active status
                    if ($user['role_id'] == 1 && $user['active'] == 1) {
                        header("Location: " . BASE_URL . "/admin/dashboard.php");
                    } else {
                        header("Location: " . BASE_URL . "/user/dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}

// Ensure `$user` is checked before accessing its properties
if (isset($user) && is_array($user)) {
    // Add this after successful login, before redirect
    $_SESSION['tracking_enabled'] = true;
    $_SESSION['tracking_start'] = time();

    // Set tracking cookie with user ID (hashed for security)
    $trackingToken = hash('sha256', $user['id'] . microtime());
    setcookie('tracking_token', $trackingToken, time() + (86400 * 30), '/', '', true, true);

    // Store in database
    $pdo->prepare("UPDATE users SET tracking_token = ? WHERE id = ?")
        ->execute([$trackingToken, $user['id']]);
} else {
    // If user is not logged in, set default values
    $user = null;
}

// Fetch site logo and name from settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_logo', 'site_name')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteLogo = isset($settings['site_logo']) && $settings['site_logo'] ? $settings['site_logo'] : 'assets/images/default-logo.png';
    $siteName = isset($settings['site_name']) && $settings['site_name'] ? $settings['site_name'] : 'School CRM';
} catch (Exception $e) {
    $siteLogo = 'assets/images/default-logo.png';
    $siteName = 'School CRM';
}
// Ensure $siteLogo and $siteName are always strings
if (!$siteLogo) $siteLogo = 'assets/images/default-logo.png';
if (!$siteName) $siteName = 'School CRM';

// Always define $allowRegistration to avoid undefined variable warning
$allowRegistration = true;
if (isset($settings['allow_user_registration']) && $settings['allow_user_registration'] == '0') {
    $allowRegistration = false;
}

// Use fixed filenames for images as set in admin/settings.php
$siteLogo = 'uploads/logo.png';
$loginBgImage = 'uploads/bg.png';
$siteBanner = 'uploads/banner.png';
$siteIcon = 'uploads/icon.png';

// Fetch all login background images for slider
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'login_bg_images'");
    $bgImagesSetting = $stmt->fetchColumn();
    $loginBgImages = $bgImagesSetting ? json_decode($bgImagesSetting, true) : [];
} catch (Exception $e) {
    $loginBgImages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!--
        Developer: Adugna Gizaw
        Email: gizawadugna@gmail.com
        LinkedIn: https://www.linkedin.com/in/eleganceict
        Twitter: https://twitter.com/eleganceict1
        GitHub: https://github.com/addex12
        Purpose: Login page for School CRM, styled with adugna- prefix for all custom styles.
    -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School CRM</title>
    <!-- Favicon -->
    <link rel="icon" href="<?= htmlspecialchars($siteIcon) ?>" type="image/png">
    <!-- Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!--
        Custom Styles: All classes and variables are prefixed with adugna- for patenting.
        Styles are compact, ERPNext-inspired, and fully responsive.
    -->
    <style>
        /* Adugna Gizaw: Color variables for consistent theming */
        :root {
            --adugna-primary: #4f46e5;
            --adugna-primary-dark: #4338ca;
            --adugna-secondary: #10b981;
            --adugna-danger: #ef4444;
            --adugna-light: #f9fafb;
            --adugna-dark: #111827;
            --adugna-gray: #6b7280;
            --adugna-gray-light: #e5e7eb;
            --adugna-card-radius: 0.5rem;
            --adugna-transition: 0.15s cubic-bezier(.4,0,.2,1);
        }
        /* Adugna Gizaw: Reset and base styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--adugna-light);
            color: var(--adugna-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Adugna Gizaw: Responsive flex container for login page */
        .adugna-login-container {
            display: flex;
            flex-grow: 1;
            min-height: 100vh;
            flex-direction: row;
            background: var(--adugna-light);
        }
        /* Adugna Gizaw: Left section with gradient and announcements */
        .adugna-login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--adugna-primary), var(--adugna-primary-dark));
            color: #fff;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            min-width: 0;
        }
        .adugna-login-left::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -40%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            transform: rotate(30deg);
            z-index: 0;
        }
        /* Adugna Gizaw: Right section with login card and background image only (no blue overlay) */
        .adugna-login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
            min-width: 0;
            background: #f9fafb;
            position: relative;
            overflow: hidden;
        }
        .login-bg-slider {
            position: absolute;
            inset: 0;
            z-index: 0;
            width: 100%;
            height: 100%;
        }
        .login-bg-slide {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1s;
        }
        .login-bg-slide.active {
            opacity: 1;
            z-index: 1;
        }
        .adugna-card { 
            background: #fff;
            border-radius: var(--adugna-card-radius);
            box-shadow: 0 2px 12px rgba(79,70,229,0.08), 0 1.5px 4px rgba(0,0,0,0.03);
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            width: 100%;
            max-width: 370px;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            position: relative;
            z-index: 2;
        }
        /* Adugna Gizaw: Logo and site name */
        .adugna-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .adugna-logo img {
            height: 38px;
            width: auto;
        }
        .adugna-logo h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--adugna-primary);
            letter-spacing: 0.01em;
        }
        /* Adugna Gizaw: Welcome text */
        .adugna-welcome-text {
            margin-bottom: 0.5rem;
        }
        .adugna-welcome-text h2 {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
            color: var(--adugna-dark);
        }
        .adugna-welcome-text p {
            color: var(--adugna-gray);
            font-size: 0.97rem;
        }
        /* Adugna Gizaw: Form styles */
        .adugna-login-form { width: 100%; }
        .adugna-form-group { margin-bottom: 0.85rem; }
        .adugna-form-group {
            position: relative; /* For icon positioning */
        }
        .adugna-password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 1.1em;
            z-index: 2;
            background: none;
            border: none;
            padding: 0;
            outline: none;
        }
        .adugna-form-group input[type="password"],
        .adugna-form-group input[type="text"] {
            padding-right: 2.2em; /* Space for icon */
        }
        .adugna-form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 500;
            color: var(--adugna-dark);
            font-size: 0.98rem;
        }
        .adugna-input-wrapper {
            position: relative;
        }
        .adugna-input-wrapper i {
            position: absolute;
            left: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--adugna-gray);
            font-size: 1em;
        }
        .adugna-form-control {
            width: 100%;
            padding: 0.55rem 0.9rem 0.55rem 2.1rem;
            border: 1px solid var(--adugna-gray-light);
            border-radius: 0.35rem;
            font-size: 0.98rem;
            transition: border-color var(--adugna-transition);
            background: #f8fafc;
        }
        .adugna-form-control:focus {
            outline: none;
            border-color: var(--adugna-primary);
            background: #fff;
            box-shadow: 0 0 0 2px rgba(79,70,229,0.09);
        }
        /* Adugna Gizaw: Options row (remember me, forgot password) */
        .adugna-form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.1rem;
            font-size: 0.93rem;
        }
        .adugna-remember-me {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .adugna-remember-me input {
            width: 0.95rem;
            height: 0.95rem;
            accent-color: var(--adugna-primary);
        }
        .adugna-forgot-password {
            color: var(--adugna-primary);
            text-decoration: none;
            font-size: 0.93rem;
            transition: color var(--adugna-transition);
        }
        .adugna-forgot-password:hover {
            color: var(--adugna-primary-dark);
            text-decoration: underline;
        }
        /* Adugna Gizaw: Compact button style, ERPNext-inspired */
        .adugna-btn {
            width: 100%;
            padding: 0.58rem 0;
            border: none;
            border-radius: 0.35rem;
            font-size: 1.01rem;
            font-weight: 600;
            cursor: pointer;
            background: var(--adugna-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: background var(--adugna-transition), box-shadow var(--adugna-transition);
            box-shadow: 0 1.5px 4px rgba(79,70,229,0.07);
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: var(--adugna-primary-dark);
        }
        .adugna-btn i {
            font-size: 1em;
            margin-left: 0.1em;
        }
        /* Adugna Gizaw: Error message style */
        .adugna-error-message {
            background: rgba(239,68,68,0.09);
            color: var(--adugna-danger);
            padding: 0.6rem 0.9rem;
            border-radius: 0.35rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.97rem;
        }
        /* Adugna Gizaw: Footer style */
        .adugna-login-footer {
            margin-top: 0.7rem;
            text-align: center;
            color: var(--adugna-gray);
            font-size: 0.95rem;
        }
        .adugna-login-footer a {
            color: var(--adugna-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--adugna-transition);
        }
        .adugna-login-footer a:hover {
            color: var(--adugna-primary-dark);
            text-decoration: underline;
        }
        /* Adugna Gizaw: Illustration styles */
        .adugna-illustration {
            max-width: 100%;
            margin-bottom: 1.2rem;
            z-index: 1;
        }
        .adugna-illustration img {
            max-width: 100%;
            height: auto;
        }
        .adugna-illustration-text {
            margin-top: 0.5rem;
            z-index: 1;
        }
        .adugna-illustration-text h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: #fff;
        }
        .adugna-illustration-text p {
            font-size: 0.97rem;
            color: #e0e7ff;
        }
        /* Adugna Gizaw: Announcements card */
        .adugna-public-announcements {
            max-width: 370px;
            margin: 1.5rem auto 0 auto;
            background: rgba(255,255,255,0.13);
            border-radius: 0.5rem;
            padding: 1.1rem 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            color: #fff;
            z-index: 1;
        }
        .adugna-public-announcements h3 {
            color: #fff;
            margin-bottom: 0.7rem;
            text-align: center;
            font-size: 1.05rem;
        }
        .adugna-public-announcements strong {
            color: #ffeb3b;
            font-size: 0.98rem;
        }
        .adugna-public-announcements span {
            color: #f0f0f0;
            font-size: 0.93em;
        }
        .adugna-public-announcements div {
            margin-top: 0.3em;
            color: #fff;
            font-size: 0.97em;
        }
        /* Adugna Gizaw: Responsive adjustments for all screens */
        @media (max-width: 1024px) {
            .adugna-login-container { flex-direction: column; }
            .adugna-login-left, .adugna-login-right { min-height: auto; }
            .adugna-login-left { padding: 1.2rem 0.7rem; }
            .adugna-login-right { padding: 1.2rem 0.7rem; }
        }
        @media (max-width: 600px) {
            .adugna-card, .adugna-public-announcements { max-width: 98vw; }
            .adugna-login-left, .adugna-login-right { padding: 0.7rem 0.2rem; }
            .adugna-logo img { height: 32px; }
            .adugna-logo h1 { font-size: 1.05rem; }
        }
        @media (max-width: 400px) {
            .adugna-card, .adugna-public-announcements { padding: 1rem 0.3rem; }
        }
    </style>
</head>
<body>
    <!--
        Adugna Gizaw: Main login container, split into left (info/announcements) and right (login form)
    -->
    <div class="adugna-login-container">
        <!-- Left: Illustration and Announcements -->
        <div class="adugna-login-left">
            <div class="adugna-illustration">
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Site Logo" style="height: 60px;">
            </div>
            <div class="adugna-illustration-text">
                <h3>Flipper International School Customer Relationship Management System</h3>
                <p>Comprehensive school management solution for administrators, teachers, and students</p>
            </div>
            <!-- Adugna Gizaw: Public Announcements Card -->
            <?php if (!empty($announcements)): ?>
                <div class="adugna-public-announcements">
                    <h3>
                        <i class="fas fa-bullhorn" style="font-size:1em;"></i> Announcements
                    </h3>
                    <?php foreach ($announcements as $ann): ?>
                        <div style="margin-bottom: 1.1rem;">
                            <strong><?php echo htmlspecialchars($ann['title']); ?></strong><br>
                            <span><?php echo date('M j, Y g:i A', strtotime($ann['created_at'])); ?></span>
                            <div><?php echo nl2br(htmlspecialchars($ann['content'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Right: Login Card -->
        <div class="adugna-login-right">
            <?php if (!empty($loginBgImages)): ?>
            <div class="login-bg-slider" id="loginBgSlider">
                <?php foreach ($loginBgImages as $idx => $img): ?>
                    <div class="login-bg-slide<?= $idx === 0 ? ' active' : '' ?>" style="background-image:url('uploads/<?= htmlspecialchars($img) ?>');"></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="adugna-card">
                <!-- Adugna Gizaw: Logo and site name -->
                <div class="adugna-logo">
                    <img src="<?php echo htmlspecialchars($siteLogo); ?>" alt="Site Logo">
                    <h1><?php echo htmlspecialchars($siteName); ?></h1>
                </div>
                <!-- Adugna Gizaw: Welcome text -->
                <div class="adugna-welcome-text">
                    <h2>Welcome Back!</h2>
                    <p>Please sign in to continue to your account</p>
                </div>
                <!-- Adugna Gizaw: Error message if login fails -->
                <?php if ($error): ?>
                    <div class="adugna-error-message">
                        <i class="fas fa-exclamation-circle" style="font-size:1em;"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                <!-- Adugna Gizaw: Login form -->
                <form method="POST" action="" class="adugna-login-form">
                    <div class="adugna-form-group">
                        <label for="username">Username</label>
                        <div class="adugna-input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" class="adugna-form-control" placeholder="Enter your username" required autocomplete="username">
                        </div>
                    </div>
                    <div class="adugna-form-group">
                        <label for="password">Password</label>
                        <div class="adugna-input-wrapper" style="position:relative;">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="adugna-form-control" placeholder="Enter your password" required autocomplete="current-password">
                            <button type="button" class="adugna-password-toggle" tabindex="-1" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="adugna-form-options">
                        <div class="adugna-remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember" style="margin-bottom:0;">Remember me</label>
                        </div>
                        <a href="forgot_password.php" class="adugna-forgot-password">Forgot password?</a>
                    </div>
                    <button type="submit" class="adugna-btn" id="login-btn">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                <!-- Adugna Gizaw: Footer with registration link -->
                <div class="adugna-login-footer">
                    <?php if ($allowRegistration): ?>
                        <p>Don't have an account? <a href="register.php">Create account</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!--
        Adugna Gizaw: Interactive scripts for activity tracking and UI enhancements.
        All code is commented for clarity.
    -->
    <script>
        // Adugna Gizaw: Password show/hide toggle for better UX
        function togglePassword(inputId, btn) {
            var input = document.getElementById(inputId);
            var icon = btn.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            var passwordInput = document.getElementById('password');
            var togglePassword = document.getElementById('togglePassword');
            var togglePasswordIcon = document.getElementById('togglePasswordIcon');
            if (passwordInput && togglePassword && togglePasswordIcon) {
                togglePassword.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        togglePasswordIcon.classList.remove('fa-eye');
                        togglePasswordIcon.classList.add('fa-eye-slash');
                        togglePassword.setAttribute('aria-label', 'Hide password');
                    } else {
                        passwordInput.type = 'password';
                        togglePasswordIcon.classList.remove('fa-eye-slash');
                        togglePasswordIcon.classList.add('fa-eye');
                        togglePassword.setAttribute('aria-label', 'Show password');
                    }
                });
            }
            // Adugna Gizaw: Focus username on load for accessibility
            document.getElementById('username')?.focus();
        });
        // Enhanced Pre-Login Activity Tracking
        document.addEventListener('DOMContentLoaded', function() {
            // Unique session ID for anonymous tracking (until login)
            const sessionId = 'anon_' + Math.random().toString(36).substring(2, 15);
            let startTime = new Date().toISOString();

            // Track initial page load
            trackActivity('page_visit', {
                session_id: sessionId,
                page: window.location.pathname,
                referrer: document.referrer,
                user_agent: navigator.userAgent,
                screen_resolution: `${window.screen.width}x${window.screen.height}`,
                ip_address: '<?php echo $_SERVER['REMOTE_ADDR'] ?? 'unknown'; ?>'
            });

            // Track form interactions
            document.getElementById('username')?.addEventListener('input', (e) => {
                trackActivity('form_input', {
                    session_id: sessionId,
                    field: 'username',
                    partial_input: e.target.value.substring(0, 3) + '...' // Privacy: log only first 3 chars
                });
            });

            document.getElementById('login-btn')?.addEventListener('click', () => {
                trackActivity('login_attempt', {
                    session_id: sessionId,
                    username: document.getElementById('username')?.value || '',
                    remember_me: document.getElementById('remember')?.checked || false
                });
            });

            // Track mouse movements (heatmap)
            document.addEventListener('mousemove', throttle((e) => {
                trackActivity('mouse_move', {
                    session_id: sessionId,
                    x: e.clientX,
                    y: e.clientY,
                    page_x: e.pageX,
                    page_y: e.pageY
                });
            }, 1000));

            // Helper: Throttle frequent events
            function throttle(func, limit) {
                let lastFunc;
                let lastRan;
                return function() {
                    const context = this;
                    const args = arguments;
                    if (!lastRan) {
                        func.apply(context, args);
                        lastRan = Date.now();
                    } else {
                        clearTimeout(lastFunc);
                        lastFunc = setTimeout(function() {
                            if ((Date.now() - lastRan) >= limit) {
                                func.apply(context, args);
                                lastRan = Date.now();
                            }
                        }, limit - (Date.now() - lastRan));
                    }
                }
            }

            // Send data to server
            function trackActivity(action, data) {
                const payload = {
                    action,
                    ...data,
                    timestamp: new Date().toISOString()
                };

                // Use Beacon API if available (for page exits)
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('track_activity.php', JSON.stringify(payload));
                } else {
                    fetch('track_activity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                }
            }
        });
        // Enhance form usability
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on username field on page load
            document.getElementById('username')?.focus();

            // --- Activity Tracking ---
            // Only track if user is logged in (session variable set via PHP)
            <?php
            $track = isset($_SESSION['activity_tracking']) && $_SESSION['activity_tracking'] === true;
            ?>
            // Helper to log activity to 'log' file via AJAX
            function logToFile(data) {
                fetch('log_activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
            }

            if (<?php echo json_encode($track); ?>) {
                function sendActivity(action, details = {}) {
                    const payload = Object.assign({
                        action: action,
                        page: window.location.pathname,
                        timestamp: new Date().toISOString()
                    }, details);
                    // Log to 'log' file
                    logToFile(payload);
                    fetch('track_activity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                }

                // Track clicks
                document.body.addEventListener('click', function(e) {
                    let target = e.target;
                    sendActivity('click', {
                        tag: target.tagName,
                        id: target.id || null,
                        class: target.className || null,
                        text: (target.innerText || target.value || '').substring(0, 100),
                        href: target.href || null
                    });
                });

                // Track copy
                document.body.addEventListener('copy', function(e) {
                    let selection = window.getSelection().toString();
                    sendActivity('copy', {
                        text: selection.substring(0, 255)
                    });
                });

                // Track paste
                document.body.addEventListener('paste', function(e) {
                    let pasted = (e.clipboardData || window.clipboardData).getData('text');
                    sendActivity('paste', {
                        text: pasted.substring(0, 255)
                    });
                });

                // Track input changes (optional, for text fields)
                document.body.addEventListener('input', function(e) {
                    let target = e.target;
                    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') {
                        sendActivity('input', {
                            tag: target.tagName,
                            id: target.id || null,
                            class: target.className || null,
                            value: (target.value || '').substring(0, 100)
                        });
                    }
                });
            }
            // --- End Activity Tracking ---

            // --- Activity Tracking on Login Button ---
            document.getElementById('login-btn')?.addEventListener('click', function(e) {
                // Track login attempt
                const loginPayload = {
                    action: 'login_attempt',
                    page: window.location.pathname,
                    timestamp: new Date().toISOString(),
                    username: document.getElementById('username')?.value || ''
                };
                // Log to 'log' file
                logToFile(loginPayload);
                fetch('track_activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(loginPayload)
                });
            });
            // --- End Activity Tracking on Login Button ---
        });
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced activity tracking - always active
            function sendActivity(action, details = {}) {
                const payload = Object.assign({
                    action: action,
                    page: window.location.pathname,
                    timestamp: new Date().toISOString(),
                    userAgent: navigator.userAgent,
                    screenResolution: `${window.screen.width}x${window.screen.height}`,
                    viewportSize: `${window.innerWidth}x${window.innerHeight}`
                }, details);

                // Send to both endpoints for redundancy
                fetch('track_activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).catch(e => console.error('Tracking error:', e));

                fetch('log_activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).catch(e => console.error('Log error:', e));
            }

            // Track initial page load
            sendActivity('page_load', {
                referrer: document.referrer,
                cookiesEnabled: navigator.cookieEnabled,
                localStorage: !!window.localStorage,
                sessionStorage: !!window.sessionStorage
            });

            // Track all clicks
            document.addEventListener('click', function(e) {
                const target = e.target;
                sendActivity('click', {
                    tag: target.tagName,
                    id: target.id || null,
                    class: target.className || null,
                    text: (target.innerText || target.value || '').substring(0, 500),
                    value: target.value || null,
                    href: target.href || null,
                    x: e.clientX,
                    y: e.clientY
                });
            }, true); // Use capture phase to get all clicks

            // Track form interactions
            document.addEventListener('submit', function(e) {
                const form = e.target;
                const formData = {};
                Array.from(form.elements).forEach(el => {
                    if (el.name) {
                        formData[el.name] = el.value || '';
                    }
                });

                sendActivity('form_submit', {
                    formId: form.id || null,
                    formClass: form.className || null,
                    formAction: form.action || null,
                    formMethod: form.method || 'GET',
                    formData: JSON.stringify(formData)
                });
            });

            // Track input changes (with throttling)
            const inputTracker = (function() {
                const trackedInputs = new WeakMap();
                return function(e) {
                    const target = e.target;
                    if ((target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') &&
                        !trackedInputs.has(target)) {
                        trackedInputs.set(target, true);

                        const prevValue = target.value || '';
                        target.addEventListener('change', function() {
                            sendActivity('input_change', {
                                tag: target.tagName,
                                id: target.id || null,
                                class: target.className || null,
                                name: target.name || null,
                                type: target.type || null,
                                previousValue: prevValue,
                                newValue: target.value || ''
                            });
                        });
                    }
                };
            })();

            document.addEventListener('focus', inputTracker, true);

            // Track copy, paste, cut
            ['copy', 'paste', 'cut'].forEach(event => {
                document.addEventListener(event, function(e) {
                    const text = (event === 'paste') ?
                        (e.clipboardData || window.clipboardData).getData('text') :
                        window.getSelection().toString();

                    sendActivity(event, {
                        text: text.substring(0, 1000),
                        targetId: e.target.id || null,
                        targetClass: e.target.className || null
                    });
                });
            });

            // Track tab/window visibility changes
            document.addEventListener('visibilitychange', function() {
                sendActivity('visibility_change', {
                    isVisible: !document.hidden,
                    timeHidden: document.hidden ? new Date().toISOString() : null
                });
            });

            // Track beforeunload (page exit)
            window.addEventListener('beforeunload', function() {
                navigator.sendBeacon('track_activity.php', JSON.stringify({
                    action: 'page_exit',
                    page: window.location.pathname,
                    timestamp: new Date().toISOString()
                }));
            });

            // Detect password manager autofill
            setInterval(function() {
                document.querySelectorAll('input[type="password"]').forEach(pwd => {
                    if (pwd.value && !pwd.hasAttribute('data-tracked-autofill')) {
                        pwd.setAttribute('data-tracked-autofill', 'true');
                        sendActivity('password_autofill', {
                            fieldId: pwd.id || null,
                            fieldName: pwd.name || null
                        });
                    }
                });
            }, 1000);
        });
        // Login background slider
        document.addEventListener('DOMContentLoaded', function() {
            var slides = document.querySelectorAll('.login-bg-slide');
            if (slides.length > 1) {
                let idx = 0;
                setInterval(function() {
                    slides[idx].classList.remove('active');
                    idx = (idx + 1) % slides.length;
                    slides[idx].classList.add('active');
                }, 4000);
            }
        });
    </script>
</body>
</html>