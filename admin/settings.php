<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php'; // Include config to initialize $pdo
require_once '../includes/setting.json'; // Include the revamped setting functions

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        setSystemSetting($key, $value);
    }

    // Handle file uploads (logo, favicon)
    if (!empty($_FILES['site_logo']['name'])) {
        $upload_dir = '../assets/images/';
        $filename = 'logo.' . pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $filepath)) {
            setSystemSetting('site_logo', $filename, 'appearance');
        }
    }

    if (!empty($_FILES['favicon']['name'])) {
        $upload_dir = '../assets/images/';
        $filename = 'favicon.' . pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $filepath)) {
            setSystemSetting('favicon', $filename, 'appearance');
        }
    }

    $success = "Settings updated successfully!";
}

// Fetch all settings
$settings = getAllSystemSettings();

// Fetch all tables from the database
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// List of common SMTP providers
$smtp_providers = [
    'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls'],
    'yahoo' => ['host' => 'smtp.mail.yahoo.com', 'port' => 465, 'secure' => 'ssl'],
    'outlook' => ['host' => 'smtp.office365.com', 'port' => 587, 'secure' => 'tls'],
    'zoho' => ['host' => 'smtp.zoho.com', 'port' => 465, 'secure' => 'ssl']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="container">
                <h1>System Settings</h1>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <h2>General Settings</h2>
                    <label for="site_name">Site Name:</label>
                    <input type="text" id="site_name" name="settings[site_name]" value="<?php echo htmlspecialchars(getSystemSetting('site_name')); ?>">

                    <label for="site_email">Site Email:</label>
                    <input type="email" id="site_email" name="settings[site_email]" value="<?php echo htmlspecialchars(getSystemSetting('site_email')); ?>">

                    <label for="timezone">Timezone:</label>
                    <select id="timezone" name="settings[timezone]">
                        <?php foreach (DateTimeZone::listIdentifiers() as $timezone): ?>
                            <option value="<?php echo $timezone; ?>" <?php echo $timezone === getSystemSetting('timezone') ? 'selected' : ''; ?>>
                                <?php echo $timezone; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <h2>Appearance</h2>
                    <label for="site_logo">Site Logo:</label>
                    <input type="file" id="site_logo" name="site_logo">
                    <?php if ($logo = getSystemSetting('site_logo')): ?>
                        <img src="../assets/images/<?php echo $logo; ?>" alt="Site Logo" style="max-width: 100px;">
                    <?php endif; ?>

                    <label for="favicon">Favicon:</label>
                    <input type="file" id="favicon" name="favicon">
                    <?php if ($favicon = getSystemSetting('favicon')): ?>
                        <img src="../assets/images/<?php echo $favicon; ?>" alt="Favicon" style="max-width: 50px;">
                    <?php endif; ?>

                    <h2>Email Settings</h2>
                    <label for="smtp_provider">SMTP Provider:</label>
                    <select id="smtp_provider" name="settings[smtp_provider]" onchange="updateSMTPSettings(this.value)">
                        <option value="">Custom</option>
                        <?php foreach ($smtp_providers as $provider => $details): ?>
                            <option value="<?php echo $provider; ?>" <?php echo getSystemSetting('smtp_provider') === $provider ? 'selected' : ''; ?>>
                                <?php echo ucfirst($provider); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="smtp_host">SMTP Host:</label>
                    <input type="text" id="smtp_host" name="settings[smtp_host]" value="<?php echo htmlspecialchars(getSystemSetting('smtp_host')); ?>">

                    <label for="smtp_port">SMTP Port:</label>
                    <input type="number" id="smtp_port" name="settings[smtp_port]" value="<?php echo htmlspecialchars(getSystemSetting('smtp_port')); ?>">

                    <label for="smtp_username">SMTP Username:</label>
                    <input type="text" id="smtp_username" name="settings[smtp_username]" value="<?php echo htmlspecialchars(getSystemSetting('smtp_username')); ?>">

                    <label for="smtp_password">SMTP Password:</label>
                    <input type="password" id="smtp_password" name="settings[smtp_password]" value="<?php echo htmlspecialchars(getSystemSetting('smtp_password')); ?>">

                    <label for="smtp_secure">SMTP Security:</label>
                    <select id="smtp_secure" name="settings[smtp_secure]">
                        <option value="tls" <?php echo getSystemSetting('smtp_secure') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo getSystemSetting('smtp_secure') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    </select>

                    <h2>Database Tables</h2>
                    <label for="database_tables">Available Tables:</label>
                    <select id="database_tables" name="settings[database_tables][]" multiple>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?php echo $table; ?>"><?php echo $table; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const smtpProviders = <?php echo json_encode($smtp_providers); ?>;

        function updateSMTPSettings(provider) {
            if (smtpProviders[provider]) {
                document.getElementById('smtp_host').value = smtpProviders[provider].host;
                document.getElementById('smtp_port').value = smtpProviders[provider].port;
                document.getElementById('smtp_secure').value = smtpProviders[provider].secure;
            } else {
                document.getElementById('smtp_host').value = '';
                document.getElementById('smtp_port').value = '';
                document.getElementById('smtp_secure').value = 'tls';
            }
        }
    </script>
</body>
</html>