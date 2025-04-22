<?php
require_once 'db.php'; // adjust path as needed

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: sections.php");
exit;
