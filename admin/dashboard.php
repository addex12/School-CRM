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

// School CRM Dashboard widgets (revamped)
$widgets = [
    [
        "title" => "Total Users",
        "icon" => "fa-users",
        "color" => "blue",
        "query" => "SELECT COUNT(*) FROM users"
    ],
    [
        "title" => "Students",
        "icon" => "fa-user-graduate",
        "color" => "purple",
        "query" => "SELECT COUNT(*) FROM students"
    ],
    [
        "title" => "Teachers",
        "icon" => "fa-chalkboard-teacher",
        "color" => "teal",
        // Fixed: Use staff table with role filter if teachers table does not exist
        // "query" => "SELECT COUNT(*) FROM teachers"
        "query" => "SELECT COUNT(*) FROM staff WHERE role = 'teacher'"
    ],
    [
        "title" => "Parents",
        "icon" => "fa-user-friends",
        "color" => "yellow",
        // Fixed: Use guardians table if parents table does not exist
        // "query" => "SELECT COUNT(*) FROM parents"
        "query" => "SELECT COUNT(*) FROM guardians"
    ],
    [
        "title" => "Active Surveys",
        "icon" => "fa-poll",
        "color" => "green",
        "query" => "SELECT COUNT(*) FROM surveys WHERE is_active = 1"
    ],
    [
        "title" => "Feedback",
        "icon" => "fa-comments",
        "color" => "orange",
        "query" => "SELECT COUNT(*) FROM feedback"
    ],
    [
        "title" => "Open Tickets",
        "icon" => "fa-ticket-alt",
        "color" => "red",
        "query" => "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'"
    ],
    [
        "title" => "Messages",
        "icon" => "fa-envelope",
        "color" => "blue",
        // Fixed: Use inbox table if messages table does not exist
        // "query" => "SELECT COUNT(*) FROM messages"
        "query" => "SELECT COUNT(*) FROM inbox"
    ]
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

// Fetch survey participation stats for chart
$surveyStats = [];
try {
    // Ensure surveys and survey_responses tables exist and columns are correct
    $stmt = $pdo->query("SELECT s.title, COUNT(sr.id) as responses
        FROM surveys s
        LEFT JOIN survey_responses sr ON s.id = sr.survey_id
        GROUP BY s.id, s.title
        ORDER BY responses DESC
        LIMIT 7");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $surveyStats[$row['title']] = $row['responses'];
    }
} catch (Exception $e) {
    $surveyStats = [];
}

// Fetch feedback rating distribution for chart
$feedbackRatings = [];
try {
    // Ensure feedback table and rating column exist
    $stmt = $pdo->query("SELECT rating, COUNT(*) as count FROM feedback GROUP BY rating ORDER BY rating");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $feedbackRatings[$row['rating']] = $row['count'];
    }
} catch (Exception $e) {
    $feedbackRatings = [];
}

// Fetch support ticket status distribution for chart
$ticketStatus = [];
try {
    // Ensure support_tickets table and status column exist
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ticketStatus[$row['status']] = $row['count'];
    }
} catch (Exception $e) {
    $ticketStatus = [];
}

// Error log viewer: read last 20 lines of error.log
$errorLogLines = [];
$errorLogPath = realpath(__DIR__ . '/../error.log');
if ($errorLogPath && is_readable($errorLogPath)) {
    $lines = file($errorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $errorLogLines = array_slice($lines, -20);
} else {
    $errorLogLines = [];
}

// Fetch recent activity log
$activityLog = [];
try {
    // Ensure activity_log table and columns exist
    $stmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
    $activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $activityLog = [];
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
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            background: #f5f7fa;
        }
        .admin-main {
            flex: 1;
            padding: 2rem 2.5rem;
        }
        .admin-header h1 {
            color: #2563eb;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            letter-spacing: 0.01em;
        }
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .dashboard-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
            position: relative;
            border-top: 4px solid #007bfc;
        }
        .dashboard-widget i {
            font-size: 2.2rem;
            margin-bottom: 0.7rem;
            color: #f1c40f;
        }
        .widget-blue { border-top: 4px solid #3498db; }
        .widget-green { border-top: 4px solid #27ae60; }
        .widget-orange { border-top: 4px solid #f39c12; }
        .widget-red { border-top: 4px solid #e74c3c; }
        .widget-purple { border-top: 4px solid #8e44ad; }
        .widget-teal { border-top: 4px solid #16a085; }
        .widget-yellow { border-top: 4px solid #f1c40f; }
        .dashboard-widget h3 {
            font-size: 2.1rem;
            margin: 0.5rem 0 0.2rem 0;
            color: #2c3e50;
        }
        .dashboard-widget p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin: 0;
        }
        .dashboard-section {
            margin-bottom: 2.5rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
        }
        .dashboard-section h2 {
            font-size: 1.3rem;
            color: #34495e;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid #f0f2f5;
            padding-bottom: 0.5rem;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        tr:hover {
            background: #f4f8fb;
        }
        .dashboard-section pre.error-log {
            background: #222;
            color: #f1c40f;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .quick-links {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .quick-link {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.2rem 1.5rem;
            text-align: center;
            min-width: 140px;
            transition: box-shadow 0.15s;
        }
        .quick-link:hover {
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
        }
        .quick-link i {
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
            color: #3498db;
        }
        .quick-link span {
            display: block;
            margin-top: 0.3rem;
            color: #34495e;
            font-weight: 500;
        }
        @media (max-width: 900px) {
            .widget-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-section {
                padding: 1rem 0.5rem;
            }
        }
        @media (max-width: 600px) {
            .admin-main {
                padding: 10px 2px 80px;
            }
            .dashboard-widget, .dashboard-section {
                padding: 1rem 0.5rem;
            }
            th, td {
                padding: 8px 6px;
            }
            .widget-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .dashboard-section h2 {
                font-size: 1.1rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
                <!-- Widgets Section -->
                <div class="widget-grid">
                    <?php foreach ($widgets as $widget): ?>
                        <div class="dashboard-widget widget-<?= htmlspecialchars($widget['color']) ?>">
                            <i class="fas <?= htmlspecialchars($widget['icon']) ?>"></i>
                            <h3><?= is_numeric($widget['count']) ? number_format($widget['count']) : htmlspecialchars($widget['count']) ?></h3>
                            <p><?= htmlspecialchars($widget['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Survey Participation Chart -->
                <div class="dashboard-section">
                    <h2>Survey Participation</h2>
                    <canvas id="surveyParticipationChart" height="80"></canvas>
                </div>

                <!-- Feedback Ratings Chart -->
                <div class="dashboard-section">
                    <h2>Feedback Ratings</h2>
                    <canvas id="feedbackRatingsChart" height="80"></canvas>
                </div>

                <!-- Support Ticket Status Chart -->
                <div class="dashboard-section">
                    <h2>Support Ticket Status</h2>
                    <canvas id="ticketStatusChart" height="80"></canvas>
                </div>

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

        // Survey Participation Chart
        new Chart(document.getElementById('surveyParticipationChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($surveyStats)) ?>,
                datasets: [{
                    label: 'Responses',
                    data: <?= json_encode(array_values($surveyStats)) ?>,
                    backgroundColor: '#3b82f6'
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Feedback Ratings Chart
        new Chart(document.getElementById('feedbackRatingsChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($feedbackRatings)) ?>,
                datasets: [{
                    label: 'Feedback Ratings',
                    data: <?= json_encode(array_values($feedbackRatings)) ?>,
                    backgroundColor: ['#3b82f6', '#f59e42', '#f1c40f', '#27ae60', '#e74c3c']
                }]
            },
            options: { responsive: true }
        });

        // Support Ticket Status Chart
        new Chart(document.getElementById('ticketStatusChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($ticketStatus)) ?>,
                datasets: [{
                    label: 'Tickets',
                    data: <?= json_encode(array_values($ticketStatus)) ?>,
                    backgroundColor: ['#3b82f6', '#e74c3c', '#f1c40f', '#27ae60']
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>
