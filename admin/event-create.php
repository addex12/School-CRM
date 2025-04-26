<?php
include('../config.php'); // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $insert_query = "INSERT INTO events (title, description, date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sss", $title, $description, $date);

    if ($stmt->execute()) {
        header("Location: events.php?message=Event created successfully");
        exit();
    } else {
        echo "Error creating event.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Event</title>
</head>
<body>
    <h1>Create Event</h1>
    <form method="POST">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required><br>

        <label for="description">Description:</label>
        <textarea name="description" id="description" required></textarea><br>

        <label for="date">Date:</label>
        <input type="date" name="date" id="date" required><br>

        <button type="submit">Create Event</button>
    </form>
</body>
</html>
