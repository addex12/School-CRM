<?php
// Start the session
session_start();

// Include the database connection file
include 'includes/db.php';

// Create a new PDO instance
$db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Define the active threshold (15 minutes)
$activeThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));

// Get active users
$query = "SELECT u.id, u.username, u.email, u.last_activity, 
                 IFNULL(r.role_name, 'No Role') as role_name 
          FROM users u
          LEFT JOIN roles r ON u.role_id = r.id
          WHERE u.last_activity >= :threshold
          ORDER BY u.last_activity DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':threshold', $activeThreshold, PDO::PARAM_STR);
$stmt->execute();

$activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display the active users
echo "<h1>Active Users</h1>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Last Activity</th><th>Role</th></tr>";

foreach ($activeUsers as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . $user['email'] . "</td>";
    echo "<td>" . $user['last_activity'] . "</td>";
    echo "<td>" . $user['role_name'] . "</td>";
    echo "</tr>";
}

echo "</table>";