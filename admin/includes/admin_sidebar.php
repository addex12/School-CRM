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
            <!-- Dashboard -->
            <li class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php" class="menu-link">
                    <i class="fas fa-home menu-icon"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            
            <!-- User Management Section -->
            <li class="menu-category">
                <div class="category-header" data-toggle="collapse" data-target="#userManagement">
                    <i class="fas fa-users category-icon"></i>
                    <span class="category-text">User Management</span>
                    <i class="fas fa-chevron-down collapse-icon"></i>
                </div>
                <ul class="submenu collapse show" id="userManagement">
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                        <a href="users.php">
                            <i class="fas fa-list"></i>
                            <span>User List</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'edit_users.php' ? 'active' : '' ?>">
                        <a href="edit_users.php">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Users</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'user_roles.php' ? 'active' : '' ?>">
                        <a href="user_roles.php">
                            <i class="fas fa-user-tag"></i>
                            <span>User Roles</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Survey Management Section -->
            <li class="menu-category">
                <div class="category-header" data-toggle="collapse" data-target="#surveyManagement">
                    <i class="fas fa-poll-h category-icon"></i>
                    <span class="category-text">Survey Management</span>
                    <i class="fas fa-chevron-down collapse-icon"></i>
                </div>
                <ul class="submenu collapse show" id="surveyManagement">
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'surveys.php' ? 'active' : '' ?>">
                        <a href="surveys.php">
                            <i class="fas fa-list-ol"></i>
                            <span>All Surveys</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'survey_builder.php' ? 'active' : '' ?>">
                        <a href="survey_builder.php">
                            <i class="fas fa-tools"></i>
                            <span>Survey Builder</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : '' ?>">
                        <a href="results.php">
                            <i class="fas fa-chart-pie"></i>
                            <span>Results & Analytics</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Content Management Section -->
            <li class="menu-category">
                <div class="category-header" data-toggle="collapse" data-target="#contentManagement">
                    <i class="fas fa-folder-open category-icon"></i>
                    <span class="category-text">Content</span>
                    <i class="fas fa-chevron-down collapse-icon"></i>
                </div>
                <ul class="submenu collapse show" id="contentManagement">
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                        <a href="categories.php">
                            <i class="fas fa-folder"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'templates.php' ? 'active' : '' ?>">
                        <a href="templates.php">
                            <i class="fas fa-file-alt"></i>
                            <span>Templates</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Communication Section -->
            <li class="menu-category">
                <div class="category-header" data-toggle="collapse" data-target="#communication">
                    <i class="fas fa-comments category-icon"></i>
                    <span class="category-text">Communication</span>
                    <i class="fas fa-chevron-down collapse-icon"></i>
                </div>
                <ul class="submenu collapse show" id="communication">
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'bulk_email.php' ? 'active' : '' ?>">
                        <a href="bulk_email.php">
                            <i class="fas fa-envelope"></i>
                            <span>Bulk Email</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'feedback_mgmt.php' ? 'active' : '' ?>">
                        <a href="feedback_mgmt.php">
                            <i class="fas fa-comment-dots"></i>
                            <span>Feedback</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'chat_mgmt.php' ? 'active' : '' ?>">
                        <a href="chat_mgmt.php">
                            <i class="fas fa-comment-alt"></i>
                            <span>Live Chat</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- System Section -->
            <li class="menu-category">
                <div class="category-header" data-toggle="collapse" data-target="#system">
                    <i class="fas fa-cogs category-icon"></i>
                    <span class="category-text">System</span>
                    <i class="fas fa-chevron-down collapse-icon"></i>
                </div>
                <ul class="submenu collapse show" id="system">
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                        <a href="settings.php">
                            <i class="fas fa-sliders-h"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'audit_log.php' ? 'active' : '' ?>">
                        <a href="audit_log.php">
                            <i class="fas fa-history"></i>
                            <span>Audit Log</span>
                        </a>
                    </li>
                    <li class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>">
                        <a href="backup.php">
                            <i class="fas fa-database"></i>
                            <span>Backup</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
        
        <!-- Bottom Section -->
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<style>
    /* Sidebar Styling */
    .admin-sidebar {
        width: 280px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        background: linear-gradient(135deg, #2c3e50, #1a1a2e);
        color: #fff;
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-header {
        padding: 20px 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .logo-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .logo-icon {
        font-size: 24px;
        color: #4cc9f0;
    }
    
    .logo-text {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        color: #fff;
    }
    
    .sidebar-toggle {
        background: transparent;
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        display: none;
    }
    
    .sidebar-content {
        flex: 1;
        overflow-y: auto;
        padding: 15px 0;
    }
    
    /* Menu Categories */
    .menu-category {
        margin-bottom: 5px;
    }
    
    .category-header {
        padding: 12px 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    
    .category-header:hover {
        background: rgba(255, 255, 255, 0.05);
        border-left-color: #4cc9f0;
    }
    
    .category-icon {
        font-size: 16px;
        margin-right: 10px;
        color: #4cc9f0;
    }
    
    .category-text {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
    }
    
    .collapse-icon {
        font-size: 12px;
        transition: transform 0.3s ease;
    }
    
    .category-header.collapsed .collapse-icon {
        transform: rotate(-90deg);
    }
    
    /* Submenu Items */
    .submenu {
        list-style: none;
        padding: 0;
        margin: 0;
        background: rgba(0, 0, 0, 0.2);
    }
    
    .submenu-item {
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
    }
    
    .submenu-item:hover {
        background: rgba(255, 255, 255, 0.05);
        border-left-color: #4cc9f0;
    }
    
    .submenu-item a {
        display: flex;
        align-items: center;
        padding: 10px 20px 10px 40px;
        color: #e0e0e0;
        text-decoration: none;
        font-size: 13px;
    }
    
    .submenu-item i {
        margin-right: 8px;
        font-size: 12px;
        color: #a0a0a0;
    }
    
    .submenu-item.active {
        background: rgba(76, 201, 240, 0.1);
        border-left-color: #4cc9f0;
    }
    
    .submenu-item.active a {
        color: #fff;
    }
    
    .submenu-item.active i {
        color: #4cc9f0;
    }
    
    /* Regular Menu Items */
    .menu-item {
        margin-bottom: 5px;
    }
    
    .menu-link {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: #fff;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    
    .menu-link:hover {
        background: rgba(255, 255, 255, 0.05);
        border-left-color: #4cc9f0;
    }
    
    .menu-icon {
        font-size: 16px;
        margin-right: 10px;
        color: #a0a0a0;
    }
    
    .menu-text {
        font-size: 14px;
        font-weight: 500;
    }
    
    .menu-item.active {
        background: rgba(76, 201, 240, 0.1);
        border-left-color: #4cc9f0;
    }
    
    .menu-item.active .menu-icon {
        color: #4cc9f0;
    }
    
    /* Sidebar Footer */
    .sidebar-footer {
        padding: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .logout-btn {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.05);
    }
    
    .logout-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .logout-btn i {
        margin-right: 10px;
        color: #f72585;
    }
    
    /* Collapsed State */
    .admin-sidebar.collapsed {
        width: 70px;
    }
    
    .admin-sidebar.collapsed .logo-text,
    .admin-sidebar.collapsed .menu-text,
    .admin-sidebar.collapsed .category-text,
    .admin-sidebar.collapsed .collapse-icon,
    .admin-sidebar.collapsed .submenu-item span {
        display: none;
    }
    
    .admin-sidebar.collapsed .sidebar-header {
        padding: 20px 10px;
    }
    
    .admin-sidebar.collapsed .menu-link,
    .admin-sidebar.collapsed .category-header {
        justify-content: center;
        padding: 15px 10px;
    }
    
    .admin-sidebar.collapsed .menu-icon,
    .admin-sidebar.collapsed .category-icon {
        margin-right: 0;
        font-size: 18px;
    }
    
    .admin-sidebar.collapsed .submenu-item a {
        padding: 10px;
        justify-content: center;
    }
    
    .admin-sidebar.collapsed .logout-btn {
        justify-content: center;
        padding: 10px;
    }
    
    .admin-sidebar.collapsed .logout-btn span {
        display: none;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .admin-sidebar {
            width: 70px;
        }
        
        .admin-sidebar.expanded {
            width: 280px;
        }
        
        .sidebar-toggle {
            display: block;
        }
        
        .logo-text,
        .menu-text,
        .category-text,
        .collapse-icon,
        .submenu-item span {
            display: none;
        }
        
        .admin-sidebar.expanded .logo-text,
        .admin-sidebar.expanded .menu-text,
        .admin-sidebar.expanded .category-text,
        .admin-sidebar.expanded .collapse-icon,
        .admin-sidebar.expanded .submenu-item span {
            display: inline;
        }
        
        .sidebar-header {
            padding: 20px 10px;
        }
        
        .menu-link,
        .category-header {
            justify-content: center;
            padding: 15px 10px;
        }
        
        .menu-icon,
        .category-icon {
            margin-right: 0;
            font-size: 18px;
        }
        
        .submenu-item a {
            padding: 10px;
            justify-content: center;
        }
        
        .logout-btn {
            justify-content: center;
            padding: 10px;
        }
        
        .logout-btn span {
            display: none;
        }
        
        .admin-sidebar.expanded .menu-link,
        .admin-sidebar.expanded .category-header {
            justify-content: flex-start;
            padding: 12px 20px;
        }
        
        .admin-sidebar.expanded .menu-icon,
        .admin-sidebar.expanded .category-icon {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .admin-sidebar.expanded .submenu-item a {
            padding: 10px 20px 10px 40px;
            justify-content: flex-start;
        }
        
        .admin-sidebar.expanded .logout-btn {
            justify-content: flex-start;
            padding: 10px 15px;
        }
        
        .admin-sidebar.expanded .logout-btn span {
            display: inline;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar collapse
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });
        }
        
        // Initialize Bootstrap collapse for categories
        const categoryHeaders = document.querySelectorAll('.category-header');
        
        categoryHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                const collapseElement = document.querySelector(target);
                
                if (collapseElement) {
                    // Toggle the collapse state
                    if (collapseElement.classList.contains('show')) {
                        collapseElement.classList.remove('show');
                        this.classList.add('collapsed');
                    } else {
                        collapseElement.classList.add('show');
                        this.classList.remove('collapsed');
                    }
                }
            });
        });
        
        // Responsive behavior
        function handleResponsiveSidebar() {
            if (window.innerWidth <= 992) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }
        
        window.addEventListener('resize', handleResponsiveSidebar);
        handleResponsiveSidebar();
    });
</script>