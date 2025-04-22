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

// Thorough check: Only allow POST, check id is numeric and section exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: sections.php");
exit();
?>