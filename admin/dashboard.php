<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */

include('header.html'); 
?>
<div class="dashboard-container">
    <h1>Admin Dashboard</h1>
    <nav class="dashboard-nav">
        <ul>
            <li><a href="users.php" class="nav-link">Manage Users</a></li>
            <li><a href="surveys.php" class="nav-link">Manage Surveys</a></li>
            <li><a href="communications.php" class="nav-link">Communications Setup</a></li>
            <li><a href="settings.php" class="nav-link">Settings</a></li>
            <!-- Add other navigation links as necessary -->
        </ul>
    </nav>
    <style>
        .dashboard-nav {
            background-color: #2c3e50;
            padding: 10px;
            border-radius: 5px;
        }
        .dashboard-nav ul {
            list-style-type: none;
            padding: 0;
        }
        .dashboard-nav ul li {
            display: inline;
            margin-right: 15px;
        }
        .dashboard-nav .nav-link {
            color: #ecf0f1;
            text-decoration: none;
            font-weight: bold;
        }
        .dashboard-nav .nav-link:hover {
            color: #3498db;
        }
    </style>
    </nav>
    <div class="dashboard-content">
        <!-- Dashboard Widgets -->
        <div class="widget">
            <h2>Users</h2>
            <p>Total Users: 150</p>
            <button class="toggle-section" data-target="#user-details">View Details</button>
            <div id="user-details" class="hidden">
                <p>Active Users: 120</p>
                <p>Inactive Users: 30</p>
            </div>
        </div>
        <div class="widget">
            <h2>Surveys</h2>
            <p>Active Surveys: 5</p>
            <button class="toggle-section" data-target="#survey-details">View Details</button>
            <div id="survey-details" class="hidden">
                <p>Completed Surveys: 3</p>
                <p>Pending Surveys: 2</p>
            </div>
        </div>
        <div class="widget">
            <h2>Communications</h2>
            <p>Messages Sent: 200</p>
            <button class="toggle-section" data-target="#communication-details">View Details</button>
            <div id="communication-details" class="hidden">
                <p>Unread Messages: 10</p>
                <p>Read Messages: 190</p>
            </div>
        </div>
        <div class="widget">
            <h2>Settings</h2>
            <p>System Status: Online</p>
            <button class="toggle-section" data-target="#settings-details">View Details</button>
            <div id="settings-details" class="hidden">
                <p>Last Backup: 2 days ago</p>
                <p>Next Maintenance: In 5 days</p>
            </div>
        </div>
    </div>
</div>
<!-- Add necessary JavaScript files -->
<script src="../js/dashboard.js"></script>
<?php include('footer.html'); ?>