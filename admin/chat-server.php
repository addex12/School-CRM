<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require dirname(__DIR__) . '/vendor/autoload.php';
require_once '../includes/config.php'; // Use existing $pdo

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $userConns = [];
    protected $connUsers = [];
    protected $pdo;

    public function __construct($pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        // Wait for auth message from client
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data) return;
        // First message must be auth
        if (!isset($this->connUsers[$from->resourceId])) {
            if (isset($data['type']) && $data['type'] === 'auth' && isset($data['user_id'])) {
                $user_id = intval($data['user_id']);
                $role = isset($data['role']) ? $data['role'] : 'user';
                $this->connUsers[$from->resourceId] = [
                    'user_id' => $user_id,
                    'role' => $role
                ];
                $this->userConns[$user_id] = $from;
                // Update last_active with error logging
                try {
                    $stmt = $this->pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                } catch (PDOException $e) {
                    error_log("Failed to update last_active for user_id $user_id: " . $e->getMessage());
                }
                return;
            } else {
                $from->close();
                return;
            }
        }
        // Handle chat messages
        if ($data['type'] === 'chat' && isset($data['to'], $data['message'])) {
            $from_user_id = $this->connUsers[$from->resourceId]['user_id'];
            $to_user_id = intval($data['to']);
            $message = $data['message'];
            // Save to DB
            $stmt = $this->pdo->prepare("INSERT INTO chat_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$from_user_id, $to_user_id, $message]);
            $msg_id = $this->pdo->lastInsertId();
            $payload = json_encode([
                'type' => 'chat',
                'from' => $from_user_id,
                'to' => $to_user_id,
                'message' => $message,
                'msg_id' => $msg_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            // Send to recipient if online
            if (isset($this->userConns[$to_user_id])) {
                $this->userConns[$to_user_id]->send($payload);
            }
            // Echo back to sender
            $from->send($payload);
        }
        // Heartbeat/keepalive
        if ($data['type'] === 'ping') {
            $user_id = $this->connUsers[$from->resourceId]['user_id'];
            try {
                $stmt = $this->pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
            } catch (PDOException $e) {
                error_log("Failed to update last_active during ping for user_id $user_id: " . $e->getMessage());
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        if (isset($this->connUsers[$conn->resourceId])) {
            $user_id = $this->connUsers[$conn->resourceId]['user_id'];
            unset($this->userConns[$user_id]);
            unset($this->connUsers[$conn->resourceId]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$server = Ratchet\Server\IoServer::factory(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            new ChatServer($pdo)
        )
    ),
    8080
);

$server->run();