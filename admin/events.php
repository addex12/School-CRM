<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Events";

// Fetch all events
try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY start_date DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to fetch events: " . $e->getMessage();
    $events = [];
}

// Handle form submission for adding a new event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $user_id = $_SESSION['user_id']; // Assuming the user is logged in

        // Check if user_id exists in the users table
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid user ID. Please ensure the user exists.");
        }

        if (empty($title) || empty($start_date) || empty($end_date)) {
            throw new Exception("Title, start date, and end date are required.");
        }

        $stmt = $pdo->prepare("INSERT INTO events (title, description, start_date, end_date, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $start_date, $end_date, $user_id]);

        $_SESSION['success'] = "Event added successfully!";
        header("Location: events.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Handle inline edit and delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_event'])) {
        try {
            $event_id = intval($_POST['event_id']);
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];

            if (empty($title) || empty($start_date) || empty($end_date)) {
                throw new Exception("Title, start date, and end date are required.");
            }

            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, start_date = ?, end_date = ? WHERE id = ?");
            $stmt->execute([$title, $description, $start_date, $end_date, $event_id]);

            $_SESSION['success'] = "Event updated successfully!";
            header("Location: events.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }

    if (isset($_POST['delete_event'])) {
        try {
            $event_id = intval($_POST['event_id']);
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$event_id]);

            $_SESSION['success'] = "Event deleted successfully!";
            header("Location: events.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
            border: 1px solid #e74c3c;
        }
        .adugna-btn-danger:hover { background: #c82333; }
        .adugna-btn-sm { padding: 2px 7px; font-size: 0.93em; border-radius: 3px; }
        .adugna-events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .adugna-events-header h1 {
            font-size: 1.3rem;
            color: #1976d2;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .adugna-events-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1rem;
        }
        .adugna-event-card {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 6px rgba(25,118,210,0.05);
            transition: box-shadow 0.15s, transform 0.13s;
        }
        .adugna-event-card:hover {
            box-shadow: 0 4px 16px rgba(25,118,210,0.13);
            transform: translateY(-2px) scale(1.01);
        }
        .adugna-event-card input, .adugna-event-card textarea, .adugna-event-card button {
            font-size: 0.95em;
            padding: 0.5rem;
            border: 1px solid #d0d7de;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        .adugna-event-card .adugna-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .adugna-event-card .adugna-actions button {
            flex: 1;
        }
        .adugna-event-form {
            max-width: 600px;
            margin: 2rem auto 0 auto;
            padding: 1rem;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(25,118,210,0.05);
        }
        .adugna-event-form .adugna-form-group {
            margin-bottom: 1rem;
        }
        .adugna-event-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1976d2;
        }
        .adugna-event-form input, .adugna-event-form textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d0d7de;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.97em;
        }
        @media (max-width: 1100px) {
            .adugna-main-content { max-width: 99vw; margin: 18px 2vw 0 2vw; padding: 10px 4px 18px 4px; }
        }
        @media (max-width: 900px) {
            .adugna-events-header { flex-direction: column; align-items: flex-start; gap: 0.7em; }
            .adugna-event-card { padding: 0.8rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.5rem 0.2rem 0.7rem 0.2rem; }
            .adugna-events-header h1 { font-size: 1.05rem; }
            .adugna-event-card { padding: 0.6rem; }
            .adugna-btn { padding: 4px 8px; font-size: 0.93em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-events-header">
                <h1><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($pageTitle) ?></h1>
                <a href="#addEventForm" class="adugna-btn adugna-btn-sm"><i class="fas fa-plus"></i> Add Event</a>
            </div>
            <?php include 'includes/alerts.php'; ?>
            <div class="adugna-events-list">
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="adugna-event-card">
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <input type="text" name="title" value="<?= htmlspecialchars($event['title'] ?? '') ?>" required>
                                <textarea name="description" rows="2"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                                <input type="datetime-local" name="start_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['start_date'] ?? ''))) ?>" required>
                                <input type="datetime-local" name="end_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['end_date'] ?? ''))) ?>" required>
                                <div class="adugna-actions">
                                    <button type="submit" name="edit_event" class="adugna-btn adugna-btn-sm"><i class="fas fa-save"></i> Save</button>
                                    <button type="submit" name="delete_event" class="adugna-btn adugna-btn-danger adugna-btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#888;">No events found.</p>
                <?php endif; ?>
            </div>
            <form id="addEventForm" method="POST" class="adugna-event-form" style="margin-top:2rem;">
                <h2 style="color:#1976d2;font-size:1.1em;"><i class="fas fa-plus"></i> Add New Event</h2>
                <div class="adugna-form-group">
                    <label for="title">Event Title</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="adugna-form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3"></textarea>
                </div>
                <div class="adugna-form-group">
                    <label for="start_date">Start Date</label>
                    <input type="datetime-local" name="start_date" id="start_date" required>
                </div>
                <div class="adugna-form-group">
                    <label for="end_date">End Date</label>
                    <input type="datetime-local" name="end_date" id="end_date" required>
                </div>
                <button type="submit" name="add_event" class="adugna-btn adugna-btn-sm"><i class="fas fa-plus"></i> Add Event</button>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
