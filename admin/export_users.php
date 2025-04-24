<?php
if (!function_exists('export_users_csv')) {
    function export_users_csv($pdo, $ids = [], $filters = []) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_export.csv"');
        if (ob_get_level()) ob_end_clean();
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Username', 'Last Active', 'Online', 'Role', 'Status']);
        $where = [];
        $params = [];
        if (is_array($ids) && count($ids)) {
            $where[] = "u.id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
            $params = array_merge($params, $ids);
        }
        if (isset($filters['status']) && ($filters['status'] === '0' || $filters['status'] === '1')) {
            $where[] = "u.active = ?";
            $params[] = $filters['status'];
        }
        if (isset($filters['role']) && $filters['role'] !== '') {
            $where[] = "u.role_id = ?";
            $params[] = $filters['role'];
        }
        if (isset($filters['online']) && $filters['online'] === '1') {
            $where[] = "u.online = 1";
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $where[] = "u.username LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT u.id, u.username, u.last_active, u.online, u.role_id, u.active, r.role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                $where_sql
                ORDER BY u.username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $row['id'],
                $row['username'],
                $row['last_active'],
                $row['online'] ? 'Online' : 'Offline',
                $row['role_name'],
                $row['active'] ? 'Active' : 'Inactive'
            ]);
        }
        fclose($out);
        exit;
    }
}
?>
