<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// 1. Enhanced database connection check
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'debug' => $e->getMessage()
    ]);
    exit;
}

// 2. Strict input validation
if (!isset($_GET['user_id']) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameter: user_id',
        'debug' => ['received_params' => $_GET]
    ]);
    exit;
}

// 3. Secure session handling
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required',
        'debug' => ['session_status' => session_status()]
    ]);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$other_user_id = $_GET['user_id'];

// 4. Query building with exact field matching
try {
    if ($other_user_id === 'broadcast') {
        // Broadcast messages query (admin to all users)
        $query = "SELECT 
                    m.id,
                    m.sender_id,
                    m.content,
                    m.sent_at,
                    m.is_read,
                    u.username as sender
                  FROM messages m
                  JOIN users u ON m.sender_id = u.id
                  WHERE m.receiver_id = :user_id
                  AND m.is_admin = 1
                  ORDER BY m.sent_at ASC";
        
        $params = [':user_id' => $current_user_id];
    } else {
        // Direct messages query
        $other_user_id = (int)$other_user_id;
        $query = "SELECT 
                    m.id,
                    m.sender_id,
                    m.content,
                    m.sent_at,
                    m.is_read,
                    u.username as sender
                  FROM messages m
                  JOIN users u ON m.sender_id = u.id
                  WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
                  OR (m.sender_id = :user2 AND m.receiver_id = :user1)
                  ORDER BY m.sent_at ASC";
        
        $params = [
            ':user1' => $current_user_id,
            ':user2' => $other_user_id
        ];
    }

    // 5. Secure statement preparation and execution
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        throw new PDOException("Prepare failed: " . implode(" ", $pdo->errorInfo()));
    }

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    if (!$stmt->execute()) {
        throw new PDOException("Execute failed: " . implode(" ", $stmt->errorInfo()));
    }

    // 6. Data formatting with null checks
    $messages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            'id' => (int)$row['id'],
            'sender' => $row['sender'] ?? 'Unknown',
            'message' => $row['content'] ?? '',
            'sent_at' => isset($row['sent_at']) ? date('M j, Y g:i a', strtotime($row['sent_at'])) : 'Unknown',
            'is_own' => isset($row['sender_id']) && $row['sender_id'] == $current_user_id
        ];
    }

    // 7. Success response
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'meta' => [
            'count' => count($messages),
            'current_user' => $current_user_id,
            'other_user' => $other_user_id
        ]
    ]);

} catch (PDOException $e) {
    // 8. Detailed error reporting
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Message retrieval failed',
        'debug' => [
            'error' => $e->getMessage(),
            'query' => $query ?? 'Not prepared',
            'params' => $params ?? []
        ]
    ]);
}