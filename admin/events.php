<?php
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

        if (empty($title) || empty($start_date) || empty($end_date)) {
            throw new Exception("Title, start date, and end date are required.");
        }

        $stmt = $pdo->prepare("INSERT INTO events (title, description, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $start_date, $end_date]);

        $_SESSION['success'] = "Event added successfully!";
        header("Location: events.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
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
        .events-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .events-header h1 {
            font-size: 1.8rem;
            color: #34495e;
        }
        .btn {
            background: #007bfc;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.18s, transform 0.18s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .event-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        .event-card h2 {
            font-size: 1.2rem;
            color: #34495e;
            margin-bottom: 0.5rem;
        }
        .event-card p {
            font-size: 0.95rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }
        .event-card .event-dates {
            font-size: 0.9rem;
            color: #888;
        }
        @media (max-width: 768px) {
            .events-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
                                <h2><?= htmlspecialchars($event['title']) ?></h2>
                                <p><?= htmlspecialchars($event['description']) ?></p>
                                <p class="event-dates">
                                    From: <?= date('M j, Y g:i A', strtotime($event['start_date'])) ?><br>
                                    To: <?= date('M j, Y g:i A', strtotime($event['end_date'])) ?>
                                </p>
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
