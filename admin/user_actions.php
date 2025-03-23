<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */
include('../includes/db.php'); // Include database connection

$action = $_POST['action'];

if ($action == 'create') {
    $username = $_POST['username'];
    $role = $_POST['role'];
    // Insert user into the database
    $query = "INSERT INTO users (username, role) VALUES ('$username', '$role')";
    mysqli_query($conn, $query);
    header('Location: users.php');
} elseif ($action == 'update') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    // Update user in the database
    $query = "UPDATE users SET username='$username', role='$role' WHERE id='$id'";
    mysqli_query($conn, $query);
    header('Location: users.php');
} elseif ($action == 'delete') {
    $id = $_POST['id'];
    // Delete user from the database
    $query = "DELETE FROM users WHERE id='$id'";
    mysqli_query($conn, $query);
    header('Location: users.php');
}
?>
