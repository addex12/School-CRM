<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
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
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.07);
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, width 0.2s;
        }
        .adugna-card-header {
            font-size: 1.13em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 1em;
            letter-spacing: 0.01em;
        }
        .adugna-form-group {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.2em;
        }
        .adugna-form-group label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input,
        .adugna-form-group select,
        .adugna-form-group textarea {
            padding: 7px 10px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-btn-danger {
            background: #e74c3c;
            color: #fff;
            border: 1px solid #e74c3c;
        }
        .adugna-btn-danger:hover { background: #c82333; }
        .adugna-btn-info {
            background: #38bdf8;
            color: #fff;
            border: 1px solid #38bdf8;
        }
        .adugna-btn-info:hover { background: #2563eb; }
        .adugna-btn-sm { padding: 2px 7px; font-size: 0.93em; border-radius: 3px; }
        .adugna-alert {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
            text-align: center;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
        }
        .adugna-alert-success {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
        }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .adugna-table th, .adugna-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 0.97em;
        }
        .adugna-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
        }
        .adugna-table tr:hover { background: #f4f8fb; }
        .adugna-feedback-rating span { font-size: 1.2em; }
        .adugna-admin-reply { background: #f5f7fa; border-radius: 5px; padding: 5px 10px; margin-bottom: 5px; color: #215967; }
        .adugna-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .adugna-modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 8px; }
        .adugna-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .adugna-modal-close:hover, .adugna-modal-close:focus { color: #1976d2; text-decoration: none; cursor: pointer; }
        .adugna-star-rating {
            direction: rtl;
            unicode-bidi: bidi-override;
            display: inline-block;
        }
        .adugna-star-rating input[type="radio"] { display: none; }
        .adugna-star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
            font-size: 1.5em;
        }
        .adugna-star-rating input[type="radio"]:checked ~ label,
        .adugna-star-rating label:hover,
        .adugna-star-rating label:hover ~ label {
            color: orange;
        }
        @media (max-width: 900px) {
            .adugna-main-content, .adugna-card { padding: 1rem; }
        }
        @media (max-width: 600px) {
            .adugna-main-content, .adugna-card { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-header-title { font-size: 1.05em; }
            .adugna-modal-content { width: 95%; }
            .adugna-btn, .adugna-btn-primary { padding: 6px 10px; font-size: 0.95em; }
        }
    </style>
</head>
<body>
    <!-- Floating global status message -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div id="statusMsg" class="adugna-alert adugna-alert-success text-center" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;min-width:260px;max-width:420px;padding:10px 24px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:6px;">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (!empty($_SESSION['error'])): ?>
        <div id="statusMsg" class="adugna-alert adugna-alert-error text-center" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;min-width:260px;max-width:420px;padding:10px 24px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:6px;">
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
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-comments"></i> <?= htmlspecialchars($pageTitle) ?>
            </div>
            <!-- Add Feedback Section -->
            <div class="adugna-card">
                <div class="adugna-card-header"><i class="fas fa-plus"></i> Add Feedback</div>
                <form method="POST">
                    <div class="adugna-form-group">
                        <label for="user_id">User</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">Select user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>">ID <?= $user['id'] ?> - <?= htmlspecialchars($user['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adugna-form-group">
                        <label for="subject">Subject</label>
                        <select name="subject" id="subject" required>
                            <option value="">Select subject...</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adugna-form-group">
                        <label for="message">Message</label>
                        <textarea name="message" id="message" rows="4" required></textarea>
                    </div>
                    <div class="adugna-form-group">
                        <label for="rating">Rating</label>
                        <div class="adugna-star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                <label for="star<?= $i ?>">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_feedback" class="adugna-btn"><i class="fas fa-plus"></i> Add Feedback</button>
                </form>
            </div>
            <!-- Feedback List Section -->
            <div class="adugna-card">
                <div class="adugna-card-header"><i class="fas fa-list"></i> Feedback List</div>
                <div style="max-width:500px;margin-bottom:24px;">
                    <canvas id="feedbackChart" height="180"></canvas>
                </div>
                <input type="text" id="feedback-search" placeholder="Search feedback..." class="adugna-form-group" style="margin-bottom:1.2rem;">
                <?php if (count($feedbackList) > 0): ?>
                    <table class="adugna-table" id="feedbackTable">
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
                                    <td class="adugna-feedback-rating">
                                        <?php
                                        $full = intval($feedback['rating']);
                                        $empty = 5 - $full;
                                        for ($i=0; $i<$full; $i++) echo '<span style="color:gold;font-size:1.2em">&#9733;</span>';
                                        for ($i=0; $i<$empty; $i++) echo '<span style="color:#ccc;font-size:1.2em">&#9733;</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($feedback['admin_reply'])): ?>
                                            <div class="adugna-admin-reply"><strong>Admin:</strong> <?= htmlspecialchars($feedback['admin_reply']) ?></div>
                                        <?php endif; ?>
                                        <form method="POST" style="margin-top:5px;">
                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                            <input type="text" name="reply" placeholder="Add reply..." class="adugna-form-group" required>
                                            <button type="submit" name="admin_reply" class="adugna-btn adugna-btn-info adugna-btn-sm" style="margin-top:2px;">Reply</button>
                                        </form>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                            <button type="submit" name="delete_feedback" class="adugna-btn adugna-btn-danger adugna-btn-sm" onclick="return confirm('Are you sure you want to delete this feedback?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#888;">No feedback found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Edit Modal -->
    <div id="editModal" class="adugna-modal">
        <div class="adugna-modal-content">
            <span class="adugna-modal-close" onclick="closeModal()">&times;</span>
            <h2 style="color:#1976d2;"><i class="fas fa-edit"></i> Edit Feedback</h2>
            <form method="POST">
                <input type="hidden" name="feedback_id" id="editFeedbackId">
                <div class="adugna-form-group">
                    <label for="editSubject">Subject</label>
                    <input type="text" name="subject" id="editSubject" required>
                </div>
                <div class="adugna-form-group">
                    <label for="editMessage">Message</label>
                    <textarea name="message" id="editMessage" rows="4" required></textarea>
                </div>
                <div class="adugna-form-group">
                    <label for="editRating">Rating</label>
                    <input type="number" name="rating" id="editRating" min="1" max="5" required>
                </div>
                <button type="submit" name="edit_feedback" class="adugna-btn"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Adugna Gizaw: Chart.js feedback ratings bar chart, visually outstanding and responsive
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
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Feedback Ratings Distribution', color: '#1976d2', font: { weight: 700, size: 16 } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { display: false }, ticks: { color: '#1976d2', font: { weight: 600 } } },
                    y: { beginAtZero: true, precision: 0, grid: { color: '#e5e7eb' }, ticks: { color: '#34495e', font: { weight: 500 } } }
                }
            }
        });

        // Adugna Gizaw: Feedback search (real-time, compact, responsive)
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

        // Adugna Gizaw: Edit feedback modal logic
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
