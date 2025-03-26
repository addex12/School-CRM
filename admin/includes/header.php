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
            'title' => 'Settings',
            'url'   => 'settings.php',
            'icon'  => 'fa-cog',
            'roles' => ['admin'],
        ],
    ];
}
return $adminMenu;