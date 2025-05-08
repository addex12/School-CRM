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
        } elseif (isset($_POST['add_subject'])) {
            // Add new subject
            $subject = trim($_POST['subject']);
            if (empty($subject)) {
                throw new Exception("Subject is required.");
            }
            $stmt = $pdo->prepare("INSERT INTO feedback_subjects (subject, status) VALUES (?, 'active')");
            $stmt->execute([$subject]);
            $_SESSION['success'] = "Subject added successfully!";
        } elseif (isset($_POST['edit_subject'])) {
            // Edit subject
            $subject_id = intval($_POST['subject_id']);
            $subject = trim($_POST['subject']);
            if (empty($subject)) {
                throw new Exception("Subject is required.");
            }
            $stmt = $pdo->prepare("UPDATE feedback_subjects SET subject = ? WHERE id = ?");
            $stmt->execute([$subject, $subject_id]);
            $_SESSION['success'] = "Subject updated successfully!";
        } elseif (isset($_POST['delete_subject'])) {
            // Delete subject
            $subject_id = intval($_POST['subject_id']);
            $stmt = $pdo->prepare("DELETE FROM feedback_subjects WHERE id = ?");
            $stmt->execute([$subject_id]);
            $_SESSION['success'] = "Subject deleted successfully!";
        } elseif (isset($_POST['toggle_subject_status'])) {
            // Toggle subject status
            $subject_id = intval($_POST['subject_id']);
            $current_status = $_POST['current_status'];
            $new_status = ($current_status === 'active') ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE feedback_subjects SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $subject_id]);
            $_SESSION['success'] = "Subject status updated successfully!";
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
    <!--
    Developer: Adugna Gizaw
    Custom adugna- styles for compact, branded, responsive, visually outstanding UI.
    All styles use adugna- prefix for patenting and branding.
    -->
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/feedback.js" defer></script>
    <style>
        /**
         * Developer: Adugna Gizaw
         * All custom styles use adugna- prefix for branding and patenting.
         * Compact, outstanding, responsive, content/screen-aware UI.
         * Layout and cards adapt to any screen size for best usability.
         */
        html { font-size: 16px; }
        @media (max-width: 1200px) { html { font-size: 15px; } }
        @media (max-width: 900px) { html { font-size: 14px; } }
        @media (max-width: 700px) { html { font-size: 13px; } }
        @media (max-width: 500px) { html { font-size: 12px; } }

        .adugna-main-content {
            max-width: 1100px;
            margin: 2vw auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.08);
            padding: 2vw 1vw 3vw 1vw;
            transition: box-shadow 0.2s;
            width: 96vw;
        }
        @media (max-width: 900px) {
            .adugna-main-content { max-width: 99vw; padding: 2vw 2vw 3vw 2vw; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 1vw 0.5vw 2vw 0.5vw; }
        }

        .adugna-card {
            background: #fff;
            border-radius: 7px;
            box-shadow: 0 1px 6px rgba(25,118,210,0.06);
            padding: 1.2vw 1.5vw 1.5vw 1.5vw;
            margin-bottom: 1.1rem;
            transition: box-shadow 0.2s, width 0.2s;
            width: 100%;
        }
        @media (max-width: 700px) {
            .adugna-card { padding: 2vw 1vw 2vw 1vw; }
        }
        @media (max-width: 500px) {
            .adugna-card { padding: 2vw 0.5vw 2vw 0.5vw; }
        }

        .adugna-header-title {
            font-size: 1.15em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 14px;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }
        .adugna-header-title i {
            font-size: 1.1em;
        }

        .adugna-card-header {
            font-size: 1em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 0.7em;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .adugna-card-header i {
            font-size: 1em;
        }

        .adugna-form-group {
            margin-bottom: 0.7rem;
            display: flex;
            flex-direction: column;
            gap: 0.15em;
            width: 100%;
        }
        .adugna-form-group label {
            font-size: 0.96em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input,
        .adugna-form-group select,
        .adugna-form-group textarea {
            padding: 5px 8px;
            border-radius: 3px;
            border: 1px solid #d0d7de;
            font-size: 0.96em;
            background: #f9fbfd;
            color: #222;
            width: 100%;
            box-sizing: border-box;
        }
        .adugna-form-group textarea {
            min-height: 70px;
            resize: vertical;
        }

        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 3px 10px;
            font-size: 0.93em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
            min-height: 26px;
        }
        .adugna-btn i { font-size: 0.95em; }
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
        .adugna-btn-sm { padding: 1px 6px; font-size: 0.89em; border-radius: 2px; min-height: 22px; }

        .adugna-alert {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #d4f5e9;
            border-radius: 4px;
            padding: 7px 14px;
            margin-bottom: 0.7em;
            font-size: 0.95em;
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
            overflow-x: auto;
            display: block;
        }
        .adugna-table th, .adugna-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 0.95em;
            white-space: pre-line;
        }
        .adugna-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
        }
        .adugna-table tr:hover { background: #f4f8fb; }
        .adugna-feedback-rating span { font-size: 1.1em; }
        .adugna-admin-reply { background: #f5f7fa; border-radius: 4px; padding: 4px 8px; margin-bottom: 4px; color: #215967; font-size: 0.93em; }

        /* Responsive table for small screens */
        @media (max-width: 700px) {
            .adugna-table, .adugna-table thead, .adugna-table tbody, .adugna-table th, .adugna-table td, .adugna-table tr {
                display: block;
            }
            .adugna-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .adugna-table tr {
                border: 1px solid #e2efda;
                margin-bottom: 8px;
                border-radius: 5px;
                box-shadow: 0 1px 4px rgba(25,118,210,0.04);
                background: #fff;
            }
            .adugna-table td {
                border: none;
                border-bottom: 1px solid #f0f2f5;
                position: relative;
                padding-left: 50%;
                min-height: 32px;
            }
            .adugna-table td:before {
                position: absolute;
                top: 8px;
                left: 8px;
                width: 45%;
                white-space: nowrap;
                font-weight: bold;
                color: #1976d2;
                font-size: 0.96em;
                content: attr(data-label);
            }
        }

        .adugna-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); }
        .adugna-modal-content { background-color: #fefefe; margin: 10% auto; padding: 16px; border: 1px solid #888; width: 48%; border-radius: 7px; }
        .adugna-modal-close { color: #aaa; float: right; font-size: 22px; font-weight: bold; }
        .adugna-modal-close:hover, .adugna-modal-close:focus { color: #1976d2; text-decoration: none; cursor: pointer; }
        @media (max-width: 900px) {
            .adugna-modal-content { width: 80%; }
        }
        @media (max-width: 600px) {
            .adugna-modal-content { width: 97%; }
        }

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
            font-size: 1.2em;
            margin: 0 1px;
        }
        .adugna-star-rating input[type="radio"]:checked ~ label,
        .adugna-star-rating label:hover,
        .adugna-star-rating label:hover ~ label {
            color: orange;
        }
    </style>
</head>
<body>
    <!-- Floating global status message -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div id="statusMsg" class="adugna-alert adugna-alert-success text-center" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;min-width:220px;max-width:350px;padding:8px 18px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:5px;">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (!empty($_SESSION['error'])): ?>
        <div id="statusMsg" class="adugna-alert adugna-alert-error text-center" style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;min-width:220px;max-width:350px;padding:8px 18px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:5px;">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <script>
    // Developer: Adugna Gizaw
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
                    <button type="submit" name="add_feedback" class="adugna-btn"><i class="fas fa-plus"></i> Add</button>
                </form>
            </div>
            <!-- Feedback List Section -->
            <div class="adugna-card">
                <div class="adugna-card-header"><i class="fas fa-list"></i> Feedback List</div>
                <div style="max-width:420px;margin-bottom:18px;">
                    <canvas id="feedbackChart" height="140"></canvas>
                </div>
                <input type="text" id="feedback-search" placeholder="Search feedback..." class="adugna-form-group" style="margin-bottom:1rem;">
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
                                    <td data-label="ID"><?= htmlspecialchars($feedback['id']) ?></td>
                                    <td data-label="User"><?= htmlspecialchars($feedback['username'] ?? 'Anonymous') ?></td>
                                    <td data-label="Subject"><?= htmlspecialchars($feedback['subject']) ?></td>
                                    <td data-label="Message"><?= htmlspecialchars($feedback['message']) ?></td>
                                    <td data-label="Rating" class="adugna-feedback-rating">
                                        <?php
                                        $full = intval($feedback['rating']);
                                        $empty = 5 - $full;
                                        for ($i=0; $i<$full; $i++) echo '<span style="color:gold;font-size:1.1em">&#9733;</span>';
                                        for ($i=0; $i<$empty; $i++) echo '<span style="color:#ccc;font-size:1.1em">&#9733;</span>';
                                        ?>
                                    </td>
                                    <td data-label="Admin Reply">
                                        <?php if (!empty($feedback['admin_reply'])): ?>
                                            <div class="adugna-admin-reply"><strong>Admin:</strong> <?= htmlspecialchars($feedback['admin_reply']) ?></div>
                                        <?php endif; ?>
                                        <form method="POST" style="margin-top:4px;">
                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                            <input type="text" name="reply" placeholder="Add reply..." class="adugna-form-group" required>
                                            <button type="submit" name="admin_reply" class="adugna-btn adugna-btn-info adugna-btn-sm" style="margin-top:1px;">Reply</button>
                                        </form>
                                    </td>
                                    <td data-label="Created At"><?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?></td>
                                    <td data-label="Actions">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                            <button type="submit" name="delete_feedback" class="adugna-btn adugna-btn-danger adugna-btn-sm" onclick="return confirm('Are you sure you want to delete this feedback?')"><i class="fas fa-trash"></i></button>
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
            <!-- Feedback Subject Manager Section -->
            <div class="adugna-card">
                <div class="adugna-card-header"><i class="fas fa-tags"></i> Feedback Subject Manager</div>
                <!-- Add Subject Form -->
                <form method="POST" style="margin-bottom:1.2em;">
                    <div class="adugna-form-group">
                        <label for="subject">New Subject</label>
                        <input type="text" name="subject" id="subject" required>
                    </div>
                    <button type="submit" name="add_subject" class="adugna-btn"><i class="fas fa-plus"></i> Add Subject</button>
                </form>
                <div class="adugna-card-header" style="margin-top:1.5rem;"><i class="fas fa-list"></i> Subject List</div>
                <?php
                $subjectList = $pdo->query("SELECT * FROM feedback_subjects ORDER BY subject")->fetchAll();
                if (count($subjectList) > 0): ?>
                    <table class="adugna-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjectList as $subject): ?>
                                <tr>
                                    <td data-label="ID"><?= htmlspecialchars($subject['id']) ?></td>
                                    <td data-label="Subject">
                                        <?php if (isset($_GET['edit_subject']) && $_GET['edit_subject'] == $subject['id']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                                <input type="text" name="subject" value="<?= htmlspecialchars($subject['subject']) ?>" required style="width:120px;">
                                                <button type="submit" name="edit_subject" class="adugna-btn adugna-btn-info adugna-btn-sm"><i class="fas fa-save"></i></button>
                                                <a href="feedback_mgmt.php" class="adugna-btn adugna-btn-secondary adugna-btn-sm"><i class="fas fa-times"></i></a>
                                            </form>
                                        <?php else: ?>
                                            <?= htmlspecialchars($subject['subject']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Status"><?= htmlspecialchars($subject['status']) ?></td>
                                    <td data-label="Actions">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $subject['status'] ?>">
                                            <button type="submit" name="toggle_subject_status" class="adugna-btn adugna-btn-secondary adugna-btn-sm"><?= $subject['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                        <a href="feedback_mgmt.php?edit_subject=<?= $subject['id'] ?>" class="adugna-btn adugna-btn-info adugna-btn-sm"><i class="fas fa-edit"></i></a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                            <button type="submit" name="delete_subject" class="adugna-btn adugna-btn-danger adugna-btn-sm" onclick="return confirm('Are you sure you want to delete this subject?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#888;">No subjects found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Edit Modal -->
    <div id="editModal" class="adugna-modal">
        <div class="adugna-modal-content">
            <span class="adugna-modal-close" onclick="closeModal()">&times;</span>
            <h2 style="color:#1976d2;font-size:1.1em;"><i class="fas fa-edit"></i> Edit Feedback</h2>
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
                <button type="submit" name="edit_feedback" class="adugna-btn"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Developer: Adugna Gizaw
        // Chart.js feedback ratings bar chart, visually outstanding and responsive
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
                    borderRadius: 7,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Feedback Ratings Distribution', color: '#1976d2', font: { weight: 700, size: 15 } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { display: false }, ticks: { color: '#1976d2', font: { weight: 600 } } },
                    y: { beginAtZero: true, precision: 0, grid: { color: '#e5e7eb' }, ticks: { color: '#34495e', font: { weight: 500 } } }
                }
            }
        });

        // Developer: Adugna Gizaw
        // Feedback search (real-time, compact, responsive)
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

        // Developer: Adugna Gizaw
        // Edit feedback modal logic
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
