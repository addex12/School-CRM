<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

// Handle all admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support Ticket Actions
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'update_ticket_status':
                    $stmt = $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
                    $stmt->execute([$_POST['status'], $_POST['ticket_id']]);
                    $_SESSION['success'] = "Ticket status updated";
                    break;
                    
                case 'add_ticket_reply':
                    $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['ticket_id'], $_SESSION['user_id'], $_POST['message']]);
                    $_SESSION['success'] = "Reply added successfully";
                    break;
                    
                case 'delete_ticket':
                    $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
                    $stmt->execute([$_POST['ticket_id']]);
                    $_SESSION['success'] = "Ticket deleted";
                    break;
                    
                case 'close_chat':
                    $stmt = $pdo->prepare("UPDATE chats SET status = 'closed' WHERE id = ?");
                    $stmt->execute([$_POST['chat_id']]);
                    $_SESSION['success'] = "Chat closed";
                    break;
                    
                case 'send_chat_message':
                    $stmt = $pdo->prepare("INSERT INTO chat_messages (chat_id, user_id, message) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['chat_id'], $_SESSION['user_id'], $_POST['message']]);
                    $_SESSION['success'] = "Message sent";
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        header("Location: dashboard.php");
        exit();
    }
}

// Get dashboard statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_surveys' => $pdo->query("SELECT COUNT(*) FROM surveys WHERE is_active = 1 AND starts_at <= NOW() AND ends_at >= NOW()")->fetchColumn(),
    'total_responses' => $pdo->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn(),
    'open_tickets' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn(),
    'active_chats' => $pdo->query("SELECT COUNT(*) FROM chats WHERE status = 'open'")->fetchColumn(),
    'new_feedback' => $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn()
];

// Get support tickets
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';

$ticket_query = "SELECT t.*, u.username, u.email FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id";
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
    $ticket_query .= " WHERE " . implode(" AND ", $where);
}

$ticket_query .= " ORDER BY 
    CASE 
        WHEN t.priority = 'critical' THEN 1
        WHEN t.priority = 'high' THEN 2 
        WHEN t.priority = 'medium' THEN 3
        ELSE 4
    END, t.created_at DESC";

$tickets = $pdo->prepare($ticket_query);
$tickets->execute($params);
$tickets = $tickets->fetchAll();

// Get ticket replies
$replies = [];
if (!empty($tickets)) {
    $ticket_ids = array_column($tickets, 'id');
    $placeholders = implode(',', array_fill(0, count($ticket_ids), '?'));
    $reply_stmt = $pdo->prepare("SELECT r.*, u.username FROM ticket_replies r JOIN users u ON r.user_id = u.id WHERE r.ticket_id IN ($placeholders) ORDER BY r.created_at");
    $reply_stmt->execute($ticket_ids);
    $all_replies = $reply_stmt->fetchAll();
    
    foreach ($all_replies as $reply) {
        $replies[$reply['ticket_id']][] = $reply;
    }
}

// Get active chats
$chats = $pdo->query("SELECT c.*, u.username FROM chats c JOIN users u ON c.user_id = u.id WHERE c.status = 'open' ORDER BY c.last_activity DESC LIMIT 5")->fetchAll();

// Get recent feedback
$feedback = $pdo->query("SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 5")->fetchAll();

// Get user activity
$activity = $pdo->query("
    SELECT 'survey' as type, s.title as description, u.username, r.submitted_at as date 
    FROM survey_responses r
    JOIN surveys s ON r.survey_id = s.id
    JOIN users u ON r.user_id = u.id
    UNION ALL
    SELECT 'ticket' as type, CONCAT('Ticket #', t.ticket_number) as description, u.username, t.created_at as date
    FROM support_tickets t
    JOIN users u ON t.user_id = u.id
    UNION ALL
    SELECT 'feedback' as type, 'Submitted feedback' as description, u.username, f.created_at as date
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    ORDER BY date DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-main">
            <h1>Admin Dashboard</h1>
            
            <!-- Display success and error messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <!-- Dashboard Widgets -->
            <div class="widget-grid">
                <?php foreach ($stats as $key => $value): ?>
                    <div class="dashboard-widget widget-stat">
                        <h3><?php echo ucwords(str_replace('_', ' ', $key)); ?></h3>
                        <p><?php echo $value; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Support Tickets Section -->
            <div class="dashboard-widget ticket-container">
                <div class="ticket-header">
                    <h2>Support Tickets</h2>
                    <div class="ticket-filters">
                        <!-- Filters for tickets -->
                        <select id="status-filter">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <select id="priority-filter">
                            <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priorities</option>
                            <option value="critical" <?php echo $priority_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                        <button id="apply-filters" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
                
                <!-- Display tickets -->
                <?php if (empty($tickets)): ?>
                    <p>No tickets found matching your criteria.</p>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-card">
                            <!-- Ticket details -->
                            <div class="ticket-header">
                                <div>
                                    <strong>#<?php echo $ticket['ticket_number']; ?></strong> - <?php echo htmlspecialchars($ticket['subject']); ?>
                                </div>
                                <div>
                                    <span class="ticket-priority priority-<?php echo $ticket['priority']; ?>">
                                        <?php echo $ticket['priority']; ?>
                                    </span>
                                    <span class="ticket-status status-<?php echo $ticket['status']; ?>">
                                        <?php echo $ticket['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ticket-body">
                                <!-- Ticket meta and actions -->
                                <div class="ticket-meta">
                                    <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($ticket['username'] ?? 'Guest'); ?></span>
                                    <span><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($ticket['email']); ?></span>
                                    <span><i class="bi bi-clock"></i> <?php echo date('M j, Y g:i a', strtotime($ticket['created_at'])); ?></span>
                                </div>
                                
                                <p><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                                
                                <?php if ($ticket['attachment']): ?>
                                    <p>
                                        <a href="../<?php echo htmlspecialchars($ticket['attachment']); ?>" target="_blank">
                                            <i class="bi bi-paperclip"></i> Download Attachment
                                        </a>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="ticket-actions">
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="action" value="update_ticket_status">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                            <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </form>
                                    
                                    <button class="btn btn-secondary toggle-replies" data-ticket="<?php echo $ticket['id']; ?>">
                                        <i class="bi bi-chat-left-text"></i> Replies (<?php echo count($replies[$ticket['id']] ?? []); ?>)
                                    </button>
                                    
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="action" value="delete_ticket">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="ticket-replies" id="replies-<?php echo $ticket['id']; ?>" style="display: none;">
                                    <h4>Conversation</h4>
                                    
                                    <?php if (!empty($replies[$ticket['id']])): ?>
                                        <?php foreach ($replies[$ticket['id']] as $reply): ?>
                                            <div class="reply">
                                                <div class="reply-header">
                                                    <span><strong><?php echo htmlspecialchars($reply['username']); ?></strong></span>
                                                    <span><?php echo date('M j, Y g:i a', strtotime($reply['created_at'])); ?></span>
                                                </div>
                                                <p><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (empty($replies[$ticket['id']])): ?>
                                        <p>No replies yet.</p>
                                    <?php endif; ?>
                                    
                                    <form method="post" class="reply-form">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="action" value="add_ticket_reply">
                                        <textarea name="message" rows="3" placeholder="Type your reply..." required></textarea>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send"></i> Send Reply
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Survey Builder Section -->
            <div class="dashboard-widget survey-builder">
                <h2>Create a Survey</h2>
                <form id="survey-form" method="post" action="save_survey.php">
                    <div id="survey-fields">
                        <div class="survey-field">
                            <label for="question-1">Question 1</label>
                            <input type="text" name="questions[]" id="question-1" placeholder="Enter your question" required>
                            <select name="field_types[]" class="field-type-selector" required>
                                <option value="text">Text Input</option>
                                <option value="textarea">Text Area</option>
                                <option value="radio">Radio Buttons</option>
                                <option value="checkbox">Checkboxes</option>
                                <option value="dropdown">Dropdown</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="rating">Rating</option>
                                <option value="file">File Upload</option>
                            </select>
                            <div class="field-options" style="display: none;">
                                <label>Options (comma-separated):</label>
                                <input type="text" name="options[]" placeholder="Option1, Option2, Option3">
                            </div>
                            <button type="button" class="remove-field btn btn-danger">Remove</button>
                        </div>
                    </div>
                    <button type="button" id="add-field" class="btn btn-primary">Add Question</button>
                    <button type="submit" class="btn btn-success">Save Survey</button>
                </form>
            </div>
            
            <!-- Chats and Feedback Section -->
            <div class="dashboard-grid">
                <!-- Active Chats -->
                <div class="dashboard-widget chat-container">
                    <h2>Active Chats</h2>
                    
                    <?php if (empty($chats)): ?>
                        <p>No active chats.</p>
                    <?php else: ?>
                        <?php foreach ($chats as $chat): ?>
                            <div class="chat-item">
                                <div style="display: flex; justify-content: space-between;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($chat['username']); ?></strong>
                                        <span class="text-muted"><?php echo date('M j, g:i a', strtotime($chat['last_activity'])); ?></span>
                                    </div>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="chat_id" value="<?php echo $chat['id']; ?>">
                                        <input type="hidden" name="action" value="close_chat">
                                        <button type="submit" class="btn btn-sm btn-danger">Close</button>
                                    </form>
                                </div>
                                <p><?php echo htmlspecialchars($chat['subject']); ?></p>
                                <a href="chat.php?id=<?php echo $chat['id']; ?>" class="btn btn-primary">View Chat</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Feedback -->
                <div class="dashboard-widget feedback-container">
                    <h2>Recent Feedback</h2>
                    
                    <?php if (empty($feedback)): ?>
                        <p>No feedback received.</p>
                    <?php else: ?>
                        <?php foreach ($feedback as $item): ?>
                            <div class="feedback-item">
                                <div style="display: flex; justify-content: space-between;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['username'] ?? 'Anonymous'); ?></strong>
                                        <span class="text-muted"><?php echo date('M j, g:i a', strtotime($item['created_at'])); ?></span>
                                    </div>
                                    <span class="badge"><?php echo ucfirst($item['status']); ?></span>
                                </div>
                                <p><?php echo htmlspecialchars($item['message']); ?></p>
                                <div>
                                    <span class="rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo $i <= $item['rating'] ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Activity Log -->
            <div class="dashboard-widget activity-log">
                <h2>Recent Activity</h2>
                
                <?php if (empty($activity)): ?>
                    <p>No recent activity.</p>
                <?php else: ?>
                    <?php foreach ($activity as $item): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php switch ($item['type']):
                                    case 'survey': ?>
                                        <i class="bi bi-clipboard-check"></i>
                                        <?php break; ?>
                                    case 'ticket': ?>
                                        <i class="bi bi-ticket-detailed"></i>
                                        <?php break; ?>
                                    case 'feedback': ?>
                                        <i class="bi bi-chat-square-text"></i>
                                        <?php break; ?>
                                <?php endswitch; ?>
                            </div>
                            <div>
                                <p>
                                    <strong><?php echo htmlspecialchars($item['username']); ?></strong> 
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </p>
                                <small class="text-muted"><?php echo date('M j, g:i a', strtotime($item['date'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Toggle ticket replies
        document.querySelectorAll('.toggle-replies').forEach(button => {
            button.addEventListener('click', function() {
                const ticketId = this.getAttribute('data-ticket');
                const repliesDiv = document.getElementById(`replies-${ticketId}`);
                repliesDiv.style.display = repliesDiv.style.display === 'none' ? 'block' : 'none';
            });
        });
        
        // Apply ticket filters
        document.getElementById('apply-filters')?.addEventListener('click', function() {
            const status = document.getElementById('status-filter').value;
            const priority = document.getElementById('priority-filter').value;
            
            let url = 'dashboard.php?';
            if (status !== 'all') url += `status=${status}&`;
            if (priority !== 'all') url += `priority=${priority}`;
            
            window.location.href = url;
        });
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Response trends chart
            const ctx = document.createElement('canvas');
            document.querySelector('.widget-grid').appendChild(ctx);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Survey Responses',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: '#3498db',
                        tension: 0.1
                    }]
                }
            });
        });

        // Handle adding new survey fields
        document.getElementById('add-field').addEventListener('click', function () {
            const fieldCount = document.querySelectorAll('.survey-field').length + 1;
            const fieldHTML = `
                <div class="survey-field">
                    <label for="question-${fieldCount}">Question ${fieldCount}</label>
                    <input type="text" name="questions[]" id="question-${fieldCount}" placeholder="Enter your question" required>
                    <select name="field_types[]" class="field-type-selector" required>
                        <option value="text">Text Input</option>
                        <option value="textarea">Text Area</option>
                        <option value="radio">Radio Buttons</option>
                        <option value="checkbox">Checkboxes</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="rating">Rating</option>
                        <option value="file">File Upload</option>
                    </select>
                    <div class="field-options" style="display: none;">
                        <label>Options (comma-separated):</label>
                        <input type="text" name="options[]" placeholder="Option1, Option2, Option3">
                    </div>
                    <button type="button" class="remove-field btn btn-danger">Remove</button>
                </div>`;
            document.getElementById('survey-fields').insertAdjacentHTML('beforeend', fieldHTML);
        });

        // Handle removing survey fields
        document.getElementById('survey-fields').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-field')) {
                e.target.closest('.survey-field').remove();
            }
        });

        // Show options input for applicable field types
        document.getElementById('survey-fields').addEventListener('change', function (e) {
            if (e.target.classList.contains('field-type-selector')) {
                const optionsDiv = e.target.closest('.survey-field').querySelector('.field-options');
                if (['radio', 'checkbox', 'dropdown'].includes(e.target.value)) {
                    optionsDiv.style.display = 'block';
                } else {
                    optionsDiv.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>