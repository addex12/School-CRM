<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, start_date, end_date) VALUES (?, ?, ?)");
        $stmt->execute([$title, $start_date, $end_date]);
        header("Location: events.php?message=Event created successfully");
        exit();
    } catch (Exception $e) {
        error_log("Error creating event: " . $e->getMessage());
        $error = "Failed to create event.";
    }
}

include 'includes/header.php';
?>

<main>
    <h1>Create Event</h1>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required><br>

        <label for="start_date">Start Date:</label>
        <input type="datetime-local" name="start_date" id="start_date" required><br>

        <label for="end_date">End Date:</label>
        <input type="datetime-local" name="end_date" id="end_date" required><br>

        <button type="submit">Create Event</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>
