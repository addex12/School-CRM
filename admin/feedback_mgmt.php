<?php
require_once '../includes/auth.php';
requireAdmin();

// Fetch feedback with user info
$stmt = $pdo->query("
    SELECT f.*, u.username 
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    ORDER BY f.created_at DESC
");
$feedbackList = $stmt->fetchAll();

// Get rating distribution
$ratings = array_column($feedbackList, 'rating');
$ratingCounts = array_count_values($ratings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <div class="content">
                <div class="admin-content">
                    <h2><i class="fas fa-comment-dots"></i> Feedback Management</h2>
                    
                    <div class="content-section">
                        <div class="analytics-section">
                            <div class="rating-chart">
                                <canvas id="ratingChart"></canvas>
                            </div>
                            
                            <div class="quick-stats">
                                <div class="stat-card total">
                                    <h3>Total Feedback</h3>
                                    <p><?= count($feedbackList) ?></p>
                                </div>
                                <div class="stat-card average">
                                    <h3>Average Rating</h3>
                                    <p><?= round(array_sum($ratings)/count($ratings), 1) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="feedback-list">
                            <?php for($i=5; $i>=1; $i--): ?>
                            <div class="rating-group rating-<?= $i ?>">
                                <h3><?= str_repeat('★', $i) ?> Ratings</h3>
                                <?php foreach($feedbackList as $feedback): ?>
                                <?php if($feedback['rating'] == $i): ?>
                                <div class="feedback-item">
                                    <div class="feedback-header">
                                        <span class="user"><?= htmlspecialchars($feedback['username']) ?></span>
                                        <span class="date"><?= date('M j, Y', strtotime($feedback['created_at'])) ?></span>
                                        <div class="rating"><?= str_repeat('★', $feedback['rating']) ?></div>
                                    </div>
                                    <div class="feedback-body">
                                        <h4><?= htmlspecialchars($feedback['subject']) ?></h4>
                                        <p><?= htmlspecialchars($feedback['message']) ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Rating distribution chart
    const ctx = document.getElementById('ratingChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['★', '★★', '★★★', '★★★★', '★★★★★'],
            datasets: [{
                label: 'Rating Distribution',
                data: [
                    <?= $ratingCounts[1] ?? 0 ?>,
                    <?= $ratingCounts[2] ?? 0 ?>,
                    <?= $ratingCounts[3] ?? 0 ?>,
                    <?= $ratingCounts[4] ?? 0 ?>,
                    <?= $ratingCounts[5] ?? 0 ?>
                ],
                backgroundColor: [
                    '#ff6384',
                    '#ff9f40',
                    '#ffcd56',
                    '#4bc0c0',
                    '#36a2eb'
                ]
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
</body>
</html>