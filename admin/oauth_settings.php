<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna: Admin page to manage OAuth settings (Google, Facebook, Telegram)
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();
$pageTitle = "Adugna OAuth Settings";
// Fetch current settings from DB
$defaults = [
    'google_client_id' => '',
    'google_client_secret' => '',
    'facebook_app_id' => '',
    'facebook_app_secret' => '',
    'telegram_bot_username' => '',
];
$settings = $defaults;
$stmt = $pdo->query("SELECT `key`, `value` FROM oauth_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $key => $val) {
        $value = $_POST[$key] ?? '';
        $exists = $pdo->prepare("SELECT COUNT(*) FROM oauth_settings WHERE `key` = ?");
        $exists->execute([$key]);
        if ($exists->fetchColumn() > 0) {
            $update = $pdo->prepare("UPDATE oauth_settings SET `value` = ? WHERE `key` = ?");
            $update->execute([$value, $key]);
        } else {
            $insert = $pdo->prepare("INSERT INTO oauth_settings (`key`, `value`) VALUES (?, ?)");
            $insert->execute([$key, $value]);
        }
        $settings[$key] = $value;
    }
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adugna OAuth Settings - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: Compact, responsive, adugna-branded admin settings form */
        .adugna-settings-card { max-width: 520px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(44,62,80,0.09); padding: 2rem 2.2rem; }
        .adugna-settings-title { font-size: 1.5rem; color: #1a4d5c; font-weight: 700; margin-bottom: 1.2rem; text-align: center; }
        .adugna-settings-form label { font-weight: 600; color: #215967; margin-bottom: 0.2em; display: block; }
        .adugna-settings-form input[type=text], .adugna-settings-form input[type=password] { width: 100%; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 5px; margin-bottom: 1.1em; font-size: 1em; background: #f9fafb; }
        .adugna-btn { padding: 8px 18px; font-size: 1em; border-radius: 4px; border: none; background: #1a4d5c; color: #fff; font-weight: 600; cursor: pointer; transition: background 0.13s; }
        .adugna-btn:active, .adugna-btn:focus { outline: 2px solid #1a4d5c; }
        .adugna-success { background: #e7fbe7; color: #388e3c; border: 1px solid #b2dfdb; border-radius: 5px; padding: 10px 14px; margin-bottom: 1em; text-align: center; }
        @media (max-width: 600px) { .adugna-settings-card { padding: 1rem 0.5rem; } }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-settings-card">
            <div class="adugna-settings-title"><i class="fas fa-cogs"></i> Adugna OAuth Settings</div>
            <?php if (!empty($success)): ?>
                <div class="adugna-success">Settings updated successfully.</div>
            <?php endif; ?>
            <form method="post" class="adugna-settings-form">
                <label for="google_client_id">Google Client ID</label>
                <input type="text" id="google_client_id" name="google_client_id" value="<?= htmlspecialchars($settings['google_client_id']) ?>">
                <label for="google_client_secret">Google Client Secret</label>
                <input type="text" id="google_client_secret" name="google_client_secret" value="<?= htmlspecialchars($settings['google_client_secret']) ?>">
                <label for="facebook_app_id">Facebook App ID</label>
                <input type="text" id="facebook_app_id" name="facebook_app_id" value="<?= htmlspecialchars($settings['facebook_app_id']) ?>">
                <label for="facebook_app_secret">Facebook App Secret</label>
                <input type="text" id="facebook_app_secret" name="facebook_app_secret" value="<?= htmlspecialchars($settings['facebook_app_secret']) ?>">
                <label for="telegram_bot_username">Telegram Bot Username</label>
                <input type="text" id="telegram_bot_username" name="telegram_bot_username" value="<?= htmlspecialchars($settings['telegram_bot_username']) ?>">
                <button type="submit" class="adugna-btn"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>