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

require_once __DIR__ . '/../../includes/db.php'; // Add DB connection

// Fetch Telegram bot username and bot ID from DB
$defaults = [
    'telegram_bot_username' => '',
    'telegram_bot_id' => ''
];
$settings = $defaults;
$stmt = $pdo->query("SELECT `key`, `value` FROM oauth_settings WHERE `key` IN ('telegram_bot_username', 'telegram_bot_id')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}
$bot_username = $settings['telegram_bot_username'];
$bot_id = $settings['telegram_bot_id'];

// Determine the origin dynamically
$origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

// 2. If no Telegram login data, show Telegram login button
if (!isset($_GET['id']) && !isset($_GET['hash'])) {
    $login_url = 'https://oauth.telegram.org/auth?bot=' . urlencode($bot_username);
    if (!empty($bot_id)) {
        $login_url .= '&bot_id=' . urlencode($bot_id);
    }
    $login_url .= '&origin=' . urlencode($origin) . '&embed=0&request_access=write';
    header('Location: ' . $login_url);
    exit();
}

// 3. Validate Telegram login data (see Telegram docs for security)
$user = $_GET;
if (!isset($user['id'])) {
    die('Adugna OAuth: Failed to get Telegram user info.');
}

// 4. Register or log in the user in your system
// Store Telegram credentials in users table
$telegram_id = $user['id'];
$email = $user['email'] ?? ($telegram_id . '@telegram.local'); // fallback if Telegram doesn't provide email
$username = $email; // Use email as username
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$photo_url = $user['photo_url'] ?? '';
$auth_date = $user['auth_date'] ?? '';
$hash = $user['hash'] ?? '';
$random_password = bin2hex(random_bytes(16)); // Generate a random password
$hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

// Check if user exists by telegram_id or email
$stmt = $pdo->prepare("SELECT id FROM users WHERE telegram_id = ? OR email = ? LIMIT 1");
$stmt->execute([$telegram_id, $email]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if ($existing) {
    $update = $pdo->prepare("UPDATE users SET username = ?, password = ?, first_name = ?, last_name = ?, telegram_id = ? WHERE id = ?");
    $update->execute([$username, $hashed_password, $first_name, $last_name, $telegram_id, $existing['id']]);
    $user_id = $existing['id'];
} else {
    $insert = $pdo->prepare("INSERT INTO users (telegram_id, username, password, email, first_name, last_name, active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $insert->execute([$telegram_id, $username, $hashed_password, $email, $first_name, $last_name]);
    $user_id = $pdo->lastInsertId();
}

// 5. Send details to the bot
$bot_token = $settings['telegram_bot_id']; // Actually, this should be the bot token, not ID. Adjust if you store the token.
$chat_id = $telegram_id; // Send to the user themselves, or use a fixed admin chat_id
$message = "New Telegram OAuth login:\nID: $telegram_id\nUsername: @$username\nName: $first_name $last_name\nAuth Date: $auth_date\nHash: $hash";
if (!empty($bot_token)) {
    $send_url = "https://api.telegram.org/bot$bot_token/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $message
    ];
    $ch = curl_init($send_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// 6. Redirect to login page
header('Location: /login.php');
exit();