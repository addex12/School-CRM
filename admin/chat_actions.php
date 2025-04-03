<?php
/**
 * Handle chat-related actions
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if action is provided
if (!isset($_GET['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

$action = $_GET['action'];
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'start_chat':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = (int)$_POST['user_id'];
                
                // Check if thread already exists
                $stmt = $pdo->prepare("SELECT id FROM chat_threads WHERE user_id = ? AND status = 'open' LIMIT 1");
                $stmt->execute([$userId]);
                $thread = $stmt->fetch();
                
                if ($thread) {
                    $threadId = $thread['id'];
                } else {
                    // Create new thread
                    $stmt = $pdo->prepare("INSERT INTO chat_threads (user_id, subject, status) 
                                          VALUES (?, 'New Chat', 'open')");
                    $stmt->execute([$userId]);
                    $threadId = $pdo->lastInsertId();
                }
                
                $response = ['success' => true, 'thread_id' => $threadId];
            }
            break;
            
        case 'get_messages':
            if (isset($_GET['thread_id'])) {
                $threadId = (int)$_GET['thread_id'];
                
                $stmt = $pdo->prepare("SELECT cm.*, u.username, u.avatar 
                                      FROM chat_messages cm
                                      LEFT JOIN users u ON cm.user_id = u.id
                                      WHERE cm.thread_id = ?
                                      ORDER BY cm.created_at ASC");
                $stmt->execute([$threadId]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Mark messages as read if they're from the user
                $pdo->prepare("UPDATE chat_messages SET is_read = 1 
                              WHERE thread_id = ? AND is_admin = 0")
                   ->execute([$threadId]);
                
                $response = ['success' => true, 'messages' => $messages];
            }
            break;
            
        case 'send_message':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['thread_id'])) {
                $threadId = (int)$_POST['thread_id'];
                $message = trim($_POST['message']);
                
                if (empty($message)) {
                    $response = ['success' => false, 'message' => 'Message cannot be empty'];
                    break;
                }
                
                // Verify thread exists and is open
                $stmt = $pdo->prepare("SELECT id FROM chat_threads WHERE id = ? AND status = 'open'");
                $stmt->execute([$threadId]);
                
                if (!$stmt->fetch()) {
                    $response = ['success' => false, 'message' => 'Chat is closed or does not exist'];
                    break;
                }
                
                // Insert message
                $stmt = $pdo->prepare("INSERT INTO chat_messages (thread_id, user_id, message, is_admin, created_at)
                                      VALUES (?, ?, ?, 1, NOW())");
                $stmt->execute([$threadId, $_SESSION['user_id'], $message]);
                
                // Update thread timestamp
                $pdo->prepare("UPDATE chat_threads SET updated_at = NOW() WHERE id = ?")
                   ->execute([$threadId]);
                
                $response = ['success' => true];
            }
            break;
            
        case 'save_notes':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['thread_id'])) {
                $threadId = (int)$_POST['thread_id'];
                $notes = trim($_POST['admin_notes']);
                
                $stmt = $pdo->prepare("UPDATE chat_threads SET admin_notes = ? WHERE id = ?");
                $stmt->execute([$notes, $threadId]);
                
                $response = ['success' => true];
            }
            break;
            
        case 'close_chat':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['thread_id'])) {
                $threadId = (int)$_POST['thread_id'];
                
                $stmt = $pdo->prepare("UPDATE chat_threads SET status = 'closed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$threadId]);
                
                $response = ['success' => true];
            }
            break;
            
        case 'get_unread_counts':
            // Get total unread count
            $stmt = $pdo->prepare("SELECT COUNT(*) as total_unread
                                  FROM chat_messages cm
                                  JOIN chat_threads ct ON cm.thread_id = ct.id
                                  WHERE cm.is_admin = 0 AND cm.is_read = 0 AND ct.status = 'open'");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get unread counts per thread
            $stmt = $pdo->prepare("SELECT cm.thread_id, COUNT(*) as unread_count
                                  FROM chat_messages cm
                                  JOIN chat_threads ct ON cm.thread_id = ct.id
                                  WHERE cm.is_admin = 0 AND cm.is_read = 0 AND ct.status = 'open'
                                  GROUP BY cm.thread_id");
            $stmt->execute();
            $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'total_unread' => $total['total_unread'],
                'threads' => $threads
            ];
            break;
            
        case 'get_quick_responses':
            // In a real app, you might store these in the database
            $responses = [
                ['id' => 1, 'text' => 'Thank you for your message. We will get back to you shortly.'],
                ['id' => 2, 'text' => 'Could you please provide more details about your issue?'],
                ['id' => 3, 'text' => 'I understand your concern. Let me check that for you.'],
                ['id' => 4, 'text' => 'Have you tried refreshing the page?'],
                ['id' => 5, 'text' => 'We appreciate your feedback!']
            ];
            
            $response = ['success' => true, 'responses' => $responses];
            break;
    }
} catch (Exception $e) {
    error_log("Chat action error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);