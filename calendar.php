<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once 'includes/auth.php';

if (!class_exists('Auth')) {
    class Auth {
        public static function isLoggedIn(): bool {
            return isset($_SESSION['user_id']);
        }
    }
}

if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$pageTitle = "Calendar";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - School CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <nav>
                <div class="logo">School CRM</div>
                <ul class="nav-links">
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="calendar-section">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <p>Here you can view and manage your events.</p>
                <div id="calendar"></div>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> School CRM. All rights reserved.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    { title: 'Event 1', start: '2023-11-01' },
                    { title: 'Event 2', start: '2023-11-07', end: '2023-11-10' }
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>
