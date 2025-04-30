<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
$pageTitle = "User Roles";

// Handle add role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $role_name = trim($_POST['role_name']);
    if ($role_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
        try {
            $stmt->execute([$role_name]);
            $_SESSION['success'] = "Role added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Role name cannot be empty.";
    }
    header("Location: user_roles.php");
    exit;
}

// Fetch all roles
$roles = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);

// Handle delete role
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $role_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    try {
        $stmt->execute([$role_id]);
        $_SESSION['success'] = "Role deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: user_roles.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting. Admin_sidebar/footer CSS untouched. */
        body { background: #f5f7fa; }
        .adugna-main {
            margin-left: 250px;
            padding: 2vw 2vw 2vw 2vw;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px 0 rgba(80, 112, 255, 0.10), 0 2px 8px 0 rgba(80, 112, 255, 0.04);
            border: 1px solid #e5e7eb;
            padding: 2.2rem 2vw 2vw 2vw;
            margin-bottom: 2.5rem;
            width: 100%;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
            transition: box-shadow 0.2s, border 0.2s;
        }
        .adugna-card h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.2rem;
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            letter-spacing: 0.01em;
        }
        .adugna-form-group {
            margin-bottom: 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }
        .adugna-form-group label {
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.3em;
            font-size: 1em;
            letter-spacing: 0.01em;
        }
        .adugna-form-group input[type="text"] {
            width: 100%;
            border-radius: 0.5em;
            border: 1.2px solid #e5e7eb;
            background: #f3f4f6;
            padding: 0.7em 1em;
            font-size: 1em;
            transition: border 0.18s, box-shadow 0.18s;
            box-sizing: border-box;
            margin-bottom: 0.05em;
        }
        .adugna-form-group input:focus {
            border: 1.2px solid #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 2px #a5b4fc33;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.5em;
            padding: 0.18rem 1.1rem;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i { font-size: 0.95em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        .adugna-table-responsive { width: 100%; overflow-x: auto; }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 1em;
        }
        .adugna-table th, .adugna-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .adugna-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
            font-size: 1em;
        }
        .adugna-table tr:hover { background: #f4f8fb; }
        .adugna-table td:last-child, .adugna-table th:last-child { text-align: right; }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        @media (max-width: 900px) {
            .adugna-card { padding: 1.2rem 1vw; }
            .adugna-main { padding: 1.2rem 1vw; }
        }
        @media (max-width: 600px) {
            .adugna-table th, .adugna-table td { padding: 8px 4px; font-size: 0.97em; }
            .adugna-main { padding: 7px 2px 80px; }
            .adugna-card { padding: 0.7rem 2vw; }
        }
        @media (max-width: 400px) {
            .adugna-card { padding: 2px; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <header class="admin-header" style="width:100%;max-width:600px;margin:0 auto 1.5rem auto;">
                <h1 style="color:#215967;font-weight:700;font-size:clamp(1.3rem,2.5vw,2rem);text-align:center;">User Roles</h1>
            </header>
            <div class="adugna-card">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="adugna-alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="adugna-alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <form method="post" style="margin-bottom:2rem;">
                    <div class="adugna-form-group">
                        <label for="role_name">Add New Role</label>
                        <input type="text" name="role_name" id="role_name" required placeholder="Enter role name">
                    </div>
                    <button type="submit" name="add_role" class="adugna-btn"><i class="fas fa-plus"></i> Add Role</button>
                </form>
                <div class="adugna-table-responsive">
                    <table class="adugna-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Role Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= htmlspecialchars($role['id']) ?></td>
                                <td><?= htmlspecialchars($role['role_name']) ?></td>
                                <td class="actions">
                                    <a href="?delete=<?= $role['id'] ?>" onclick="return confirm('Delete this role?');" class="adugna-btn adugna-btn-danger" title="Delete"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>

