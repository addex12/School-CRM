<?php
/**
 * Handle notification actions
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if action is provided
if (!isset($_GET['action'])) {
    header("Location: notifications.php");
    exit();
}

$action = $_GET['action'];
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'notifications.php';

try {
    switch ($action) {
        case 'mark_read':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $_SESSION['success'] = "Notification marked as read";
            }
            break;
            
        case 'delete':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $_SESSION['success'] = "Notification deleted";
            }
            break;
            
        case 'batch_action':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_ids'])) {
                $ids = array_map('intval', $_POST['notification_ids']);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                if (isset($_POST['mark_read'])) {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders) AND user_id = ?");
                    $stmt->execute(array_merge($ids, [$_SESSION['user_id']]));
                    $_SESSION['success'] = "Selected notifications marked as read";
                } elseif (isset($_POST['delete'])) {
                    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id IN ($placeholders) AND user_id = ?");
                    $stmt->execute(array_merge($ids, [$_SESSION['user_id']]));
                    $_SESSION['success'] = "Selected notifications deleted";
                }
            }
            break;
            
        default:
            $_SESSION['error'] = "Invalid action";
            break;
    }
} catch (Exception $e) {
    error_log("Notification action error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while processing your request";
}

header("Location: $redirectUrl");
exit();