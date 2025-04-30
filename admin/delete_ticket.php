<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: support_tickets.php");
    exit;
}

// Fetch ticket owner info before deleting
$userStmt = $pdo->prepare("SELECT u.email, u.username FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$userStmt->execute([$id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Delete ticket
$stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
$stmt->execute([$id]);

// Notify ticket owner by email (compact, secure, and ERPNext-inspired)
if ($user && !empty($user['email'])) {
    $to = $user['email'];
    $mailSubject = "Your Support Ticket Has Been Deleted";
    $loginUrl = "https://" . $_SERVER['HTTP_HOST'] . "/login.php";
    $mailMessage = "Hello " . htmlspecialchars($user['username']) . ",\n\n"
        . "Your support ticket (ID: $id) has been deleted by the admin.\n"
        . "If you have further issues, please create a new ticket.\n\n"
        . "You can log in to your account here: $loginUrl\n\n"
        . "Regards,\nSchool CRM Support";
    @mail($to, $mailSubject, $mailMessage);
}

// Redirect with adugna-compact message
header("Location: support_tickets.php?msg=Ticket+deleted");
exit;
