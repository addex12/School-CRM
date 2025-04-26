<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $user_id = $_SESSION['user_id']; // Assuming user_id is stored in the session

    try {
        $stmt = $pdo->prepare("INSERT INTO events (user_id, title, start_date, end_date, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $title, $start_date, $end_date]);
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
</style>

<div class="admin-main">
    <header class="admin-header">
        <h1 style="font-family: 'Nunito Sans', sans-serif; font-size: 26px; font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($pageTitle) ?></h1>
    </header>

    <div class="content">
        <div class="card" style="max-width: 600px; margin: auto; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); border-radius: 8px; background-color: #f9f9f9;">
            <form method="POST" class="form">
                <div class="form-group">
                    <label for="title" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; font-weight: 600; color: #34495e;">Event Title</label>
                    <input type="text" id="title" name="title" class="form-control" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
                <div class="form-group">
                    <label for="start_date" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; font-weight: 600; color: #34495e;">Start Date</label>
                    <input type="datetime-local" id="start_date" name="start_date" class="form-control" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
                <div class="form-group">
                    <label for="end_date" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; font-weight: 600; color: #34495e;">End Date</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
                <button type="submit" class="btn btn-primary" style="font-family: 'Nunito Sans', sans-serif; font-size: 16px; font-weight: 700; background-color: #3498db; border: none; padding: 12px 24px; border-radius: 6px; color: white; cursor: pointer; transition: background-color 0.3s;">
                    Create Event
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
