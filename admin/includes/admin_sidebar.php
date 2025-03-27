<?php
// Load sidebar configuration from JSON
$sidebarConfig = json_decode(file_get_contents(__DIR__ . '/sidebar_config.json'), true);
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <i class="fas fa-shield-alt logo-icon"></i>
            <h2 class="logo-text">Admin Panel</h2>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
    
    <div class="sidebar-content">
        <ul class="sidebar-menu">
            <?php foreach ($sidebarConfig['menu'] as $item): ?>
                <?php if (isset($item['items'])): // Category with subitems ?>
                    <li class="menu-category">
                        <div class="category-header" data-toggle="collapse" data-target="#<?= $item['id'] ?>">
                            <i class="fas fa-<?= $item['icon'] ?> category-icon"></i>
                            <span class="category-text"><?= $item['title'] ?></span>
                            <i class="fas fa-chevron-down collapse-icon"></i>
                        </div>
                        <ul class="submenu collapse show" id="<?= $item['id'] ?>">
                            <?php foreach ($item['items'] as $subitem): ?>
                                <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == $subitem['link'] ? 'active' : '' ?>">
                                    <a href="<?= $subitem['link'] ?>">
                                        <i class="fas fa-<?= $subitem['icon'] ?>"></i>
                                        <span><?= $subitem['title'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php else: // Single menu item ?>
                    <li class="menu-item <?= basename($_SERVER['PHP_SELF']) == $item['link'] ? 'active' : '' ?>">
                        <a href="<?= $item['link'] ?>" class="menu-link">
                            <i class="fas fa-<?= $item['icon'] ?> menu-icon"></i>
                            <span class="menu-text"><?= $item['title'] ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Create sidebar_config.json in the same directory -->
<?php
/*
// Example sidebar_config.json content:
{
    "menu": [
        {
            "title": "Dashboard",
            "link": "dashboard.php",
            "icon": "home"
        },
        {
            "title": "User Management",
            "icon": "users",
            "id": "userManagement",
            "items": [
                {
                    "title": "User List",
                    "link": "users.php",
                    "icon": "list"
                },
                {
                    "title": "Edit Users",
                    "link": "edit_users.php",
                    "icon": "user-edit"
                }
            ]
        },
        {
            "title": "Survey Management",
            "icon": "poll-h",
            "id": "surveyManagement",
            "items": [
                {
                    "title": "All Surveys",
                    "link": "surveys.php",
                    "icon": "list-ol"
                },
                {
                    "title": "Survey Builder",
                    "link": "survey_builder.php",
                    "icon": "tools"
                }
            ]
        },
        {
            "title": "Settings",
            "link": "settings.php",
            "icon": "cogs"
        }
    ]
}
*/
?>