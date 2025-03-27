<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="admin-logo">
            <i class="fas fa-shield-alt"></i>
            <h2>Admin Panel</h2>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
                <i class="fas fa-chevron-right indicator"></i>
            </a>
        </li>
        <li class="menu-group">
            <div class="group-title">
                <i class="fas fa-users"></i>
                <span>User Management</span>
            </div>
            <ul class="submenu">
                <li><a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">Manage Users</a></li>
                <li><a href="edit_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'edit_users.php' ? 'active' : '' ?>">Edit Users</a></li>
            </ul>
        </li>
        <li class="menu-group">
            <div class="group-title">
                <i class="fas fa-poll"></i>
                <span>Surveys</span>
            </div>
            <ul class="submenu">
                <li><a href="surveys.php" class="<?= basename($_SERVER['PHP_SELF']) == 'surveys.php' ? 'active' : '' ?>">All Surveys</a></li>
                <li><a href="survey_builder.php" class="<?= basename($_SERVER['PHP_SELF']) == 'survey_builder.php' ? 'active' : '' ?>">Builder</a></li>
                <li><a href="results.php" class="<?= basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : '' ?>">Results</a></li>
            </ul>
        </li>
        <li><a href="categories.php" class="<?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
            <i class="fas fa-folder"></i>
            <span>Categories</span>
        </a></li>
        <li class="menu-group">
            <div class="group-title">
                <i class="fas fa-comments"></i>
                <span>Communication</span>
            </div>
            <ul class="submenu">
                <li><a href="bulk_email.php" class="<?= basename($_SERVER['PHP_SELF']) == 'bulk_email.php' ? 'active' : '' ?>">Bulk Email</a></li>
                <li><a href="feedback_mgmt.php" class="<?= basename($_SERVER['PHP_SELF']) == 'feedback_mgmt.php' ? 'active' : '' ?>">Feedback</a></li>
                <li><a href="chat_mgmt.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat_mgmt.php' ? 'active' : '' ?>">Chat</a></li>
            </ul>
        </li>
        <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a></li>
        <li><a href="audit_log.php" class="<?= basename($_SERVER['PHP_SELF']) == 'audit_log.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            <span>Audit Log</span>
        </a></li>
        <li class="logout-item">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>