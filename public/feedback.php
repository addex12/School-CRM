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
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = sanitize($_POST["subject"]);
    $message = sanitize($_POST["message"]);
    $parent_id = $_SESSION['parent_id'];

    $sql = "INSERT INTO feedback (parent_id, subject, message) VALUES ('$parent_id', '$subject', '$message')";

    if ($conn->query($sql) === TRUE) {
        display_success("Feedback submitted successfully.");
    } else {
        display_error("Error: " . $sql . "<br>" . $conn->error);
    }
}
?>
    <div class="container">
        <h1>Feedback and Concerns</h1>
        <form method="POST">
            <label for="subject">Subject:</label><br>
            <input type="text" name="subject" required><br>
            <label for="message">Message:</label><br>
            <textarea name="message" rows="5" required></textarea><br>
            <button type="submit">Submit Feedback</button>
        </form>
    </div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
