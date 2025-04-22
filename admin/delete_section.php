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

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Optionally, you can check if the section exists before deleting
    $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: sections.php");
exit();
?>