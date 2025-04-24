<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Fetch roles for dropdown (for edit rendering)
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_KEY_PAIR);

// AJAX: Search/filter users
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // ...existing code for search/filter...
    // ...copy from original file, lines 38-97...
    // ...existing code...
    exit;
}

// AJAX: Update user
if (isset($_POST['ajax']) && $_POST['ajax'] === 'update_user') {
    // ...existing code for update...
    exit;
}

// AJAX: Delete user
if (isset($_POST['ajax']) && $_POST['ajax'] === 'delete_user') {
    // ...existing code for delete...
    exit;
}

// AJAX: Bulk delete
if (isset($_POST['ajax']) && $_POST['ajax'] === 'bulk_delete') {
    // ...existing code for bulk delete...
    exit;
}

// AJAX: Bulk status
if (isset($_POST['ajax']) && $_POST['ajax'] === 'bulk_status') {
    // ...existing code for bulk status...
    exit;
}

// AJAX: Bulk role
if (isset($_POST['ajax']) && $_POST['ajax'] === 'bulk_role') {
    // ...existing code for bulk role...
    exit;
}

// AJAX: Bulk export
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_export') {
    require_once __DIR__ . '/export_users.php';
    $ids = json_decode($_GET['ids'] ?? '[]', true);
    $filters = [
        'status' => isset($_GET['status']) ? $_GET['status'] : null,
        'role' => isset($_GET['role']) ? $_GET['role'] : null,
        'online' => isset($_GET['online']) ? $_GET['online'] : null,
        'search' => isset($_GET['search']) ? $_GET['search'] : null,
    ];
    export_users_csv($pdo, $ids, $filters);
    exit;
}
?>