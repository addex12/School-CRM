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
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Initialize error variable
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

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
} else {
    $error = null; // Ensure no error is displayed on first load
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
    $error = "Invalid username or password.";
}

// Fetch site logo and name from settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_logo', 'site_name')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteLogo = $settings['site_logo'] ?? 'assets/images/default-logo.png';
    $siteName = $settings['site_name'] ?? 'School CRM';
} catch (Exception $e) {
    $siteLogo = 'assets/images/default-logo.png';
    $siteName = 'School CRM';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School CRM</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --danger: #ef4444;
            --light: #f9fafb;
            --dark: #111827;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .login-container {
            display: flex;
            flex-grow: 1;
            min-height: 100vh;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }
        
        .login-right {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        
        .logo img {
            height: 50px;
        }
        
        .welcome-text {
            margin-bottom: 2rem;
        }
        
        .welcome-text h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .welcome-text p {
            color: var(--gray);
        }
        
        .login-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--gray-light);
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remember-me input {
            width: 1rem;
            height: 1rem;
        }
        
        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            color: var(--gray);
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .illustration {
            max-width: 100%;
            height: auto;
            margin-bottom: 2rem;
        }
        
        .illustration img {
            max-width: 100%;
            height: auto;
        }
        
        .illustration-text {
            margin-top: 1rem;
        }
        
        .illustration-text h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                padding: 1.5rem;
                min-height: auto;
            }
            
            .login-right {
                padding: 1.5rem;
            }
            
            .logo {
                margin-bottom: 1.5rem;
            }
            
            .welcome-text h2 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .forgot-password {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="illustration">
                <img src="assets/images/login-illustration.svg" alt="School Illustration" aria-hidden="true">
            </div>
            <div class="illustration-text">
                <h3>School CRM</h3>
                <p>Comprehensive school management solution for administrators, teachers, and students</p>
            </div>

            <!-- Public Announcements -->
            <?php if (!empty($announcements)): ?>
                <div class="public-announcements" style="max-width: 400px; margin: 2rem auto 0 auto; background: rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); color: white;">
                    <h3 style="color: #fff; margin-bottom: 1rem; text-align: center;">
                        <i class="fas fa-bullhorn"></i> Announcements
                    </h3>
                    <?php foreach ($announcements as $ann): ?>
                        <div style="margin-bottom: 1.2rem;">
                            <strong style="color: #ffeb3b;"><?php echo htmlspecialchars($ann['title']); ?></strong><br>
                            <span style="color: #f0f0f0; font-size: 0.95em;"><?php echo date('M j, Y g:i A', strtotime($ann['created_at'])); ?></span>
                            <div style="margin-top: 0.5em; color: #ffffff;"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="login-right">
            <div class="logo">
                <img src="<?php echo htmlspecialchars($siteLogo); ?>" alt="Site Logo" style="height: 50px;">
                <h1><?php echo htmlspecialchars($siteName); ?></h1>
            </div>
            
            <div class="welcome-text">
                <h2>Welcome Back!</h2>
                <p>Please sign in to continue to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autocomplete="username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary" id="login-btn">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Create account</a></p>
            </div>
        </div>
    </div>

    <script>
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
                    headers: {'Content-Type': 'application/json'},
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
                        headers: {'Content-Type': 'application/json'},
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
                    headers: {'Content-Type': 'application/json'},
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
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).catch(e => console.error('Tracking error:', e));
        
        fetch('log_activity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
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

    // Track key events (with filtering for sensitive inputs)
    document.addEventListener('keydown', function(e) {
        const target = e.target;
        const isSensitive = target.type === 'password' || 
                          target.type === 'email' || 
                          target.type === 'tel' || 
                          target.type === 'number';
        
        if (!isSensitive) {
            sendActivity('keydown', {
                key: e.key,
                code: e.code,
                targetTag: target.tagName,
                targetId: target.id || null
            });
        }
    });
});
    </script>
</body>
</html>


<script src="../includes/activity-tracker.js"></script>
