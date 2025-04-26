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
        ORDER BY start_date ASC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Log the number of events fetched
    error_log("Number of events fetched: " . count($events));
    if (empty($events)) {
        error_log("No events found.");
    }
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
    $events = [];
}

include 'includes/header.php';
?>

<main>
    <!-- Upcoming Event Card -->
    <?php if (!empty($events)): ?>
        <?php $nextEvent = $events[0]; ?>
        <div class="event-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
            <h2 style="font-size: 1.5rem; margin-bottom: 8px;">Next Event</h2>
            <h3 style="font-size: 1.25rem; margin-bottom: 8px;"><?= htmlspecialchars($nextEvent['title']) ?></h3>
            <p style="font-size: 0.9rem; color: #555;">
                <?= date('M j, Y g:i A', strtotime($nextEvent['start_date'])) ?> 
                to <?= date('M j, Y g:i A', strtotime($nextEvent['end_date'])) ?>
            </p>
            <a href="event-details.php?id=<?= $nextEvent['id'] ?>" class="btn-primary" style="display: inline-block; padding: 8px 16px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.9rem; text-align: center;">View Details</a>
        </div>
    <?php endif; ?>

    <div id="calendar" style="margin-bottom: 16px;"></div>

    <div class="events-list">
        <h2 style="font-size: 1.5rem; margin-bottom: 16px;">Upcoming Events</h2>
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
                <div class="event-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <h3 style="font-size: 1.25rem; margin-bottom: 8px;"><?= htmlspecialchars($event['title']) ?></h3>
                    <small style="font-size: 0.9rem; color: #555;">
                        <?= date('M j, Y g:i A', strtotime($event['start_date'])) ?> 
                        to <?= date('M j, Y g:i A', strtotime($event['end_date'])) ?>
                    </small>
                    <p style="font-size: 0.9rem; color: #555;">Created At: <?= date('M j, Y g:i A', strtotime($event['created_at'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="font-size: 0.9rem; color: #555;">No upcoming events found.</p>
        <?php endif; ?>
    </div>
</main>

<?php include  'includes/footer.php'; ?>
