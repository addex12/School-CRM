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
        <h1 style="font-family: 'Inter', sans-serif; font-size: 24px; font-weight: 600; color: #333;"><?= htmlspecialchars($pageTitle) ?></h1>
    </header>

    <div class="content">
        <div class="card" style="max-width: 600px; margin: auto; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 10px; background-color: #fff;">
            <form method="POST" class="form">
                <div class="form-group">
                    <label for="title" style="font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 500; color: #555;">Event Title</label>
                    <input type="text" id="title" name="title" class="form-control" style="font-family: 'Inter', sans-serif; font-size: 14px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>
                <div class="form-group">
                    <label for="start_date" style="font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 500; color: #555;">Start Date</label>
                    <input type="datetime-local" id="start_date" name="start_date" class="form-control" style="font-family: 'Inter', sans-serif; font-size: 14px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>
                <div class="form-group">
                    <label for="end_date" style="font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 500; color: #555;">End Date</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control" style="font-family: 'Inter', sans-serif; font-size: 14px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>
                <button type="submit" class="btn btn-primary" style="font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; background-color: #007bff; border: none; padding: 12px 24px; border-radius: 6px; color: white; cursor: pointer; transition: background-color 0.3s;">
                    Create Event
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
