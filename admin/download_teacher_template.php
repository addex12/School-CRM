<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="teacher_import_template.csv"');

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
