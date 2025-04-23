<?php
include '../db_connect.php'; // adjust path as needed

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Build query
$sql = "SELECT * FROM users WHERE status = 'active'";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter !== '') {
    // Example: filter by role
    $sql .= " AND role = ?";
    $params[] = $filter;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Active Users</title>
    <style>
        /* Simple styling */
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f4f4f4; }
        .search-bar { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Active Users</h2>
    <form method="get" class="search-bar">
        <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
        <select name="filter">
            <option value="">All Roles</option>
            <option value="admin" <?php if($filter=='admin') echo 'selected'; ?>>Admin</option>
            <option value="teacher" <?php if($filter=='teacher') echo 'selected'; ?>>Teacher</option>
            <option value="student" <?php if($filter=='student') echo 'selected'; ?>>Student</option>
            <!-- Add more roles as needed -->
        </select>
        <button type="submit">Search</button>
    </form>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <!-- ...other columns as needed... -->
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['role']); ?></td>
            <!-- ...other columns as needed... -->
        </tr>
        <?php endwhile; ?>
        <?php if($result->num_rows == 0): ?>
        <tr><td colspan="4">No active users found.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
<?php
$stmt->close();
$conn->close();