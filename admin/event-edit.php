<?php
include('../config.php'); // Include database connection

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];
    $query = "SELECT * FROM events WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $update_query = "UPDATE events SET title = ?, description = ?, date = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $title, $description, $date, $event_id);

    if ($stmt->execute()) {
        header("Location: events.php?message=Event updated successfully");
        exit();
    } else {
        echo "Error updating event.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
</head>
<body>
    <h1>Edit Event</h1>
    <form method="POST">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?php echo $event['title']; ?>" required><br>

        <label for="description">Description:</label>
        <textarea name="description" id="description" required><?php echo $event['description']; ?></textarea><br>

        <label for="date">Date:</label>
        <input type="date" name="date" id="date" value="<?php echo $event['date']; ?>" required><br>

        <button type="submit">Update Event</button>
    </form>
</body>
</html>