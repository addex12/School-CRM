<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/CredentialManager.php';

// Admin verification
requireAdmin();

$credentialManager = new CredentialManager($pdo, 'adugna');
$credentials = $credentialManager->getAllCredentials();

// Handle password reveal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_password'])) {
    $credentialId = (int)$_POST['credential_id'];
    $reason = trim($_POST['reason']);
    
    if (empty($reason)) {
        $error = "Access reason is required";
    } else {
        // Find the credential
        $stmt = $pdo->prepare("
            SELECT sc.*, u.email 
            FROM stored_credentials sc
            JOIN users u ON sc.user_id = u.id
            WHERE sc.id = ?
        ");
        $stmt->execute([$credentialId]);
        $credential = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($credential) {
            $password = $credentialManager->decryptPassword(
                $credential['encrypted_password'],
                $credential['iv']
            );
            
            // Log the access
            $credentialManager->logAccess(
                $_SESSION['user_id'],
                $credentialId,
                $reason,
                $_SERVER['REMOTE_ADDR']
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credential Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .password-cell { font-family: monospace; }
        .reveal-form { display: none; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Stored Credentials</h1>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (isset($password) && isset($credential)): ?>
                    <div class="alert alert-info">
                        <h5>Credential Details</h5>
                        <p><strong>User:</strong> <?= isset($credential['email']) ? htmlspecialchars($credential['email']) : '' ?></p>
                        <p><strong>Domain:</strong> <?= isset($credential['domain']) ? htmlspecialchars($credential['domain']) : '' ?></p>
                        <p><strong>Username:</strong> <?= isset($credential['username']) ? htmlspecialchars($credential['username']) : '' ?></p>
                        <p><strong>Password:</strong> <span class="password-cell"><?= htmlspecialchars($password) ?></span></p>
                        <p><strong>Access Reason:</strong> <?= isset($reason) ? htmlspecialchars($reason) : '' ?></p>
                        <p><em>This access has been logged.</em></p>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Domain</th>
                                <th>Username</th>
                                <th>Stored</th>
                                <th>Last Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($credentials as $cred): ?>
                            <tr>
                                <td><?= htmlspecialchars($cred['email']) ?></td>
                                <td><?= htmlspecialchars($cred['domain']) ?></td>
                                <td><?= htmlspecialchars($cred['username']) ?></td>
                                <td><?= date('M j, Y', strtotime($cred['created_at'])) ?></td>
                                <td><?= $cred['last_used_at'] ? date('M j, Y', strtotime($cred['last_used_at'])) : 'Never' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary reveal-btn" 
                                            data-credential-id="<?= $cred['id'] ?>">
                                        <i class="bi bi-eye"></i> Reveal
                                    </button>
                                </td>
                            </tr>
                            <tr class="reveal-form" id="reveal-form-<?= $cred['id'] ?>">
                                <td colspan="6">
                                    <form method="post" class="row g-3">
                                        <input type="hidden" name="credential_id" value="<?= $cred['id'] ?>">
                                        <div class="col-md-8">
                                            <label for="reason-<?= $cred['id'] ?>" class="form-label">Access Reason</label>
                                            <input type="text" class="form-control" id="reason-<?= $cred['id'] ?>" 
                                                   name="reason" required placeholder="Why do you need access?">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" name="reveal_password" class="btn btn-primary">
                                                <i class="bi bi-unlock"></i> Confirm Reveal
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.reveal-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const formId = 'reveal-form-' + btn.dataset.credentialId;
                const form = document.getElementById(formId);
                
                // Hide all other forms
                document.querySelectorAll('.reveal-form').forEach(f => {
                    if (f.id !== formId) f.style.display = 'none';
                });
                
                // Toggle this form
                form.style.display = form.style.display === 'none' ? 'table-row' : 'none';
            });
        });
    </script>
</body>
</html>