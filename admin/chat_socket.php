<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        parse_str($conn->httpRequest->getUri()->getQuery(), $query);
        
        if (isset($query['user_id'])) {
            $userId = (int)$query['user_id'];
            $this->userConnections[$userId] = $conn;
            
            // Update online status
            $stmt = $GLOBALS['pdo']->prepare("UPDATE chat_status SET is_online = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        // Broadcast message to relevant parties
        if ($data['type'] === 'message') {
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO chat_messages 
                (thread_id, user_id, message, is_admin, created_at)
                VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['thread_id'],
                $data['user_id'],
                $data['message'],
                $data['is_admin']
            ]);

            // Notify all admins and the recipient
            foreach ($this->userConnections as $userId => $client) {
                if ($userId == $data['recipient_id'] || $_SESSION['role'] === 'admin') {
                    $client->send(json_encode($data));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        foreach ($this->userConnections as $userId => $client) {
            if ($client === $conn) {
                $stmt = $GLOBALS['pdo']->prepare("UPDATE chat_status SET is_online = 0 WHERE user_id = ?");
                $stmt->execute([$userId]);
                unset($this->userConnections[$userId]);
                break;
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();