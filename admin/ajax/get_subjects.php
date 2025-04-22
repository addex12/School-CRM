<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['class_id'])) {
    echo json_encode([]);
    exit;
}

$class_id = intval($_GET['class_id']);

$stmt = $pdo->prepare("
    SELECT s.id, s.subject_name
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    WHERE cs.class_id = ?
    ORDER BY s.subject_name
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subjects);