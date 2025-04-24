<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

global $pdo;

$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    header('Location: contact.php?error=Ticket ID is required.');
    exit;
}

// Delete ticket
$stmt = $pdo->prepare('DELETE FROM support_tickets WHERE id = :id');
$stmt->execute([':id' => $ticket_id]);

header('Location: contact.php?success=Ticket deleted successfully.');
exit;
?>
