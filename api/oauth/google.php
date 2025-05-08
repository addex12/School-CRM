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

// 3. Exchange code for access token
if (isset($_GET['code'])) {
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

    // 5. Register or log in the user in your system
    // Store Google credentials in users table
    require_once __DIR__ . '/../../includes/db.php';
    $google_id = $user['id'];
    $email = $user['email'];
    $name = $user['name'] ?? '';
    $given_name = $user['given_name'] ?? '';
    $family_name = $user['family_name'] ?? '';
    $picture = $user['picture'] ?? '';

    // Check if user exists by email or google_id
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR google_id = ? LIMIT 1");
    $stmt->execute([$email, $google_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        // Update user info
        $update = $pdo->prepare("UPDATE users SET google_id = ?, name = ?, given_name = ?, family_name = ?, picture = ? WHERE id = ?");
        $update->execute([$google_id, $name, $given_name, $family_name, $picture, $existing['id']]);
        $user_id = $existing['id'];
    } else {
        // Insert new user
        $insert = $pdo->prepare("INSERT INTO users (email, google_id, name, given_name, family_name, picture) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$email, $google_id, $name, $given_name, $family_name, $picture]);
        $user_id = $pdo->lastInsertId();
    }
    // Optionally, start a session for the user
    session_start();
    $_SESSION['user_id'] = $user_id;
    // Redirect to dashboard or home
    header('Location: /');
    exit();
}