<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
require_once __DIR__.'/../config.php';

ignore_user_abort(true);
set_time_limit(0);

if (!isset($_SESSION['user_id'])) {
    die();
}

$lastEventId = $_SERVER['HTTP_LAST_EVENT_ID'] ?? 0;

while (true) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM messages 
            WHERE receiver_id = ? AND id > ? 
            ORDER BY id DESC LIMIT 10");
        $stmt->execute([$_SESSION['user_id'], $lastEventId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($messages)) {
            $lastEventId = $messages[0]['id'];
            echo "data: " . json_encode($messages) . "\n\n";
            ob_flush();
            flush();
        }
    } catch (PDOException $e) {
        error_log('SSE error: ' . $e->getMessage());
    }
    sleep(1);
}
