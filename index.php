<?php
// Start the session
session_start();

// Include required files
require_once __DIR__ . '/includes/config.php'; // This must define $pdo
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Ensure the Auth class exists
if (!class_exists('Auth')) {
    class Auth {
        public static function isLoggedIn(): bool {
            return isset($_SESSION['user_id']);
        }
    }
}

// Check if the user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get current user data
try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    error_log("User data fetch error: " . $e->getMessage());
    header("Location: error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <nav>
                <div class="logo">School CRM</div>
                <ul class="nav-links">
                    <li><a href="index.php" class="active">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($user['username'] ?? 'Guest'); ?></h1>
                <p>Your role: <?php echo htmlspecialchars($user['role'] ?? 'Unknown'); ?></p>
            </div>

            <div class="dashboard-content">
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-grid">
                        <?php if (($user['role'] ?? '') === 'admin'): ?>
                            <a href="admin/students.php" class="action-card">
                                <i class="fas fa-users"></i>
                                <span>Manage Students</span>
                            </a>
                            <a href="admin/teachers.php" class="action-card">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Manage Teachers</span>
                            </a>
                        <?php endif; ?>
                        <a href="calendar.php" class="action-card">
                            <i class="fas fa-calendar-alt"></i>
                            <span>View Calendar</span>
                        </a>
                        <a href="notifications.php" class="action-card">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                    </div>
                </div>

                <div class="recent-activity">
                    <h2>Recent Activity</h2>
                    <div class="activity-list">
                        <!-- Activity will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> School CRM. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>