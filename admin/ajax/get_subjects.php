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
    ts.id,
    t.id AS teacher_id,
    cs.id AS class_subject_id,
    c.id AS class_id,
    c.class_name,
    s.id AS subject_id,
    s.subject_name,
    sec.id AS section_id,
    sec.section_name
FROM teacher_subjects ts
JOIN teachers t ON ts.teacher_id = t.id
JOIN class_subjects cs ON ts.class_subject_id = cs.id
JOIN classes c ON cs.class_id = c.id
JOIN subjects s ON cs.subject_id = s.id
LEFT JOIN sections sec ON ts.section_id = sec.id
WHERE ts.teacher_id = ?;
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subjects);