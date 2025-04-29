<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Events";

// Set default timezone for events
date_default_timezone_set('Africa/Nairobi');

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
    .admin-sidebar {
        position: fixed; /* Ensure the sidebar stays fixed */
        top: 0;
        left: 0;
        width: 250px;
        height: 100%;
        z-index: 1000; /* Ensure the sidebar is above other elements */
        background: #2c3e50;
        overflow-y: auto;
    }

    .admin-main {
        margin-left: 250px; /* Adjust for sidebar width */
        padding: 2rem;
        transition: margin-left 0.3s ease; /* Smooth transition for sidebar toggle */
        min-height: 100vh; /* Ensure the main content takes full height */
        box-sizing: border-box;
        overflow-x: hidden; /* Prevent horizontal scrolling */
    }

    @media (max-width: 768px) {
        .admin-sidebar {
            width: 100%; /* Sidebar takes full width on smaller screens */
            height: auto;
            position: relative; /* Sidebar becomes part of the flow */
        }

        .admin-main {
            margin-left: 0; /* Remove margin for smaller screens */
            padding: 1rem; /* Adjust padding for smaller screens */
        }
    }

    .events-container {
        max-width: 100%; /* Allow full width */
        margin: 0 auto;
        padding: 1rem;
        box-sizing: border-box; /* Include padding in width calculations */
    }
    .events-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap; /* Allow wrapping for smaller screens */
    }
    .events-header h1 {
        font-size: 1.8rem;
        color: #34495e;
        margin-bottom: 0.5rem; /* Add spacing for smaller screens */
    }
    .btn {
        background: #007bfc;
        color: #fff;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 4px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: background 0.18s, transform 0.18s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }
    .btn:hover {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    }
    .events-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive grid */
        gap: 1rem;
    }
    .event-card {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }
    .event-card input, .event-card textarea, .event-card button {
        font-size: 0.9rem;
        padding: 0.5rem;
        border: 1px solid #ccc;
        border-radius: 4px;
        width: 100%; /* Ensure inputs take full width */
        box-sizing: border-box;
    }
    .event-card .actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
    }
    .event-card .actions button {
        flex: 1;
    }
    .event-form {
        max-width: 600px;
        margin: 0 auto;
        padding: 1rem;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }
    .event-form .form-group {
        margin-bottom: 1rem;
    }
    .event-form label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .event-form input, .event-form textarea {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    @media (max-width: 768px) {
        .events-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .event-card {
            padding: 0.8rem;
        }
        .event-card h2 {
            font-size: 1rem;
        }
        .event-card p {
            font-size: 0.9rem;
        }
    }
    @media (max-width: 480px) {
        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        .event-card {
            padding: 0.6rem;
        }
    }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="events-container">
                <div class="events-header">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <a href="#addEventForm" class="btn"><i class="fas fa-plus"></i> Add Event</a>
                </div>

                <?php include 'includes/alerts.php'; ?>

                <div class="events-list">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                            <div class="event-card">
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <input type="text" name="title" value="<?= htmlspecialchars($event['title'] ?? '') ?>" required>
                                    <textarea name="description" rows="2"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                                    <input type="datetime-local" name="start_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['start_date'] ?? ''))) ?>" required>
                                    <input type="datetime-local" name="end_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['end_date'] ?? ''))) ?>" required>
                                    <div class="actions">
                                        <button type="submit" name="edit_event">Save</button>
                                        <button type="submit" name="delete_event" style="background: #e74c3c;">Delete</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No events found.</p>
                    <?php endif; ?>
                </div>

                <form id="addEventForm" method="POST" class="event-form">
                    <h2>Add New Event</h2>
                    <div class="form-group">
                        <label for="title">Event Title</label>
                        <input type="text" name="title" id="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="datetime-local" name="start_date" id="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="datetime-local" name="end_date" id="end_date" required>
                    </div>
                    <button type="submit" name="add_event" class="btn">Add Event</button>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
