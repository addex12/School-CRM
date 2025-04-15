<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Feedback Management";

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
            $check = $pdo->query("SHOW TABLES");
            var_dump($check->fetchAll());
            $check2 = $pdo->query("SHOW COLUMNS FROM feedback");
            var_dump($check2->fetchAll());
            exit; 
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
    <script src="../assets/js/feedback.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <!-- Add Feedback Section -->
                <section class="form-section">
                    <h2>Add Feedback</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="user_id">User ID</label>
                            <input type="number" name="user_id" id="user_id" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" name="subject" id="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea name="message" id="message" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="rating">Rating</label>
                            <input type="number" name="rating" id="rating" min="1" max="5" required>
                        </div>
                        <button type="submit" name="add_feedback" class="btn btn-primary">Add Feedback</button>
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
                        <table class="table">
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
                            <tbody>
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
                <button type="submit" name="admin_reply" class="btn btn-sm btn-info" style="margin-top:2px;">Reply</button>
            </form>
        </td>
        <td><?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?></td>
        <td>
                                            <!-- Edit Button -->
                                            <?php /**<button class="btn btn-secondary" onclick="editFeedback(<?= $feedback['id'] ?>, '<?= htmlspecialchars($feedback['subject']) ?>', '<?= htmlspecialchars($feedback['message']) ?>', <?= $feedback['rating'] ?>)">Edit</button> **/?>
                                            
                                            <!-- Delete Button -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                <button type="submit" name="delete_feedback" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this feedback?')">Delete</button>
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
                <button type="submit" name="edit_feedback" class="btn btn-primary">Save Changes</button>
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

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <?php include 'includes/footer.php'; ?>
</body>
</html>