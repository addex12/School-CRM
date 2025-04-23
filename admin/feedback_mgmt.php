<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Feedback Management";

// Fetch all users for dropdown
$users = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

// Fetch all active feedback subjects for dropdown
$subjects = $pdo->query("SELECT subject FROM feedback_subjects WHERE status = 'active' ORDER BY subject")->fetchAll(PDO::FETCH_COLUMN);

// Fetch all feedback
$stmt = $pdo->query("SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
$feedbackList = $stmt->fetchAll();

// Prepare ratings data for chart
$ratingsData = [1=>0,2=>0,3=>0,4=>0,5=>0];
foreach ($feedbackList as $feedback) {
    $r = (int)$feedback['rating'];
    if (isset($ratingsData[$r])) $ratingsData[$r]++;
}
$ratingsJson = json_encode(array_values($ratingsData));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_feedback'])) {
            // Add new feedback
            $user_id = intval($_POST['user_id']);
            $subject = trim($_POST['subject']);
            $message = trim($_POST['message']);
            $rating = intval($_POST['rating']);

            if (empty($subject) || empty($message)) {
                throw new Exception("Subject and message are required.");
            }

            // Check if user exists
            $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $userCheck->execute([$user_id]);
            if (!$userCheck->fetch()) {
                throw new Exception("User ID does not exist.");
            }

            $stmt = $pdo->prepare("INSERT INTO feedback (user_id, subject, message, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $subject, $message, $rating]);

            $_SESSION['success'] = "Feedback added successfully!";
        } elseif (isset($_POST['edit_feedback'])) {
            // Edit feedback
            $feedback_id = intval($_POST['feedback_id']);
            $subject = trim($_POST['subject']);
            $message = trim($_POST['message']);
            $rating = intval($_POST['rating']);

            if (empty($subject) || empty($message)) {
                throw new Exception("Subject and message are required.");
            }

            $stmt = $pdo->prepare("UPDATE feedback SET subject = ?, message = ?, rating = ? WHERE id = ?");
            $stmt->execute([$subject, $message, $rating, $feedback_id]);

            $_SESSION['success'] = "Feedback updated successfully!";
        } elseif (isset($_POST['admin_reply'])) {
            // Admin reply to feedback
            $feedback_id = intval($_POST['feedback_id']);
            $reply = trim($_POST['reply']);
            $stmt = $pdo->prepare("UPDATE feedback SET admin_reply = ? WHERE id = ?");
            $stmt->execute([$reply, $feedback_id]);
            $_SESSION['success'] = "Reply added successfully!";
        } elseif (isset($_POST['delete_feedback'])) {
            // Delete feedback
            $feedback_id = intval($_POST['feedback_id']);
            $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
            $stmt->execute([$feedback_id]);

            $_SESSION['success'] = "Feedback deleted successfully!";
        }
        header("Location: feedback_mgmt.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/feedback.js" defer></script>
    <style>
        body { background: #f5f7fa; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        .admin-main { margin-left: 260px; padding: 2rem 2.5rem; }
        .dashboard-section, .form-section, .table-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            margin-bottom: 2rem;
            padding: 2rem 2.5rem;
        }
        .form-section h2, .table-section h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.2rem;
        }
        .form-group label {
            font-weight: 600;
            color: #215967;
            margin-bottom: 6px;
            display: block;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .star-rating {
            direction: rtl;
            unicode-bidi: bidi-override;
            display: inline-block;
        }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
            font-size: 2em;
        }
        .star-rating input[type="radio"]:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: orange;
        }
        .erpnext-btn, .btn, .btn-primary, .btn-secondary, .btn-danger, .btn-info {
            display: inline-block;
            padding: 10px 22px;
            font-size: 15px;
            border-radius: 4px;
            border: none;
            background: #f5f7fa;
            color: #215967;
            font-weight: 600;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 1px 2px rgba(44,62,80,0.04);
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary, .erpnext-btn.btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover, .erpnext-btn.btn-primary:hover { background: #2563eb; }
        .btn-secondary, .erpnext-btn.btn-secondary { background: #eaeaea; color: #666; }
        .btn-secondary:hover, .erpnext-btn.btn-secondary:hover { background: #e2efda; color: #215967; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-info { background: #38bdf8; color: #fff; }
        .btn-sm { padding: 6px 14px; font-size: 13px; }
        .alert { background: #e2efda; color: #215967; border-radius: 8px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; border: 1px solid #b7e4c7; font-size: 1.05rem; }
        .alert-danger { background: #fee2e2; color: #e74c3c; border: 1px solid #fca5a5; }
        .alert-success { background: #dcfce7; color: #27ae60; border: 1px solid #b7e4c7; }
        .table-section .form-control, .form-section .form-control { margin-bottom: 0.7rem; }
        .table-section input[type="text"] { min-width: 120px; }
        .table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .table th, .table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
        }
        .table tr:hover { background: #f4f8fb; }
        .feedback-rating span { font-size: 1.2em; }
        .admin-reply { background: #f5f7fa; border-radius: 5px; padding: 5px 10px; margin-bottom: 5px; color: #215967; }
        #feedback-search { margin-bottom: 1.2rem; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 5px; background: #f9fafb; font-size: 1rem; width: 100%; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        @media (max-width: 900px) {
            .form-section, .table-section { padding: 12px !important; margin: 10px 0 !important; }
        }
        @media (max-width: 600px) {
            .form-section, .table-section { padding: 8px !important; margin: 6px 0 !important; }
            .form-actions { flex-direction: column !important; gap: 10px !important; }
            .admin-header { flex-direction: column !important; align-items: flex-start !important; gap: 8px !important; }
            .page-title, h1, h2 { font-size: 1.2rem !important; }
            .table-responsive, .table { display: block; width: 100%; overflow-x: auto; }
            th, td { white-space: nowrap; font-size: 0.95em; }
            .modal-content { width: 95%; }
        }
    </style>
</head>
<body>
    <!-- Floating global status message -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div id="statusMsg" class="alert alert-success text-center" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;min-width:260px;max-width:420px;padding:10px 24px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:6px;">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (!empty($_SESSION['error'])): ?>
        <div id="statusMsg" class="alert alert-danger text-center" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;min-width:260px;max-width:420px;padding:10px 24px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:6px;">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <script>
    // Auto-hide status message after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        var msg = document.getElementById('statusMsg');
        if (msg) setTimeout(function() { msg.style.display = 'none'; }, 3000);
    });
    </script>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1 style="color:#215967;font-weight:700;"><i class="fas fa-comments"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <!-- Add Feedback Section -->
                <section class="form-section">
                    <h2>Add Feedback</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">Select user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">ID <?= $user['id'] ?> - <?= htmlspecialchars($user['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select name="subject" id="subject" class="form-control" required>
                                <option value="">Select subject...</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea name="message" id="message" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="rating">Rating</label>
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                    <label for="star<?= $i ?>">&#9733;</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <button type="submit" name="add_feedback" class="erpnext-btn btn-primary"><i class="fas fa-plus"></i> Add Feedback</button>
                    </form>
                </section>

                <!-- Feedback List Section -->
                <section class="table-section">
                    <h2>Feedback List</h2>
                    <div style="max-width:500px;margin-bottom:24px;">
                        <canvas id="feedbackChart" height="180"></canvas>
                    </div>
                    <input type="text" id="feedback-search" placeholder="Search feedback..." class="form-control">
                    <?php if (count($feedbackList) > 0): ?>
                        <table class="table" id="feedbackTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Rating</th>
                                    <th>Admin Reply</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="feedbackTbody">
                                <?php foreach ($feedbackList as $feedback): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($feedback['id']) ?></td>
                                        <td><?= htmlspecialchars($feedback['username'] ?? 'Anonymous') ?></td>
                                        <td><?= htmlspecialchars($feedback['subject']) ?></td>
                                        <td><?= htmlspecialchars($feedback['message']) ?></td>
                                        <td class="feedback-rating">
                                            <?php
                                            $full = intval($feedback['rating']);
                                            $empty = 5 - $full;
                                            for ($i=0; $i<$full; $i++) echo '<span style="color:gold;font-size:1.2em">&#9733;</span>';
                                            for ($i=0; $i<$empty; $i++) echo '<span style="color:#ccc;font-size:1.2em">&#9733;</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($feedback['admin_reply'])): ?>
                                                <div class="admin-reply"><strong>Admin:</strong> <?= htmlspecialchars($feedback['admin_reply']) ?></div>
                                            <?php endif; ?>
                                            <form method="POST" style="margin-top:5px;">
                                                <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                <input type="text" name="reply" placeholder="Add reply..." class="form-control" required>
                                                <button type="submit" name="admin_reply" class="erpnext-btn btn-sm btn-info" style="margin-top:2px;">Reply</button>
                                            </form>
                                        </td>
                                        <td><?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                <button type="submit" name="delete_feedback" class="erpnext-btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this feedback?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No feedback found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Feedback</h2>
            <form method="POST">
                <input type="hidden" name="feedback_id" id="editFeedbackId">
                <div class="form-group">
                    <label for="editSubject">Subject</label>
                    <input type="text" name="subject" id="editSubject" required>
                </div>
                <div class="form-group">
                    <label for="editMessage">Message</label>
                    <textarea name="message" id="editMessage" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editRating">Rating</label>
                    <input type="number" name="rating" id="editRating" min="1" max="5" required>
                </div>
                <button type="submit" name="edit_feedback" class="erpnext-btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart.js feedback ratings bar chart
        const ratingsData = <?= $ratingsJson ?>;
        const ctx = document.getElementById('feedbackChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Number of Feedbacks',
                    data: ratingsData,
                    backgroundColor: [
                        '#ff4d4d', '#ff9933', '#ffe066', '#a3e635', '#34d399'
                    ],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Feedback Ratings Distribution' }
                },
                scales: {
                    y: { beginAtZero: true, precision: 0 }
                }
            }
        });

        // Feedback search (real-time)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('feedback-search');
            const tbody = document.getElementById('feedbackTbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            searchInput.addEventListener('input', function() {
                const val = searchInput.value.toLowerCase();
                rows.forEach(function(row) {
                    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
        });

        function editFeedback(id, subject, message, rating) {
            document.getElementById('editFeedbackId').value = id;
            document.getElementById('editSubject').value = subject;
            document.getElementById('editMessage').value = message;
            document.getElementById('editRating').value = rating;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>