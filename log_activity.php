<?php
$data = json_decode(file_get_contents('php://input'), true);
if ($data) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $entry = sprintf(
        "[%s] %s: %s\n",
        date('Y-m-d H:i:s'),
        $data['action'] ?? 'unknown',
        json_encode($data)
    );
    
    file_put_contents($logDir . '/raw_activity.log', $entry, FILE_APPEND | LOCK_EX);
}
http_response_code(204);
?>