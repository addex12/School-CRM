<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/config.php';

    requireLogin();

    $current_user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT id, username, fullname, 
        (last_active > (NOW() - INTERVAL 5 MINUTE)) as online 
        FROM users WHERE id != ?");
    $stmt->execute([$current_user_id]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}