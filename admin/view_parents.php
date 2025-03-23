<?php
session_start();
// ...existing code for admin check...
// ...existing code for database connection...

$parents = $conn->query("SELECT id, parent_name, parent_email, created_at FROM parents");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Parents</title>
</head>
<body>
    <h1>Parents</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Created At</th>
        </tr>
        <?php while ($parent = $parents->fetch_assoc()): ?>
        <tr>
            <td><?php echo $parent['id']; ?></td>
            <td><?php echo htmlspecialchars($parent['parent_name']); ?></td>
            <td><?php echo htmlspecialchars($parent['parent_email']); ?></td>
            <td><?php echo $parent['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
