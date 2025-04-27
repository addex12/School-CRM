<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/css/brands.min.css">
    <link rel="stylesheet" href="../assets/css/solid.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/dashboard.js" defer></script>
    <script src="../assets/js/jquery.min.js"></script>
    <style>
        /* Frappe/Jinja style buttons */
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            text-decoration: none;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 0.25rem;
            transition: all 0.2s ease-in-out;
        }
        
        .btn-primary {
            color: #fff;
            background-color: #2490ef;
            border-color: #2490ef;
        }
        
        .btn-primary:hover {
            background-color: #1a7fdb;
            border-color: #1a7fdb;
        }
        
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.2rem;
        }

        /* Widget Grid */
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin: 1rem 0;
        }

        .dashboard-widget {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
            position: relative;
            border: 1px solid #e5e7eb;
        }

        .dashboard-widget:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-widget i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #2490ef;
        }

        .dashboard-widget h3 {
            font-size: 1.75rem;
            margin: 0.5rem 0;
            color: #2e2e2e;
        }

        .dashboard-widget p {
            margin: 0;
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Responsive Layout */
        .admin-main {
            padding: 1.5rem;
            margin-left: 250px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .content {
            max-width: 100%;
            overflow-x: hidden;
        }

        .dashboard-section {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: #fff;
            border-radius: 8px;
            text-decoration: none;
            color: #2e2e2e;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .quick-link:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .quick-link i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #2490ef;
        }

        .quick-link span {
            font-size: 0.875rem;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        table th {
            background-color: #f8f9fa;
            font-weight: 500;
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .widget-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 1rem;
            }
            
            .quick-links {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .widget-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .quick-links {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 576px) {
            .widget-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .dashboard-widget {
                padding: 1rem;
            }
            
            .dashboard-widget i {
                font-size: 1.5rem;
            }
            
            .dashboard-widget h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php
        // Make unreadMessagesCount available to sidebar
        $ADMIN_UNREAD_MESSAGES = $unreadMessagesCount;
        include __DIR__ . '/includes/admin_sidebar.php';
        ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 style="margin:0;"><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($unreadMessagesCount > 0): ?>
                    <a href="messages.php" class="btn btn-primary" style="position:relative;">
                        <i class="fas fa-envelope"></i>
                        <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:#fff;border-radius:50%;padding:2px 7px;font-size:0.75em;font-weight:600;">
                            <?= $unreadMessagesCount ?>
                        </span>
                        New Messages
                    </a>
                <?php endif; ?>
            </header>
            <div class="content">
                <!-- Quick Links Section -->
                <div class="quick-links">
                    <a href="users.php" class="quick-link"><i class="fas fa-users"></i><span>Manage Users</span></a>
                    <a href="surveys.php" class="quick-link"><i class="fas fa-poll"></i><span>Surveys</span></a>
                    <a href="feedback.php" class="quick-link"><i class="fas fa-comments"></i><span>Feedback</span></a>
                    <a href="support_tickets.php" class="quick-link"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
                    <a href="events.php" class="quick-link"><i class="fas fa-calendar-plus"></i><span>Add Event</span></a>
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

                <!-- Rest of the content remains the same -->
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
                        <li>Database Name: <?= htmlspecialchars(DB_NAME ?? 'N/A') ?></li>
                        <li>Database User: <?= htmlspecialchars(DB_USER ?? 'N/A') ?></li>
                        <li>Database Version: <?= htmlspecialchars($pdo->getAttribute(PDO::ATTR_SERVER_VERSION) ?? 'N/A') ?></li>
                        <li>Database Table Count: <?= htmlspecialchars($pdo->query('SHOW TABLES')->rowCount()) ?></li>
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

                <!-- User Activity Logs Section -->
                <div class="dashboard-section">
                    <h2>User Activity Logs</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activityLogs)): ?>
                                    <?php foreach ($activityLogs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                            <td><?= htmlspecialchars($log['action']) ?></td>
                                            <td><?= htmlspecialchars($log['details']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No activity logs found.</td>
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
        // Survey Participation Chart
        (function() {
            const ctx = document.getElementById('surveyParticipationChart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_keys($surveyStats)) ?>,
                        datasets: [{
                            label: 'Responses',
                            data: <?= json_encode(array_values($surveyStats)) ?>,
                            backgroundColor: '#3b82f6'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true },
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        })();

        // Feedback Ratings Chart
        (function() {
            const ctx = document.getElementById('feedbackRatingsChart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx.getContext('2d'), {
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
            }
        })();

        // Support Ticket Status Chart
        (function() {
            const ctx = document.getElementById('ticketStatusChart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx.getContext('2d'), {
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
            }
        })();
    </script>
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>