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
        WHERE user_id = ? OR user_id IS NULL 
        ORDER BY start_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="event-card">
            <h2>Next Event</h2>
            <h3><?= htmlspecialchars($nextEvent['title']) ?></h3>
            <p>
                <?= date('M j, Y g:i A', strtotime($nextEvent['start_date'])) ?> 
                to <?= date('M j, Y g:i A', strtotime($nextEvent['end_date'])) ?>
            </p>
            <a href="event-details.php?id=<?= $nextEvent['id'] ?>" class="btn-primary">View Details</a>
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

<?php include  'includes/footer.php'; ?>
