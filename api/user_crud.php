<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if ($action === 'update') {
        $username = trim($_POST['username'] ?? '');
        $role_id = trim($_POST['role_id'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $online = isset($_POST['online']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE users SET username = :username, role_id = :role_id, active = :active, online = :online WHERE id = :id");
        $ok = $stmt->execute([
            ':username' => $username,
            ':role_id' => $role_id,
            ':active' => $active,
            ':online' => $online,
            ':id' => $id
        ]);
        echo json_encode(['status' => $ok ? 'success' : 'fail']);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $ok = $stmt->execute([':id' => $id]);
        echo json_encode(['status' => $ok ? 'success' : 'fail']);
        exit;
    }
}

echo json_encode(['status' => 'invalid']);
exit;
<?php ob_clean(); ?>