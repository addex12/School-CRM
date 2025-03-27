<div class="admin-sidebar">
    <div class="sidebar-header">
        <h2>Admin Panel</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="edit_users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'edit_users.php' ? 'active' : ''; ?>"><i class="fas fa-user-edit"></i> Edit Users</a></li>
        <li><a href="surveys.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'surveys.php' ? 'active' : ''; ?>"><i class="fas fa-poll"></i> Surveys</a></li>
        <li><a href="survey_builder.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'survey_builder.php' ? 'active' : ''; ?>"><i class="fas fa-wrench"></i> Survey Builder</a></li>
        <li><a href="results.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Results</a></li>
        <li><a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-folder"></i> Categories</a></li>
        <li><a href="bulk_email.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bulk_email.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Bulk Email</a></li>
        <li><a href="feedback_mgmt.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback_mgmt.php' ? 'active' : ''; ?>"><i class="fas fa-comment-dots"></i> Feedback Management</a></li>
        <li><a href="chat_mgmt.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'chat_mgmt.php' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Chat Management</a></li>
        <li><a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="audit_log.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'audit_log.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Audit Log</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>