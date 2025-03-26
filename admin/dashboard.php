<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/header.php';
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php'; // Include config to initialize $pdo

// Get statistics
$totalSurveys = $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
$activeSurveys = $pdo->query("SELECT COUNT(*) FROM surveys WHERE is_active = TRUE AND starts_at <= NOW() AND ends_at >= NOW()")->fetchColumn();
$totalResponses = $pdo->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Get recent surveys
$recentSurveys = $pdo->query("
    SELECT s.*, COUNT(r.id) as response_count 
    FROM surveys s
    LEFT JOIN survey_responses r ON s.id = r.survey_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT 5
")->fetchAll();

// Get recent responses
$recentResponses = $pdo->query("
    SELECT r.*, u.username, s.title as survey_title
    FROM survey_responses r
    JOIN users u ON r.user_id = u.id
    JOIN surveys s ON r.survey_id = s.id
    ORDER BY r.submitted_at DESC
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>      
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Surveys</h3>
                <p><?php echo $totalSurveys; ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Surveys</h3>
                <p><?php echo $activeSurveys; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Responses</h3>
                <p><?php echo $totalResponses; ?></p>
            </div>
            <div class="stat-card">
                <h3>Registered Users</h3>
                <p><?php echo $totalUsers; ?></p>
            </div>
        </div>
        
        <div class="dashboard-row">
            <div class="dashboard-col">
                <h2>Recent Surveys</h2>
                <?php if (count($recentSurveys) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Responses</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSurveys as $survey): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($survey['title']); ?></td>
                                    <td><?php echo $survey['response_count']; ?></td>
                                    <td>
                                        <?php if ($survey['is_active'] && $survey['starts_at'] <= date('Y-m-d H:i:s') && $survey['ends_at'] >= date('Y-m-d H:i:s')): ?>
                                            <span class="status-active">Active</span>
                                        <?php elseif (!$survey['is_active']): ?>
                                            <span class="status-inactive">Inactive</span>
                                        <?php else: ?>
                                            <span class="status-pending">Pending/Scheduled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="survey_preview.php?id=<?php echo $survey['id']; ?>">Preview</a>
                                        <a href="results.php?survey_id=<?php echo $survey['id']; ?>">Results</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No surveys found.</p>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-col">
                <h2>Recent Responses</h2>
                <?php if (count($recentResponses) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Survey</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentResponses as $response): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(string: $response['username']); ?></td>
                                    <td><?php echo htmlspecialchars(string: $response['survey_title']); ?></td>
                                    <td><?php echo date(format: 'M j, Y g:i a', timestamp: strtotime(datetime: $response['submitted_at'])); ?></td>
                                    <td>
                                        <a href="response_view.php?id=<?php echo $response['id']; ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No responses found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="chart-container">
            <h2>Response Trends</h2>
            <canvas id="responseChart"></canvas>
        </div>
    </div>

    <script>
        // Response trends chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('responseChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [
                        <?php 
                        // Generate labels for last 7 days
                        for ($i = 6; $i >= 0; $i--) {
                            $date = date(format: 'M j', timestamp: strtotime(datetime: "-$i days"));
                            echo "'$date',";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Survey Responses',
                        data: [
                            <?php
                            // Get response counts for last 7 days
                            for ($i = 6; $i >= 0; $i--) {
                                $date = date(format: 'Y-m-d', timestamp: strtotime(datetime: "-$i days"));
                                $stmt = $pdo->prepare(query: "SELECT COUNT(*) FROM survey_responses WHERE DATE(submitted_at) = ?");
                                $stmt->execute(params: [$date]);
                                $count = $stmt->fetchColumn();
                                echo "$count,";
                            }
                            ?>
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Responses'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>