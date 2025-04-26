<?php
include('../config.php'); // Include database connection

$query = "SELECT * FROM events";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Events</title>
</head>
<body>
    <h1>Events</h1>
    <a href="event-create.php">Create New Event</a>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($event = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $event['id']; ?></td>
                    <td><?php echo $event['title']; ?></td>
                    <td><?php echo $event['description']; ?></td>
                    <td><?php echo $event['date']; ?></td>
                    <td>
                        <a href="event-edit.php?id=<?php echo $event['id']; ?>">Edit</a>
                        <a href="event-delete.php?id=<?php echo $event['id']; ?>" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
