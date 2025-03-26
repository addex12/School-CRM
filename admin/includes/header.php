<?php
/**
 * Header for the admin panel, including authentication, database connection,
 * settings retrieval, menu generation, and HTML structure.
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/setting.php';

// Ensure the user is an admin
requireAdmin();

// Update user last activity timestamp
$stmt = $pdo->prepare(query: "UPDATE users SET last_activity = NOW() WHERE id = ?");
$stmt->execute(params: [$_SESSION['user_id']]);

// Retrieve site-wide settings
$siteName     = getSystemSetting('site_name', 'Admin Panel'); // Set a default site name
$siteLogo     = getSystemSetting('site_logo');
$themeColor   = getSystemSetting('theme_color', '#3498db'); // Default theme color

// Initialize the admin menu array
$adminMenu = [];
$menuJson  = getSystemSetting('admin_menu');

// Attempt to decode the admin menu from the settings
if (!empty($menuJson)) {
    $decodedMenu = json_decode(json: $menuJson, associative: true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMenu)) {
        $adminMenu = $decodedMenu;
    } else {
        // Log an error if the JSON is invalid
        error_log(message: "Invalid admin menu JSON in settings: " . json_last_error_msg());
        // Fallback to the default menu will occur below
    }
}

// Define a default admin menu if the configured one is empty or invalid
if (empty($adminMenu)) {
    $adminMenu = [
        [
            'title' => 'Dashboard',
            'url'   => 'dashboard.php',
            'icon'  => 'fa-home',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Surveys',
            'url'   => 'surveys.php',
            'icon'  => 'fa-poll',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Survey Builder',
            'url'   => 'survey_builder.php',
            'icon'  => 'fa-wrench',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Categories',
            'url'   => 'categories.php',
            'icon'  => 'fa-folder',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Users',
            'url'   => 'users.php',
            'icon'  => 'fa-users',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Results',
            'url'   => 'results.php',
            'icon'  => 'fa-chart-bar',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Bulk Email',
            'url'   => 'bulk_email.php',
            'icon'  => 'fa-envelope',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Settings',
            'url'   => 'settings.php',
            'icon'  => 'fa-cog',
            'roles' => ['admin'],
        ],
    ];
}

// Sanitize page title
$pageTitle = isset($pageTitle) ? htmlspecialchars(string: $pageTitle) : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> - Admin | <?= $pageTitle ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Dynamically set the primary color from the settings */
        :root {
            --primary-color: <?= htmlspecialchars($themeColor) ?>;
        }

        /* General styles for header and navigation */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: #fff;
        }

        .admin-header .logo {
            display: flex;
            align-items: center;
        }

        .admin-header .logo-img {
            max-height: 40px;
            margin-right: 10px;
        }

        .admin-nav-horizontal {
            display: flex;
            gap: 15px;
        }

        .admin-nav-horizontal a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .admin-nav-horizontal a.active {
            font-weight: bold;
        }

        .logout-btn {
            margin-left: auto;
        }

        /* Responsive styles for smaller screens */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-nav-horizontal {
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 10px;
            }

            .admin-nav-horizontal a {
                font-size: 14px;
            }

            .admin-header .logo h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body></body>
    <div class="admin-layout">
        <header class="admin-header">
            <div class="logo"></div>
                <?php if (!empty($siteLogo)): ?>
                    <img src="../assets/images/<?= htmlspecialchars($siteLogo) ?>"
                         alt="<?= htmlspecialchars($siteName) ?> Logo"
                         class="logo-img">
                <?php endif; ?>
                <h1><?= htmlspecialchars($siteName) ?></h1>
            </div>
            <nav class="admin-nav-horizontal">
                <?php foreach ($adminMenu as $item): ?>
                    <?php
                    // Check if the user's role is allowed to see this menu item
                    $allowedRoles = $item['roles'] ?? ['admin'];
                    if (!isset($_SESSION['role']) || !in_array(needle: $_SESSION['role'], haystack: $allowedRoles)) {
                        continue;
                    }
                    // Determine if the current menu item is active
                    $isActive = (basename(path: $_SERVER['PHP_SELF']) === $item['url']);
                    ?>
                    <a href="<?= htmlspecialchars(string: $item['url']) ?>"
                       class="<?= $isActive ? 'active' : '' ?>">
                        <i class="fas <?= htmlspecialchars(string: $item['icon']) ?>"></i>
                        <span class="nav-text"><?= htmlspecialchars(string: $item['title']) ?></span>
                    </a>
                <?php endforeach; ?>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
        </header>

        <div class="admin-content-wrapper">
            <main class="admin-main-content">
