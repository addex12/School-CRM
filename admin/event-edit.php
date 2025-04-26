<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid event ID.";
    header("Location: events.php");
    exit();
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    try {
        $stmt = $pdo->prepare("UPDATE events SET title = ?, start_date = ?, end_date = ? WHERE id = ?");
        $stmt->execute([$title, $start_date, $end_date, $id]);
        $_SESSION['success'] = "Event updated successfully.";
        header("Location: events.php");
        exit();
    } catch (Exception $e) {
        error_log("Error updating event: " . $e->getMessage());
        $_SESSION['error'] = "Error updating event.";
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        $_SESSION['error'] = "Event not found.";
        header("Location: events.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching event: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching event.";
    header("Location: events.php");
    exit();
}

$pageTitle = "Edit Event";
include 'includes/admin_sidebar.php';
?>

<style>
    body, html {
        margin: 0;
        padding: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .admin-main {
        flex: 1;
    }
    .admin-header h1 {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 26px;
        font-weight: 700;
        color: #2c3e50;
    }
    .form-group label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 600;
        color: #34495e;
    }
    .form-control {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .btn-primary {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 16px;
        font-weight: 700;
        background-color: #3498db;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s;
    }
</style>

<div class="admin-main">
    <header class="admin-header">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
    </header>

    <div class="content">
        <form method="POST" class="form">
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['start_date'])) ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['end_date'])) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Event</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
