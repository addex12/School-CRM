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
    protected $adminConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->adminConnections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        parse_str($conn->httpRequest->getUri()->getQuery(), $query);
        
        if (isset($query['user_id']) && isset($query['role'])) {
            $userId = (int)$query['user_id'];
            $role = $query['role'];
            
            if ($role === 'admin') {
                $this->adminConnections[$userId] = $conn;
            } else {
                $this->userConnections[$userId] = $conn;
                $stmt = $GLOBALS['pdo']->prepare("UPDATE chat_status SET is_online = 1, last_active = NOW() WHERE user_id = ?");
                $stmt->execute([$userId]);
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'message') {
            // Save message to database
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO chat_messages 
                (thread_id, user_id, message, is_admin, created_at)
                VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['thread_id'],
                $data['user_id'],
                $data['message'],
                $data['is_admin']
            ]);

            // Notify recipients
            if ($data['is_admin']) {
                // Admin message to user
                if (isset($this->userConnections[$data['recipient_id']])) {
                    $this->userConnections[$data['recipient_id']]->send(json_encode($data));
                }
            } else {
                // User message to all admins
                foreach ($this->adminConnections as $adminConn) {
                    $adminConn->send(json_encode($data));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Remove from connections
        if (($userId = array_search($conn, $this->userConnections)) !== false) {
            unset($this->userConnections[$userId]);
            $stmt = $GLOBALS['pdo']->prepare("UPDATE chat_status SET is_online = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        
        if (($adminId = array_search($conn, $this->adminConnections)) !== false) {
            unset($this->adminConnections[$adminId]);
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