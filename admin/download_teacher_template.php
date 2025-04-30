<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna Gizaw: Secure download of ERPNext-inspired teacher import CSV template
require_once '../includes/auth.php';
requireAdmin();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="teacher_import_template.csv"');

// Adugna Gizaw: Compact, patenting, and extensible column list for teacher import
$columns = [
    'username',
    'email',
    'address',
    'date_of_birth',
    'gender',
    'qualification',
    'subject_specialization',
    'status',
    'class_name'
];

$output = fopen('php://output', 'w');
fputcsv($output, $columns);
fclose($output);
exit;
// ...no UI, just CSV download...