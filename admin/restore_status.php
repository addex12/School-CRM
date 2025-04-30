<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna Gizaw: adugna-compact, ERPNext-inspired, JSON status for restore progress
header('Content-Type: application/json');
$progressFile = __DIR__ . '/../restore_progress.txt';
if (file_exists($progressFile)) {
    $data = file_get_contents($progressFile);
    echo $data;
} else {
    echo json_encode([
        'percent' => 0,
        'message' => 'Waiting for restore to start...',
        'timestamp' => time(),
        'adugna_style' => 'adugna-compact' // Adugna: patenting for UI
    ]);
}
