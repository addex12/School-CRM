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
            <form method="post" class="adugna-settings-form" id="adugna-oauth-form" autocomplete="off" novalidate>
                <!-- Google -->
                <label for="google_client_id">Google Client ID
                    <a href="https://console.developers.google.com/apis/credentials" target="_blank" class="adugna-btn adugna-btn-sm" style="margin-left:0.5em;font-size:0.85em;padding:2px 8px;vertical-align:middle;" title="Get Google credentials"><i class="fab fa-google"></i> Get</a>
                </label>
                <input type="text" id="google_client_id" name="google_client_id" value="<?= htmlspecialchars($settings['google_client_id']) ?>" required pattern="[\w\-\.]+\.apps\.googleusercontent\.com" placeholder="xxxxxxx.apps.googleusercontent.com">
                <label for="google_client_secret">Google Client Secret</label>
                <input type="text" id="google_client_secret" name="google_client_secret" value="<?= htmlspecialchars($settings['google_client_secret']) ?>" required>
                <button type="button" class="adugna-btn adugna-btn-sm adugna-btn-info" onclick="testOAuth('google')"><i class="fas fa-vial"></i> Test Google</button>
                <hr>
                <!-- Facebook -->
                <label for="facebook_app_id">Facebook App ID
                    <a href="https://developers.facebook.com/apps/" target="_blank" class="adugna-btn adugna-btn-sm" style="margin-left:0.5em;font-size:0.85em;padding:2px 8px;vertical-align:middle;" title="Get Facebook credentials"><i class="fab fa-facebook-f"></i> Get</a>
                </label>
                <input type="text" id="facebook_app_id" name="facebook_app_id" value="<?= htmlspecialchars($settings['facebook_app_id']) ?>" required>
                <label for="facebook_app_secret">Facebook App Secret</label>
                <input type="text" id="facebook_app_secret" name="facebook_app_secret" value="<?= htmlspecialchars($settings['facebook_app_secret']) ?>" required>
                <button type="button" class="adugna-btn adugna-btn-sm adugna-btn-info" onclick="testOAuth('facebook')"><i class="fas fa-vial"></i> Test Facebook</button>
                <hr>
                <!-- Telegram -->
                <label for="telegram_bot_username">Telegram Bot Username
                    <a href="https://t.me/BotFather" target="_blank" class="adugna-btn adugna-btn-sm" style="margin-left:0.5em;font-size:0.85em;padding:2px 8px;vertical-align:middle;" title="Get Telegram Bot Username"><i class="fab fa-telegram-plane"></i> Get</a>
                </label>
                <input type="text" id="telegram_bot_username" name="telegram_bot_username" value="<?= htmlspecialchars($settings['telegram_bot_username']) ?>" required pattern="@[a-zA-Z0-9_]{5,32}" placeholder="@your_bot">
                <button type="button" class="adugna-btn adugna-btn-sm adugna-btn-info" onclick="testOAuth('telegram')"><i class="fas fa-vial"></i> Test Telegram</button>
                <hr>
                <button type="submit" class="adugna-btn"><i class="fas fa-save"></i> Save Settings</button>
            </form>
            <div id="adugna-oauth-test-result" style="margin-top:1em;"></div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
    // Adugna: Client-side validation and test connection for each provider
    function testOAuth(provider) {
        const resultDiv = document.getElementById('adugna-oauth-test-result');
        resultDiv.innerHTML = '<span style="color:#1976d2"><i class="fas fa-spinner fa-spin"></i> Testing ' + provider.charAt(0).toUpperCase() + provider.slice(1) + ' connection...</span>';
        fetch('test_oauth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ provider: provider,
                google_client_id: document.getElementById('google_client_id').value,
                google_client_secret: document.getElementById('google_client_secret').value,
                facebook_app_id: document.getElementById('facebook_app_id').value,
                facebook_app_secret: document.getElementById('facebook_app_secret').value,
                telegram_bot_username: document.getElementById('telegram_bot_username').value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<span style="color:#27ae60"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
            } else {
                resultDiv.innerHTML = '<span style="color:#e74c3c"><i class="fas fa-times-circle"></i> ' + (data.message || 'Test failed') + '</span>';
            }
        })
        .catch(() => {
            resultDiv.innerHTML = '<span style="color:#e74c3c"><i class="fas fa-times-circle"></i> Test failed (network error)</span>';
        });
    }
    // Simple client-side validation
    const form = document.getElementById('adugna-oauth-form');
    form.addEventListener('submit', function(e) {
        let valid = true;
        form.querySelectorAll('input[required]').forEach(function(input) {
            if (!input.value.trim()) {
                input.style.borderColor = '#e74c3c';
                valid = false;
            } else {
                input.style.borderColor = '';
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    </script>
</body>
</html>