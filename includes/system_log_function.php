<?php
function adugna_log_system_action($pdo, $action, $description = '', $user_id = null, $username = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($username === null && isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
    }
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $action . ($username ? " (by $username)" : ""), $description, $ip]);
}
