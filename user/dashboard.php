<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Get the user's role
$role_name = strtolower($_SESSION['role_name'] ?? 'user');

// Load role-specific configuration
$config_file = __DIR__ . "/../assets/json/{$role_name}_config.json";
if (!file_exists($config_file)) {
    $config_file = __DIR__ . "/../assets/json/default_config.json";
}
$config = json_decode(file_get_contents($config_file), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['title'] ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($role_name) ?>.css">
    <script src="/assets/js/<?= htmlspecialchars($role_name) ?>.js" defer></script>
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($config['title'] ?? 'Dashboard') ?></h1>
        <nav>
            <a href="/logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <section class="widgets">
            <h2>Widgets</h2>
            <ul>
                <?php foreach ($config['widgets'] as $widget): ?>
                    <li><?= htmlspecialchars($widget) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section class="links">
            <h2>Quick Links</h2>
            <ul>
                <?php foreach ($config['links'] as $link): ?>
                    <li><a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </main>
</body>
</html>
