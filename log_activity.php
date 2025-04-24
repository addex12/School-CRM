<?php
// Accept JSON POST data
$data = json_decode(file_get_contents('php://input'), true);
if ($data) {
    $logFile = __DIR__ . '/log';
    $entry = sprintf(
        "[%s] %s: %s\n",
        date('Y-m-d H:i:s'),
        $data['action'] ?? 'unknown',
        json_encode($data)
    );
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}
http_response_code(204);
?>
