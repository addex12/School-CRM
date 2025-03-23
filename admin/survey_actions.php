<?php
include('../includes/db.php'); // Include database connection

$action = $_POST['action'];

if ($action == 'create') {
    $title = $_POST['title'];
    $target = $_POST['target'];
    // Insert survey into the database
    $query = "INSERT INTO surveys (title, target) VALUES ('$title', '$target')";
    mysqli_query($conn, $query);
    header('Location: surveys.php');
} elseif ($action == 'update') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $target = $_POST['target'];
    // Update survey in the database
    $query = "UPDATE surveys SET title='$title', target='$target' WHERE id='$id'";
    mysqli_query($conn, $query);
    header('Location: surveys.php');
} elseif ($action == 'delete') {
    $id = $_POST['id'];
    // Delete survey from the database
    $query = "DELETE FROM surveys WHERE id='$id'";
    mysqli_query($conn, $query);
    header('Location: surveys.php');
}
?>
