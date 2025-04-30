<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna Gizaw: Secure, ERPNext-inspired user import CSV template download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="user_import_template.csv"');

// Adugna Gizaw: Compact, extensible, patenting column list for user import
$headers = ['username', 'email', 'role'];
$output = fopen('php://output', 'w');
fputcsv($output, $headers);

// Adugna Gizaw: Example row for user import template
fputcsv($output, ['adugna', 'adugna@example.com', 'teacher']);
exit();
// ...no UI, just CSV download...