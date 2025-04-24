<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_REQUEST['ajax'] ?? '';

switch ($action) {
    case 'search':
        $search = trim($_GET['search'] ?? '');
        $filter_online = isset($_GET['online']) && $_GET['online'] === '1';
        $role_id = trim($_GET['role'] ?? '');
        $status_filter = isset($_GET['status']) && ($_GET['status'] === '0' || $_GET['status'] === '1') ? $_GET['status'] : '';

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "u.username LIKE :search";
            $params[':search'] = "%$search%";
        }
        if ($filter_online) {
            $where[] = "u.online = 1";
        }
        if ($role_id !== '') {
            $where[] = "u.role_id = :role_id";
            $params[':role_id'] = $role_id;
        }
        if ($status_filter !== '') {
            $where[] = "u.active = :status";
            $params[':status'] = $status_filter;
        }
        if ($status_filter === '') {
            $where[] = "u.active = 1";
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.last_active, u.online, u.role_id, u.active, r.role_name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            $where_sql
            ORDER BY u.username");
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['users' => $users]);
        break;

    case 'update_user':
        $id = intval($_POST['id']);
        $username = trim($_POST['username']);
        $role_id = trim($_POST['role_id']);
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $active = $status;
        $stmt = $pdo->prepare("UPDATE users SET username = :username, role_id = :role_id, active = :active WHERE id = :id");
        $ok = $stmt->execute([
            ':username' => $username,
            ':role_id' => $role_id,
            ':active' => $active,
            ':id' => $id
        ]);
        echo json_encode(['result' => $ok ? 'success' : 'fail']);
        break;

    case 'delete_user':
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $ok = $stmt->execute([':id' => $id]);
        echo json_encode(['result' => $ok ? 'success' : 'fail']);
        break;

    // ...add bulk_delete, bulk_status, bulk_role, etc. as needed...

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
