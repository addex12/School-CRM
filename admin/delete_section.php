<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// Debugging output for troubleshooting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            // Optional: set a session message for success
            // $_SESSION['message'] = "Section deleted successfully.";
        } else {
            // Optional: set a session message for failure
            // $_SESSION['error'] = "Failed to delete section.";
        }
    } else {
        // Optional: set a session message for invalid id
        // $_SESSION['error'] = "Invalid section id.";
    }
    header("Location: sections.php");
    exit;
}

// If accessed via GET, redirect without doing anything
header("Location: sections.php");
exit;
?>