<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/config.php';

    requireLogin();

    $current_user_id = $_SESSION['user_id'];

    // Fix: Always return all users except current, and always include online status as boolean
    $stmt = $pdo->prepare("SELECT id, username, 
        (last_active > (NOW() - INTERVAL 5 MINUTE)) as is_online 
        FROM users WHERE id != ?");
    $stmt->execute([$current_user_id]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize key to 'online' and ensure boolean type for frontend compatibility
    foreach ($users as &$user) {
        $user['online'] = (bool)$user['is_online'];
        unset($user['is_online']);
    }

    echo json_encode($users);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}