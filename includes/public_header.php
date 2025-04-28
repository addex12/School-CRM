<?php
require_once __DIR__ . '/../includes/config.php';

// Fetch system settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if settings are not set
$site_name = $settings['site_name'] ?? 'School CRM';
$site_logo = $settings['site_logo'] ?? '../uploads/default_logo.png';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_name) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: "Inter", "Segoe UI", Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
        .public-header {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .public-header img {
            height: 40px;
        }
        .public-header h1 {
            font-size: 1.2rem;
            margin: 0;
            color: white;
        }
        .public-header a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            margin-left: 15px;
        }
        .public-header a:hover {
            text-decoration: underline;
        }
        .public-content {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .announcement, .knowledge-base {
            margin-bottom: 20px;
        }
        .announcement h3, .knowledge-base h3 {
            font-size: 18px;
            color: #007bff;
            margin-bottom: 10px;
        }
        .announcement p, .knowledge-base p {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <header class="public-header">
        <div>
            <img src="<?= htmlspecialchars($site_logo) ?>" alt="Site Logo">
            <h1><?= htmlspecialchars($site_name) ?></h1>
        </div>
        <div>
            <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="../register.php"><i class="fas fa-user-plus"></i> Create Account</a>
        </div>
    </header>
   
    </div>
</body>
</html>
