<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna OAuth Telegram Endpoint Stub
// This file handles Telegram login/registration for Adugna School CRM.
// You must set your Telegram bot username below.
// For production, use HTTPS and secure your credentials.

// 1. Set your Telegram bot username
$bot_username = 'YOUR_TELEGRAM_BOT_USERNAME'; // e.g. myadugna_bot

// 2. If no Telegram login data, show Telegram login button
if (!isset($_GET['id']) && !isset($_GET['hash'])) {
    $login_url = 'https://oauth.telegram.org/auth?bot=' . urlencode($bot_username) . '&origin=' . urlencode('https://' . $_SERVER['HTTP_HOST']) . '&embed=0&request_access=write';
    header('Location: ' . $login_url);
    exit();
}

// 3. Validate Telegram login data (see Telegram docs for security)
// For demo, just show the data
$user = $_GET;
if (!isset($user['id'])) {
    die('Adugna OAuth: Failed to get Telegram user info.');
}

// 4. Register or log in the user in your system
// TODO: Implement user lookup/creation in your database
// Example: $user['id'], $user['username'], $user['first_name'], $user['last_name']
// You may want to set $_SESSION['user_id'] and redirect to dashboard

// For now, just show the user info (for development)
echo '<h2>Adugna OAuth Telegram Login Success</h2>';
echo '<pre>' . htmlspecialchars(print_r($user, true)) . '</pre>';
echo '<a href="/">Go to Home</a>';
exit();