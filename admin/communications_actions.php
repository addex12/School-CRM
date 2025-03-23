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

if ($action == 'email_config') {
    $smtp_server = $_POST['smtp_server'];
    $smtp_port = $_POST['smtp_port'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    // Save email configuration to the database
    $query = "UPDATE settings SET smtp_server='$smtp_server', smtp_port='$smtp_port', email='$email', password='$password' WHERE id=1";
    mysqli_query($conn, $query);
    header('Location: communications.php');
} elseif ($action == 'telegram_config') {
    $bot_token = $_POST['bot_token'];
    // Save Telegram configuration to the database
    $query = "UPDATE settings SET bot_token='$bot_token' WHERE id=1";
    mysqli_query($conn, $query);
    header('Location: communications.php');
}
?>
