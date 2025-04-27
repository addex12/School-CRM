<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
ob_start(); // Start output buffering
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
$pageTitle = "Admin Dashboard";

if (!isset($pdo) || !$pdo) {
    error_log("Database connection not established.");
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
} else {
    error_log("Database connection established successfully.");
}

// Add more widgets for dashboard revamp
$widgets = [
    [
        "title" => "Total Users",
        "icon" => "fa-users",
        "color" => "blue",
        "query" => "SELECT COUNT(*) FROM users"
    ],
    [
        "title" => "Total Students",
        "icon" => "fa-user-graduate",
        "color" => "purple",
        "query" => "SELECT COUNT(*) FROM students"
    ],
    [
        "title" => "Total Teachers",
        "icon" => "fa-chalkboard-teacher",
        "color" => "teal",
        "query" => "SELECT COUNT(*) FROM teachers"
    ],
    /**[
        "title" => "Total Classes",
        "icon" => "fa-school",
        "color" => "orange",
        "query" => "SELECT COUNT(*) FROM classes"
    ],**/
    [
        "title" => "Active Surveys",
        "icon" => "fa-poll",
        "color" => "green",
        "query" => "SELECT COUNT(*) FROM surveys WHERE is_active = 1"
    ],
    [
        "title" => "Feedback Received",
        "icon" => "fa-comments",
        "color" => "yellow",
        "query" => "SELECT COUNT(*) FROM feedback"
    ],
    [
        "title" => "Open Tickets",
        "icon" => "fa-ticket-alt",
        "color" => "red",
        "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'"
    ],
    /**[
        "title" => "Number of Classes",
        "icon" => "fa-school",
        "color" => "orange",
        "query" => "SELECT COUNT(*) FROM classes"
    ],**/
    /**[
        "title" => "Number of Sections",
        "icon" => "fa-th-large",
        "color" => "teal",
        "query" => "SELECT COUNT(*) FROM sections"
    ],
    // Add widget for total curriculums
    [
        "title" => "Total Curriculums",
        "icon" => "fa-list",
        "color" => "blue",
        "query" => "SELECT COUNT(*) FROM curriculums"
    ]**/
    
    // Add widget for curriculum-grade/class mappings
    /**[
      /**  "title" => "Curriculum-Grade/Class Mappings",
        "icon" => "fa-layer-group",
        "color" => "purple",
        "query" => "SELECT COUNT(*) FROM curriculum_grades"
    ]**/
];

foreach ($widgets as &$widget) {
    try {
        $stmt = $pdo->query($widget['query']);
        $widget['count'] = $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        $widget['count'] = "Error";
        error_log("Widget Error: " . $e->getMessage());
    }
}

// Fetch recent activity log
$activityLog = [];
try {
    $stmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
    $activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Activity Log Error: " . $e->getMessage());
}

// Fetch recent feedback
$feedback = [];
try {
    $stmt = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 5");
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Feedback Error: " . $e->getMessage());
}

// Fetch recent support tickets
$tickets = [];
try {
    $stmt = $pdo->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 5");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Tickets Error: " . $e->getMessage());
}

// Error log viewer: read last 20 lines of error.log
$errorLogLines = [];
$errorLogPath = realpath(__DIR__ . '/../error.log');
if ($errorLogPath && is_readable($errorLogPath)) {
    $lines = file($errorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $errorLogLines = array_slice($lines, -20);
}

// Fetch grade distribution for chart
$gradeChartData = [];
try {
    $stmt = $pdo->query("SELECT grade_letter, COUNT(*) as count FROM grades GROUP BY grade_letter ORDER BY grade_letter");
    $gradeChartData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $gradeChartData = [];
}

// Fetch grade distribution by class
$gradeByClass = [];
try {
    $stmt = $pdo->query("SELECT c.class_name, g.grade_letter, COUNT(*) as count
        FROM grades g
        LEFT JOIN students s ON g.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        GROUP BY c.class_name, g.grade_letter
        ORDER BY c.class_name, g.grade_letter");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $gradeByClass[$row['class_name']][$row['grade_letter']] = $row['count'];
    }
} catch (Exception $e) {
    $gradeByClass = [];
}

// Fetch grade distribution by section
$gradeBySection = [];
try {
    $stmt = $pdo->query("SELECT sec.section_name, g.grade_letter, COUNT(*) as count
        FROM grades g
        LEFT JOIN sections sec ON g.section_id = sec.id
        GROUP BY sec.section_name, g.grade_letter
        ORDER BY sec.section_name, g.grade_letter");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $gradeBySection[$row['section_name']][$row['grade_letter']] = $row['count'];
    }
} catch (Exception $e) {
    $gradeBySection = [];
}

// Fetch grade distribution by level/grade
$gradeByLevel = [];
try {
    $stmt = $pdo->query("SELECT lv.level_name, g.grade_letter, COUNT(*) as count
        FROM grades g
        LEFT JOIN students s ON g.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN class_levels lv ON c.class_level_id = lv.id
        GROUP BY lv.level_name, g.grade_letter
        ORDER BY lv.level_name, g.grade_letter");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $gradeByLevel[$row['level_name']][$row['grade_letter']] = $row['count'];
    }
} catch (Exception $e) {
    $gradeByLevel = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
    <style>
        /* Ensure proper alignment of main content */
.admin-main {
    transition: margin-left 0.2s;
    margin-left: 260px; /* Default sidebar width */
    padding: 2rem;
    overflow-x: hidden;
}

@media (max-width: 900px) {
    .admin-main {
        margin-left: 60px; /* Collapsed sidebar width */
    }
}

@media (max-width: 600px) {
    .admin-main {
        margin-left: 0; /* Sidebar hidden */
    }
}
    </style>
    <script>
        (function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const mainContent = document.querySelector('.admin-main');
            const sidebarToggle = document.getElementById('sidebarToggle');

            // Adjust content margin dynamically
            function adjustContentMargin() {
                if (window.innerWidth > 600) {
                    mainContent.style.marginLeft = sidebar.classList.contains('open') ? '260px' : '60px';
                } else {
                    mainContent.style.marginLeft = sidebar.classList.contains('open') ? '260px' : '0';
                }
            }

            // Toggle sidebar and adjust content margin
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    adjustContentMargin();
                });
            }

            // Adjust on window resize
            window.addEventListener('resize', adjustContentMargin);

            // Initial adjustment
            adjustContentMargin();
        })();
    </script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">

                <!-- Quick Links Section -->
                <div class="quick-links">
                    <a href="users.php" class="quick-link"><i class="fas fa-users"></i><span>Manage Users</span></a>
                    <!--<a href="students.php" class="quick-link"><i class="fas fa-user-graduate"></i><span>Students</span></a>
                    <a href="teachers.php" class="quick-link"><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></a>
                    <a href="classes.php" class="quick-link"><i class="fas fa-school"></i><span>Classes</span></a>
                    <a href="curriculums.php" class="quick-link"><i class="fas fa-list"></i><span>Curriculums</span></a>
                    <a href="sections.php" class="quick-link"><i class="fas fa-th-large"></i><span>Sections</span></a>
                    <a href="subjects.php" class="quick-link"><i class="fas fa-book"></i><span>Subjects</span></a>
                    <a href="grading_scales.php" class="quick-link"><i class="fas fa-chart-line"></i><span>Grading Scales</span></a>
                    <a href="grades.php" class="quick-link"><i class="fas fa-file-alt"></i><span>Grades</span></a> -->
                    <a href="surveys.php" class="quick-link"><i class="fas fa-poll"></i><span>Surveys</span></a>
                    <a href="feedback.php" class="quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                    <a href="support_tickets.php" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
                </div>

                <!-- Widgets Section -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3><?= htmlspecialchars($widget['count']) ?></h3>
                            <p><?= htmlspecialchars($widget['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Grade Scale Chart -->
               <!-- <div class="dashboard-section">
                    <h2>Grade Scale Distribution (All Students)</h2>
                    <canvas id="gradeScaleChart" height="80"></canvas>
                </div> -->

                 <!--Grade Distribution by Class -->
                <!--<div class="dashboard-section">
                    <h2>Grade Distribution by Class</h2>
                    <canvas id="gradeByClassChart" height="100"></canvas>
                </div> -->

                <!-- Grade Distribution by Section -->
                <!--<div class="dashboard-section">
                    <h2>Grade Distribution by Section</h2>
                    <canvas id="gradeBySectionChart" height="100"></canvas>
                </div> -->

                <!-- Grade Distribution by Level/Grade -->
               <!-- <div class="dashboard-section">
                    <h2>Grade Distribution by Level/Grade</h2>
                    <canvas id="gradeByLevelChart" height="100"></canvas>
                </div> -->
                    
                <!-- System Stats Section -->
                <div class="dashboard-section">
                    <h2>System Stats</h2>
                    <ul>
                        <li>PHP Version: <?= phpversion() ?></li>
                        <li>Server Software: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></li>
                        <li>Database Host: <?= htmlspecialchars(DB_HOST ?? 'localhost') ?></li>
                        <li>Current Time: <?= date('Y-m-d H:i:s') ?></li>
                    </ul>
                </div>

                <!-- Error Log Section -->
                <div class="dashboard-section">
                    <h2>Recent Error Log</h2>
                    <?php if (!empty($errorLogLines)): ?>
                        <pre class="error-log"><?= htmlspecialchars(implode("\n", $errorLogLines)) ?></pre>
                    <?php else: ?>
                        <p>No recent errors found or error.log not readable.</p>
                    <?php endif; ?>
                </div>

                <!-- Activity Log Section -->
                <div class="dashboard-section">
                    <h2>Recent Activity Log</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Activity Type</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activityLog)): ?>
                                    <?php foreach ($activityLog as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['id']) ?></td>
                                            <td><?= htmlspecialchars($log['user_id']) ?></td>
                                            <td><?= htmlspecialchars($log['activity_type']) ?></td>
                                            <td><?= htmlspecialchars($log['description']) ?></td>
                                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No recent activity found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feedback Section -->
                <div class="dashboard-section">
                    <h2>Recent Feedback</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Rating</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($feedback)): ?>
                                    <?php foreach ($feedback as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['id']) ?></td>
                                            <td><?= htmlspecialchars($item['user_id']) ?></td>
                                            <td><?= htmlspecialchars($item['subject']) ?></td>
                                            <td><?= htmlspecialchars($item['message']) ?></td>
                                            <td><?= htmlspecialchars($item['rating']) ?></td>
                                            <td><?= htmlspecialchars($item['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No feedback found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Support Tickets Section -->
                <div class="dashboard-section">
                    <h2>Recent Support Tickets</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tickets)): ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ticket['id']) ?></td>
                                            <td><?= htmlspecialchars($ticket['user_id']) ?></td>
                                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                            <td><?= htmlspecialchars($ticket['status']) ?></td>
                                            <td><?= htmlspecialchars($ticket['priority']) ?></td>
                                            <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No tickets found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        // Grade Scale Chart
        const gradeScaleCtx = document.getElementById('gradeScaleChart').getContext('2d');
        new Chart(gradeScaleCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($gradeChartData)) ?>,
                datasets: [{
                    label: 'Number of Students',
                    data: <?= json_encode(array_values($gradeChartData)) ?>,
                    backgroundColor: '#3498db'
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Grade By Class Chart
        const gradeByClassData = <?= json_encode($gradeByClass) ?>;
        const classLabels = Object.keys(gradeByClassData);
        const gradeLetters = [...new Set([].concat(...Object.values(gradeByClassData).map(Object.keys)))];
        const datasetsByClass = gradeLetters.map(letter => ({
            label: letter,
            data: classLabels.map(cls => gradeByClassData[cls][letter] ?? 0),
            backgroundColor: '#' + Math.floor(Math.random()*16777215).toString(16)
        }));
        new Chart(document.getElementById('gradeByClassChart').getContext('2d'), {
            type: 'bar',
            data: { labels: classLabels, datasets: datasetsByClass },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { stacked: true }, y: { stacked: true } } }
        });

        // Grade By Section Chart
        const gradeBySectionData = <?= json_encode($gradeBySection) ?>;
        const sectionLabels = Object.keys(gradeBySectionData);
        const gradeLettersSection = [...new Set([].concat(...Object.values(gradeBySectionData).map(Object.keys)))];
        const datasetsBySection = gradeLettersSection.map(letter => ({
            label: letter,
            data: sectionLabels.map(sec => gradeBySectionData[sec][letter] ?? 0),
            backgroundColor: '#' + Math.floor(Math.random()*16777215).toString(16)
        }));
        new Chart(document.getElementById('gradeBySectionChart').getContext('2d'), {
            type: 'bar',
            data: { labels: sectionLabels, datasets: datasetsBySection },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { stacked: true }, y: { stacked: true } } }
        });

        // Grade By Level Chart
        const gradeByLevelData = <?= json_encode($gradeByLevel) ?>;
        const levelLabels = Object.keys(gradeByLevelData);
        const gradeLettersLevel = [...new Set([].concat(...Object.values(gradeByLevelData).map(Object.keys)))];
        const datasetsByLevel = gradeLettersLevel.map(letter => ({
            label: letter,
            data: levelLabels.map(lv => gradeByLevelData[lv][letter] ?? 0),
            backgroundColor: '#' + Math.floor(Math.random()*16777215).toString(16)
        }));
        new Chart(document.getElementById('gradeByLevelChart').getContext('2d'), {
            type: 'bar',
            data: { labels: levelLabels, datasets: datasetsByLevel },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { stacked: true }, y: { stacked: true } } }
        });
    </script>
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>
