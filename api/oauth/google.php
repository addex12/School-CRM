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

// 1. Set your Google OAuth2 credentials
$client_id = 'YOUR_GOOGLE_CLIENT_ID';
$client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirect_uri = rtrim(BASE_URL, '/') . '/api/oauth/google.php'; // Use BASE_URL for flexibility

// 2. If no code, redirect to Google consent screen
if (!isset($_GET['code'])) {
    $auth_url = 'https://accounts.google.com/sign/oauth2/v2/auth?'.http_build_query([
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
    curl_close($ch);
    $token_data = json_decode($response, true);
    if (!isset($token_data['access_token'])) {
        die('Adugna OAuth: Failed to get access token.');
    }
    $access_token = $token_data['access_token'];

    // 4. Get user info from Google
    $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($access_token);
    $user_json = file_get_contents($userinfo_url);
    $user = json_decode($user_json, true);
    if (!$user || !isset($user['email'])) {
        die('Adugna OAuth: Failed to get user info.');
    }

    // 5. Register or log in the user in your system
    // TODO: Implement user lookup/creation in your database
    // Example: $user['email'], $user['name'], $user['id']
    // You may want to set $_SESSION['user_id'] and redirect to dashboard

    // For now, just show the user info (for development)
    echo '<h2>School CRM OAuth Google Login Success</h2>';
    echo '<pre>' . htmlspecialchars(print_r($user, true)) . '</pre>';
    echo '<a href="/">Go to Home</a>';
    exit();
}