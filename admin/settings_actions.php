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

if ($action == 'add_feature') {
    $feature_name = $_POST['feature_name'];
    // Add new feature to the database
    $query = "INSERT INTO features (name) VALUES ('$feature_name')";
    mysqli_query($conn, $query);
    header('Location: settings.php');
} elseif ($action == 'add_column') {
    $table_name = $_POST['table_name'];
    $column_name = $_POST['column_name'];
    // Add new column to the specified table
    $query = "ALTER TABLE $table_name ADD $column_name VARCHAR(255)";
    mysqli_query($conn, $query);
    header('Location: settings.php');
} elseif ($action == 'add_row') {
    $table_name_row = $_POST['table_name_row'];
    // Add new row to the specified table
    $query = "INSERT INTO $table_name_row DEFAULT VALUES";
    mysqli_query($conn, $query);
    header('Location: settings.php');
}
?>
