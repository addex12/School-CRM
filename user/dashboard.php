<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php'; // Include config to initialize $pdo

// Handle support ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['ticket_id'])) {
        $ticket_id = $_POST['ticket_id'];
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'update_status':
                    $new_status = $_POST['status'];
                    $stmt = $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $ticket_id]);
                    $_SESSION['success'] = "Ticket status updated successfully";
                    break;
                    
                case 'add_reply':
                    $reply = $_POST['reply'];
                    $admin_id = $_SESSION['user_id'];
                    $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$ticket_id, $admin_id, $reply]);
                    $_SESSION['success'] = "Reply added successfully";
                    break;
                    
                case 'delete_ticket':
                    // First get attachment path if exists
                    $stmt = $pdo->prepare("SELECT attachment FROM support_tickets WHERE id = ?");
                    $stmt->execute([$ticket_id]);
                    $attachment = $stmt->fetchColumn();
                    
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
                    $stmt->execute([$ticket_id]);
                    
                    // Delete attachment file if exists
                    if ($attachment && file_exists("../$attachment")) {
                        unlink("../$attachment");
                    }
                    
                    $_SESSION['success'] = "Ticket deleted successfully";
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error processing request: " . $e->getMessage();
        }
        
        header("Location: dashboard.php");
        exit();
    }
}

// Get statistics
$totalSurveys = $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
$activeSurveys = $pdo->query("SELECT COUNT(*) FROM surveys WHERE is_active = TRUE AND starts_at <= NOW() AND ends_at >= NOW()")->fetchColumn();
$totalResponses = $pdo->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Support ticket statistics
$totalTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
$closedTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'closed'")->fetchColumn();
$highPriorityTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE priority = 'high' OR priority = 'critical'")->fetchColumn();

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

// Get support tickets with filtering
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';

$query = "SELECT t.*, u.username, u.email 
          FROM support_tickets t
          LEFT JOIN users u ON t.user_id = u.id";

$where = [];
$params = [];

if ($status_filter !== 'all') {
    $where[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter !== 'all') {
    $where[] = "t.priority = ?";
    $params[] = $priority_filter;
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY 
            CASE 
                WHEN t.priority = 'critical' THEN 1
                WHEN t.priority = 'high' THEN 2
                WHEN t.priority = 'medium' THEN 3
                ELSE 4
            END, t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$supportTickets = $stmt->fetchAll();

// Get replies for tickets
$ticketIds = array_column($supportTickets, 'id');
$replies = [];
if (!empty($ticketIds)) {
    $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
    $stmt = $pdo->prepare("
        SELECT r.*, u.username 
        FROM ticket_replies r
        JOIN users u ON r.user_id = u.id
        WHERE r.ticket_id IN ($placeholders)
        ORDER BY r.created_at
    ");
    $stmt->execute($ticketIds);
    $allReplies = $stmt->fetchAll();
    
    // Organize replies by ticket ID
    foreach ($allReplies as $reply) {
        $replies[$reply['ticket_id']][] = $reply;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Support Ticket Styles */
        .ticket-container {
            margin-top: 30px;
        }
        
        .ticket-filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .ticket-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .ticket-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        
        .ticket-priority {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .priority-critical {
            background-color: #dc3545;
            color: white;
        }
        
        .priority-high {
            background-color: #fd7e14;
            color: white;
        }
        
        .priority-medium {
            background-color: #ffc107;
            color: #212529;
        }
        
        .priority-low {
            background-color: #28a745;
            color: white;
        }
        
        .ticket-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-open {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .ticket-body {
            padding: 15px;
        }
        
        .ticket-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .ticket-message {
            margin-bottom: 15px;
            white-space: pre-wrap;
        }
        
        .ticket-attachment {
            margin-top: 10px;
        }
        
        .ticket-attachment a {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #0d6efd;
            text-decoration: none;
        }
        
        .ticket-replies {
            border-top: 1px solid #eee;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .reply {
            background: white;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 5px;
            color: #6c757d;
        }
        
        .reply-message {
            white-space: pre-wrap;
        }
        
        .ticket-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .reply-form {
            margin-top: 15px;
        }
        
        .reply-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php require_once 'includes/header.php'; ?>

        <h1>Admin Dashboard</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
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
            <div class="stat-card">
                <h3>Support Tickets</h3>
                <p><?php echo $totalTickets; ?></p>
                <small><?php echo $openTickets; ?> open, <?php echo $highPriorityTickets; ?> high/critical</small>
            </div>
        </div>
        
        <!-- Support Tickets Section -->
        <div class="ticket-container">
            <h2>Support Tickets</h2>
            
            <div class="ticket-filters">
                <div>
                    <label>Status:</label>
                    <select id="status-filter" class="form-control-sm">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                
                <div>
                    <label>Priority:</label>
                    <select id="priority-filter" class="form-control-sm">
                        <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priorities</option>
                        <option value="critical" <?php echo $priority_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                
                <button id="apply-filters" class="btn btn-primary btn-sm">Apply Filters</button>
            </div>
            
            <?php if (empty($supportTickets)): ?>
                <div class="alert alert-info">
                    No tickets found matching your criteria.
                </div>
            <?php else: ?>
                <?php foreach ($supportTickets as $ticket): ?>
                    <div class="ticket-card" id="ticket-<?php echo $ticket['id']; ?>">
                        <div class="ticket-header">
                            <div>
                                <strong>#<?php echo $ticket['ticket_number']; ?></strong> - <?php echo htmlspecialchars($ticket['subject']); ?>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span class="ticket-priority priority-<?php echo $ticket['priority']; ?>">
                                    <?php echo $ticket['priority']; ?>
                                </span>
                                <span class="ticket-status status-<?php echo $ticket['status']; ?>">
                                    <?php echo $ticket['status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="ticket-body">
                            <div class="ticket-meta">
                                <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($ticket['username'] ?? 'Guest'); ?></span>
                                <span><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($ticket['email']); ?></span>
                                <span><i class="bi bi-clock"></i> <?php echo date('M j, Y g:i a', strtotime($ticket['created_at'])); ?></span>
                            </div>
                            
                            <div class="ticket-message">
                                <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                            </div>
                            
                            <?php if ($ticket['attachment']): ?>
                                <div class="ticket-attachment">
                                    <a href="../<?php echo htmlspecialchars($ticket['attachment']); ?>" target="_blank">
                                        <i class="bi bi-paperclip"></i> Download Attachment
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="ticket-actions">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <select name="status" class="form-control-sm" onchange="this.form.submit()">
                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </form>
                                
                                <button class="btn btn-sm btn-secondary" data-bs-toggle="collapse" data-bs-target="#replies-<?php echo $ticket['id']; ?>">
                                    <i class="bi bi-chat-left-text"></i> Replies (<?php echo count($replies[$ticket['id']] ?? []); ?>)
                                </button>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <input type="hidden" name="action" value="delete_ticket">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this ticket?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="ticket-replies collapse" id="replies-<?php echo $ticket['id']; ?>">
                            <h5>Conversation</h5>
                            
                            <?php if (!empty($replies[$ticket['id']])): ?>
                                <?php foreach ($replies[$ticket['id']] as $reply): ?>
                                    <div class="reply">
                                        <div class="reply-header">
                                            <span><strong><?php echo htmlspecialchars($reply['username']); ?></strong></span>
                                            <span><?php echo date('M j, Y g:i a', strtotime($reply['created_at'])); ?></span>
                                        </div>
                                        <div class="reply-message">
                                            <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">No replies yet</div>
                            <?php endif; ?>
                            
                            <div class="reply-form">
                                <form method="post">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <input type="hidden" name="action" value="add_reply">
                                    <textarea name="reply" rows="3" placeholder="Type your reply here..." required></textarea>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send"></i> Send Reply
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Existing dashboard content -->
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
                                    <td><?php echo htmlspecialchars($response['username']); ?></td>
                                    <td><?php echo htmlspecialchars($response['survey_title']); ?></td>
                                    <td><?php echo date('M j, Y g:i a', strtotime($response['submitted_at'])); ?></td>
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
    </div>

    <script>
        // Response trends chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('responseChart')?.getContext('2d');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [
                            <?php 
                            for ($i = 6; $i >= 0; $i--) {
                                $date = date('M j', strtotime("-$i days"));
                                echo "'$date',";
                            }
                            ?>
                        ],
                        datasets: [{
                            label: 'Survey Responses',
                            data: [
                                <?php
                                for ($i = 6; $i >= 0; $i--) {
                                    $date = date('Y-m-d', strtotime("-$i days"));
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM survey_responses WHERE DATE(submitted_at) = ?");
                                    $stmt->execute([$date]);
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
            }
            
            // Filter functionality
            document.getElementById('apply-filters')?.addEventListener('click', function() {
                const status = document.getElementById('status-filter').value;
                const priority = document.getElementById('priority-filter').value;
                
                let url = 'dashboard.php';
                const params = [];
                
                if (status !== 'all') {
                    params.push(`status=${status}`);
                }
                
                if (priority !== 'all') {
                    params.push(`priority=${priority}`);
                }
                
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                
                window.location.href = url;
            });
        });
    </script>
    
    <!-- Bootstrap JS for collapse functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>