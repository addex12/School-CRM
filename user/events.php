<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!class_exists('Auth')) {
    class Auth {
        public static function isLoggedIn(): bool {
            return isset($_SESSION['user_id']);
        }
    }
}

if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Events";

// Fetch events from the database
try {
    $stmt = $pdo->prepare("
        SELECT id, title, start_date, end_date, created_at 
        FROM events 
        WHERE user_id = ? 
        ORDER BY start_date ASC
    ");
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <style>
        #calendar {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .events-list {
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .events-list h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #34495e;
        }
        .event-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f0f2f5;
        }
        .event-item:last-child {
            border-bottom: none;
        }
        .event-item h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        .event-item small {
            color: #95a5a6;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <nav>
                <div class="logo">School CRM</div>
                <ul class="nav-links">
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="events.php" class="active">Events</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <!-- Upcoming Event Card -->
            <?php if (!empty($events)): ?>
                <?php $nextEvent = $events[0]; // Get the next upcoming event ?>
                <div class="event-card" style="max-width: 900px; margin: 20px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); padding: 20px;">
                    <h2 style="margin-bottom: 10px; font-size: 1.5rem; color: #34495e;">Next Event</h2>
                    <h3 style="margin: 0; font-size: 1.2rem; color: #2c3e50;"><?= htmlspecialchars($nextEvent['title']) ?></h3>
                    <p style="margin: 5px 0; color: #7f8c8d;">
                        <?= date('M j, Y g:i A', strtotime($nextEvent['start_date'])) ?> 
                        to <?= date('M j, Y g:i A', strtotime($nextEvent['end_date'])) ?>
                    </p>
                </div>
            <?php endif; ?>

            <div id="calendar"></div>

            <div class="events-list">
                <h2>Upcoming Events</h2>
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            <small>
                                <?= date('M j, Y g:i A', strtotime($event['start_date'])) ?> 
                                to <?= date('M j, Y g:i A', strtotime($event['end_date'])) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No upcoming events found.</p>
                <?php endif; ?>
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
                events: <?= json_encode(array_map(function ($event) {
                    return [
                        'title' => $event['title'],
                        'start' => $event['start_date'],
                        'end' => $event['end_date']
                    ];
                }, $events)) ?>
            });
            calendar.render();
        });
    </script>
</body>
</html>
