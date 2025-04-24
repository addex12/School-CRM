<?php
require_once '../includes/db_connect.php'; // adjust path if needed

// Fetch roles from the database
$roles = [];
$result = $conn->query("SELECT id, name FROM roles");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Static permissions list (replace with DB if you have a permissions table)
$permissions = [
    'View Dashboard', 'Manage Users', 'Manage Surveys', 'Manage Students', 'Manage Teachers', 'Manage Parents', 'Send Messages', 'View Reports', 'Manage Settings'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Roles & Permissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* ERPNext-inspired card and table styling */
        body { font-family: Inter, Arial, sans-serif; background: #f5f7fa; margin: 0; }
        .container { margin-left: 270px; padding: 2rem; }
        h2 { color: #215967; margin-bottom: 1.5rem; }
        .erp-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .erp-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s;
            margin-bottom: 1rem;
        }
        .erp-btn i { margin-right: 6px; }
        .erp-btn:hover { background: #215967; }
        table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
        }
        th, td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background: #f4f8fb;
            color: #215967;
            font-weight: 600;
        }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) { background: #f8fafc; }
        .actions button {
            background: #f3f4f6;
            border: none;
            color: #215967;
            border-radius: 4px;
            padding: 0.4rem 0.7rem;
            margin-right: 6px;
            cursor: pointer;
            transition: background 0.18s;
        }
        .actions button:hover { background: #e2efda; }
        .permissions-list label {
            display: inline-block;
            margin-right: 1.2rem;
            margin-bottom: 0.3rem;
            font-size: 0.97rem;
        }
        .permissions-list input[type="checkbox"] {
            accent-color: #2563eb;
            margin-right: 4px;
        }
        @media (max-width: 900px) {
            .container { margin-left: 70px; padding: 1rem; }
            .erp-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .container { margin-left: 0; padding: 0.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="container">
        <div class="erp-card">
            <h2><i class="fas fa-user-shield"></i> Manage Roles</h2>
            <button class="erp-btn"><i class="fas fa-plus"></i> Add Role</button>
            <table>
                <thead>
                    <tr>
                        <th>Role Name</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= htmlspecialchars($role['name']) ?></td>
                        <td>
                            <!-- In production, fetch assigned permissions from DB -->
                            <form method="post" action="">
                                <div class="permissions-list">
                                <?php foreach ($permissions as $perm): ?>
                                    <label>
                                        <input type="checkbox" name="permissions[<?= $role['id'] ?>][]" value="<?= $perm ?>">
                                        <?= htmlspecialchars($perm) ?>
                                    </label>
                                <?php endforeach; ?>
                                </div>
                                <button type="submit" class="erp-btn" style="padding:0.3rem 1rem;font-size:0.95rem;margin-top:0.5rem;"><i class="fas fa-save"></i>Save</button>
                            </form>
                        </td>
                        <td class="actions">
                            <button title="Edit"><i class="fas fa-edit"></i></button>
                            <button title="Delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
