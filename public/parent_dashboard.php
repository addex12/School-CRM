<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 */
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    redirect('index.php');
}
require_once __DIR__ . '/../includes/header.php';
?>
    <div class="container">
        <h1>Parent Dashboard</h1>
        <p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
        <a href="feedback.php">Feedback</a><br>
        <a href="messaging.php">Messaging</a><br>
        <a href="surveys.php">Surveys</a><br>
        <a href="notifications.php">Notifications</a><br>
        <a href="?logout">Logout</a>
    </div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
