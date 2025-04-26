<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, start_date, end_date) VALUES (?, ?, ?)");
        $stmt->execute([$title, $start_date, $end_date]);
        $_SESSION['success'] = "Event created successfully.";
        header("Location: events.php");
        exit();
    } catch (Exception $e) {
        error_log("Error creating event: " . $e->getMessage());
        $_SESSION['error'] = "Error creating event.";
    }
}

$pageTitle = "Create Event";
include 'includes/admin_sidebar.php';
?>

<div class="admin-main">
    <header class="admin-header">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
    </header>

    <div class="content">
        <form method="POST" class="form">
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="datetime-local" id="start_date" name="start_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="datetime-local" id="end_date" name="end_date" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Event</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
