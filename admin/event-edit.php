<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    header("Location: events.php?error=Invalid event ID");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        header("Location: events.php?error=Event not found");
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching event: " . $e->getMessage());
    header("Location: events.php?error=Failed to fetch event");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    try {
        $stmt = $pdo->prepare("UPDATE events SET title = ?, start_date = ?, end_date = ? WHERE id = ?");
        $stmt->execute([$title, $start_date, $end_date, $event_id]);
        header("Location: events.php?message=Event updated successfully");
        exit();
    } catch (Exception $e) {
        error_log("Error updating event: " . $e->getMessage());
        $error = "Failed to update event.";
    }
}

include 'includes/header.php';
?>

<main>
    <h1>Edit Event</h1>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($event['title']) ?>" required><br>

        <label for="start_date">Start Date:</label>
        <input type="datetime-local" name="start_date" id="start_date" value="<?= htmlspecialchars($event['start_date']) ?>" required><br>

        <label for="end_date">End Date:</label>
        <input type="datetime-local" name="end_date" id="end_date" value="<?= htmlspecialchars($event['end_date']) ?>" required><br>

        <button type="submit">Update Event</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>