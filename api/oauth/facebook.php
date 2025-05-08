<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna OAuth Facebook Endpoint Stub
// This file handles Facebook OAuth2 login/registration for Adugna School CRM.
// You must set your Facebook App ID, App Secret, and redirect URI below.
// For production, use HTTPS and secure your credentials.

// 1. Set your Facebook OAuth2 credentials
$app_id = 'YOUR_FACEBOOK_APP_ID';
$app_secret = 'YOUR_FACEBOOK_APP_SECRET';
$redirect_uri = 'https://yourdomain.com/api/oauth/facebook.php'; // Update to your actual redirect URI

// 2. If no code, redirect to Facebook consent screen
if (!isset($_GET['code'])) {
    $auth_url = 'https://www.facebook.com/v19.0/dialog/oauth?'.http_build_query([
        'client_id' => $app_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'email,public_profile',
        'auth_type' => 'rerequest',
    ]);
    header('Location: ' . $auth_url);
    exit();
}

// 3. Exchange code for access token
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $token_url = 'https://graph.facebook.com/v19.0/oauth/access_token?'.http_build_query([
        'client_id' => $app_id,
        'redirect_uri' => $redirect_uri,
        'client_secret' => $app_secret,
        'code' => $code,
    ]);
    $response = file_get_contents($token_url);
    $token_data = json_decode($response, true);
    if (!isset($token_data['access_token'])) {
        die('Adugna OAuth: Failed to get access token.');
    }
    $access_token = $token_data['access_token'];

    // 4. Get user info from Facebook
    $userinfo_url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . urlencode($access_token);
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
    echo '<h2>Adugna OAuth Facebook Login Success</h2>';
    echo '<pre>' . htmlspecialchars(print_r($user, true)) . '</pre>';
    echo '<a href="/">Go to Home</a>';
    exit();
}