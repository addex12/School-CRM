<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="user_import_template.csv"');

$headers = ['username', 'email', 'role'];
$output = fopen('php://output', 'w');
fputcsv($output, $headers);

// Add example row
fputcsv($output, ['john_doe', 'john@example.com', 'teacher']);
exit();
?>