<?php
header('Content-Type: application/json');
$progressFile = __DIR__ . '/../restore_progress.txt';
if (file_exists($progressFile)) {
    $data = file_get_contents($progressFile);
    echo $data;
} else {
    echo json_encode([
        'percent' => 0,
        'message' => 'Waiting for restore to start...',
        'timestamp' => time()
    ]);
}
