<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/

/**
 * Enhanced Log Dashboard
 * Developer: Adugna Gizaw
 */
$title = "Activity Monitoring Dashboard";
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Enhanced admin verification
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

// Get filter parameters
$riskLevel = $_GET['risk_level'] ?? '';
$eventType = $_GET['event_type'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Function to safely read log files with rotation check
function readLogWithRotation($filePath) {
    global $maxFileSize;
    
    if (!file_exists($filePath)) return '';
    
    if (filesize($filePath) > $maxFileSize) {
        $backupPath = $filePath . '.' . date('Ymd-His');
        rename($filePath, $backupPath);
        file_put_contents($filePath, '');
    }
    
    return file_get_contents($filePath);
}

// Enhanced log processing with filters
function processLogs($logContent, $filters = []) {
    $entries = [];
    $lines = explode("\n", $logContent);
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        $jsonStart = strpos($line, '{');
        if ($jsonStart === false) continue;
        
        $jsonStr = substr($line, $jsonStart);
        $data = @json_decode($jsonStr, true);
        if (!$data) continue;
        
        // Process entry (same as your existing extractSensitiveData function)
        $entry = processLogEntry($data, $line);
        
        // Apply filters
        if (!empty($filters['risk_level']) && $entry['risk_level'] !== $filters['risk_level']) {
            continue;
        }
        if (!empty($filters['event_type']) && $entry['type'] !== $filters['event_type']) {
            continue;
        }
        if (!empty($filters['search'])) {
            $searchIn = json_encode($entry);
            if (stripos($searchIn, $filters['search']) === false) {
                continue;
            }
        }
        if (!empty($filters['date_from']) && strtotime($entry['timestamp']) < strtotime($filters['date_from'])) {
            continue;
        }
        if (!empty($filters['date_to']) && strtotime($entry['timestamp']) > strtotime($filters['date_to'] . ' 23:59:59')) {
            continue;
        }
        
        $entries[] = $entry;
    }
    
    return $entries;
}

// Read and process all log files with filters
$allEntries = [];
$filters = [
    'risk_level' => $riskLevel,
    'event_type' => $eventType,
    'search' => $searchQuery,
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

foreach ($logFiles as $logFile) {
    $logContent = readLogWithRotation($logFile);
    $allEntries = array_merge($allEntries, processLogs($logContent, $filters));
}

// Sort by timestamp descending
usort($allEntries, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Get unique event types for filter dropdown
$eventTypes = array_unique(array_column($allEntries, 'type'));
sort($eventTypes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing styles here */
        .adugna-filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .adugna-filter-group {
            margin-bottom: 0;
        }
        .adugna-filter-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        .adugna-filter-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .adugna-filter-btn {
            background: #1976d2;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            align-self: flex-end;
        }
        .adugna-filter-btn:hover {
            background: #1565c0;
        }
        .adugna-reset-btn {
            background: #6c757d;
            margin-left: 10px;
        }
        .adugna-reset-btn:hover {
            background: #5a6268;
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
            
            <!-- Filter Section -->
            <form method="get" action="" class="adugna-filters">
                <div class="adugna-filter-group">
                    <label class="adugna-filter-label">Risk Level</label>
                    <select name="risk_level" class="adugna-filter-input">
                        <option value="">All Levels</option>
                        <option value="Critical" <?= $riskLevel === 'Critical' ? 'selected' : '' ?>>Critical</option>
                        <option value="High" <?= $riskLevel === 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Medium" <?= $riskLevel === 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="Low" <?= $riskLevel === 'Low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>
                
                <div class="adugna-filter-group">
                    <label class="adugna-filter-label">Event Type</label>
                    <select name="event_type" class="adugna-filter-input">
                        <option value="">All Types</option>
                        <?php foreach ($eventTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $eventType === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="adugna-filter-group">
                    <label class="adugna-filter-label">Date From</label>
                    <input type="date" name="date_from" class="adugna-filter-input" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                
                <div class="adugna-filter-group">
                    <label class="adugna-filter-label">Date To</label>
                    <input type="date" name="date_to" class="adugna-filter-input" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                
                <div class="adugna-filter-group">
                    <label class="adugna-filter-label">Search</label>
                    <input type="text" name="search" class="adugna-filter-input" placeholder="Search..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
                
                <div class="adugna-filter-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="adugna-filter-btn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="?" class="adugna-filter-btn adugna-reset-btn">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
            
            <!-- Your existing stats cards and table here -->
            <!-- ... -->
            
            <!-- Pagination -->
            <div style="margin-top: 20px; text-align: center;">
                <?php
                $totalEntries = count($allEntries);
                $perPage = 50;
                $totalPages = ceil($totalEntries / $perPage);
                $currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
                $offset = ($currentPage - 1) * $perPage;
                $paginatedEntries = array_slice($allEntries, $offset, $perPage);
                ?>
                
                <div style="display: inline-block;">
                    <?php if ($currentPage > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="adugna-action-btn">
                            <i class="fas fa-angle-double-left"></i> First
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="adugna-action-btn">
                            <i class="fas fa-angle-left"></i> Prev
                        </a>
                    <?php endif; ?>
                    
                    <span style="margin: 0 10px;">
                        Page <?= $currentPage ?> of <?= $totalPages ?>
                    </span>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="adugna-action-btn">
                            Next <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="adugna-action-btn">
                            Last <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Your existing modal and scripts here -->
            <!-- ... -->
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>