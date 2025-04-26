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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$eventId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, title, description, start_date, end_date, created_at 
        FROM events 
        WHERE id = :id
    ");
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        header("Location: events.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching event details: " . $e->getMessage());
    header("Location: events.php");
    exit();
}

$pageTitle = htmlspecialchars($event['title']);
include 'includes/header.php';
?>

<main>
    <div class="event-details" style="border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <h1 style="font-size: 1.75rem; margin-bottom: 16px;"><?= htmlspecialchars($event['title']) ?></h1>
        <p style="font-size: 0.9rem; color: #555;">
            <strong>Start:</strong> <?= date('M j, Y g:i A', strtotime($event['start_date'])) ?><br>
            <strong>End:</strong> <?= date('M j, Y g:i A', strtotime($event['end_date'])) ?><br>
            <strong>Created At:</strong> <?= date('M j, Y g:i A', strtotime($event['created_at'])) ?>
        </p>
        <p style="font-size: 1rem; margin-top: 16px;"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
        <a href="events.php" class="btn-primary" style="display: inline-block; padding: 8px 16px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.9rem; text-align: center; margin-top: 16px;">Back to Events</a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
