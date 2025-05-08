<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna OAuth Google Endpoint Stub
// This file handles Google OAuth2 login/registration for Adugna School CRM.
// You must set your Google client ID, client secret, and redirect URI below.
// For production, use HTTPS and secure your credentials.

require_once __DIR__ . '/../../includes/config.php'; // Load BASE_URL
require_once __DIR__ . '/../../includes/db.php'; // Add DB connection

// Fetch Google OAuth2 credentials from DB
$defaults = [
    'google_client_id' => '',
    'google_client_secret' => ''
];
$settings = $defaults;
$stmt = $pdo->query("SELECT `key`, `value` FROM oauth_settings WHERE `key` IN ('google_client_id', 'google_client_secret')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}
$client_id = $settings['google_client_id'];
$client_secret = $settings['google_client_secret'];
$redirect_uri = rtrim(BASE_URL, '/') . '/api/oauth/google.php'; // Use BASE_URL for flexibility

// 2. If no code, redirect to Google consent screen
if (!isset($_GET['code'])) {
    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account',
    ]);
    header('Location: ' . $auth_url);
    exit();
}

if (isset($_GET['code']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['google_user']))) {
    session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['google_user'])) {
        // Password form submitted, use session data
        $user = $_SESSION['google_user'];
        $google_id = $_SESSION['google_id'];
        $user_password = $_POST['password'] ?? null;
    } else {
        // First time with code, exchange for token and get user info
        $code = $_GET['code'];
        $token_url = 'https://oauth2.googleapis.com/token';
        $post_fields = [
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code',
        ];
        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            die('OAuth: cURL error: ' . curl_error($ch));
        }
        curl_close($ch);
        $token_data = json_decode($response, true);
        if (!isset($token_data['access_token'])) {
            die('OAuth: Failed to get access token. Google response: ' . htmlspecialchars($response));
        }
        $access_token = $token_data['access_token'];

        // 4. Get user info from Google
        $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($access_token);
        $ch = curl_init($userinfo_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_json = curl_exec($ch);
        curl_close($ch);
        $user = json_decode($user_json, true);
        if (!$user || !isset($user['email'])) {
            die('OAuth: Failed to get user info.');
        }
        // Store user info in session for next POST
        $_SESSION['google_user'] = $user;
        $_SESSION['google_id'] = $user['id'];
        $google_id = $user['id'];
        $user_password = null;
    }

    // Show password form if not provided
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($user_password)) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Login</title>
        </head>
        <body>
            <form method="post" action="">
                <label for="password">To continue, please consent to store your password for this account. You may use your browser-saved password (autofill) or enter it manually:</label><br>
                <input type="password" name="password" id="password" required autocomplete="current-password"><br>
                <button type="submit" name="consent" value="1">I Consent</button>
            </form>
        </body>
        </html>
        <?php
        exit();
    }

    require_once __DIR__ . '/../../includes/db.php';
    $email = $user['email'] ?? ($google_id . '@google.local');
    $username = $email;
    $first_name = $user['given_name'] ?? '';
    $last_name = $user['family_name'] ?? '';
    if (!empty($user_password)) {
        $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
    } else {
        $random_password = bin2hex(random_bytes(16));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
    }

    // Check if user exists by google_id or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$google_id, $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $update = $pdo->prepare("UPDATE users SET username = ?, password = ?, first_name = ?, last_name = ?, google_id = ?, active = 1 WHERE id = ?");
        $update->execute([$username, $hashed_password, $first_name, $last_name, $google_id, $existing['id']]);
        $user_id = $existing['id'];
    } else {
        $insert = $pdo->prepare("INSERT INTO users (google_id, username, password, email, first_name, last_name, active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $insert->execute([$google_id, $username, $hashed_password, $email, $first_name, $last_name]);
        $user_id = $pdo->lastInsertId();
    }
    // Log the user in by setting session
    session_start();
    $_SESSION['user_id'] = $user_id;
    // After successful login, clear session data
    unset($_SESSION['google_user'], $_SESSION['google_id']);
    // Redirect to dashboard or home
    header('Location: /index.php');
    exit();
}