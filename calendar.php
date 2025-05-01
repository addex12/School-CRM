<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 * Patent rights reserved.
 */
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

// Fetch events from the database
try {
    $stmt = $pdo->prepare("SELECT title, start_date AS start, end_date AS end FROM events WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - School CRM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        #calendar {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
    </style>
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
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?= json_encode($events) ?>,
                editable: true,
                selectable: true,
                select: function (info) {
                    var title = prompt('Enter Event Title:');
                    if (title) {
                        calendar.addEvent({
                            title: title,
                            start: info.startStr,
                            end: info.endStr
                        });
                        // Save event to the database
                        fetch('save_event.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                title: title,
                                start: info.startStr,
                                end: info.endStr
                            })
                        }).then(response => response.json())
                          .then(data => {
                              if (!data.success) alert('Failed to save event.');
                          });
                    }
                    calendar.unselect();
                },
                eventClick: function (info) {
                    if (confirm('Do you want to delete this event?')) {
                        info.event.remove();
                        // Delete event from the database
                        fetch('delete_event.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: info.event.id })
                        }).then(response => response.json())
                          .then(data => {
                              if (!data.success) alert('Failed to delete event.');
                          });
                    }
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>
