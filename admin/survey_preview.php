<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['id'] ?? 0;

// Get survey info
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header("Location: surveys.php");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Get target roles
$target_roles = [];
if (isset($survey['target_roles']) && !is_null($survey['target_roles']) && $survey['target_roles'] !== '') {
    $decoded = json_decode($survey['target_roles'], true);
    if (is_array($decoded)) {
        $target_roles = $decoded;
    }
}

$pageTitle = "Preview: " . htmlspecialchars($survey['title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting and clarity */
        .adugna-admin-dashboard, .admin-dashboard {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            background: linear-gradient(120deg, #f0f4ff 0%, #f9fafb 100%);
        }
        .adugna-admin-main, .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 2.2rem 1.2rem 1.2rem 1.2rem;
            background: transparent;
            min-width: 0;
            min-height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 6px 32px 0 rgba(80, 112, 255, 0.08), 0 1.5px 6px 0 rgba(80, 112, 255, 0.03);
            border: none;
            padding: 1.5rem 1.5rem;
            margin-bottom: 2rem;
            width: 100%;
            max-width: 900px;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.5em;
            padding: 0.28rem 0.85rem;
            font-size: 0.97em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 0.97em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .adugna-btn.adugna-btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        .adugna-btn.adugna-btn-secondary:hover {
            background: #e5e7eb;
            color: #22223b;
        }
        .adugna-btn.adugna-btn-sm {
            padding: 0.18rem 0.6rem;
            font-size: 0.91em;
        }
        .adugna-preview-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 1.5rem 1.5rem;
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 6px 32px 0 rgba(80, 112, 255, 0.08), 0 1.5px 6px 0 rgba(80, 112, 255, 0.03);
        }
        .adugna-survey-info {
            margin-bottom: 30px;
            padding: 1.3rem 1.1rem;
            background: #f1f4f7;
            border-radius: 1.1rem;
            border-left: 4px solid #4361ee;
        }
        .adugna-preview-field {
            margin-bottom: 25px;
            padding: 1.1rem 1rem;
            border: 1px solid #e9ecef;
            border-radius: 0.7em;
            transition: transform 0.2s ease;
            background: #f9fafb;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .adugna-preview-field:hover {
            transform: translateX(5px) scale(1.01);
            box-shadow: 0 8px 32px rgba(80,112,255,0.13), 0 2px 8px rgba(80,112,255,0.06);
        }
        .adugna-field-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 0.95em;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .adugna-rating-star {
            color: #ffd700;
            font-size: 1.3em;
            margin-right: 3px;
        }
        .adugna-required {
            color: #dc3545;
            margin-left: 5px;
        }
        .adugna-form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .adugna-form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-top: 8px;
            background: #f9fafb;
            font-size: 1em;
        }
        .adugna-options {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .adugna-form-check {
            margin-bottom: 8px;
            min-width: 120px;
        }
        .adugna-response-chart {
            max-width: 100%;
            height: 260px !important;
            margin-top: 10px;
            background: #fff;
            border-radius: 0.7em;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 0.5em;
        }
        .adugna-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }
        .adugna-meta-item label {
            font-weight: 600;
            color: #215967;
        }
        .adugna-meta-item p {
            margin: 0.2em 0 0 0;
            color: #374151;
        }
        .adugna-status-active { color: #28a745; font-weight: 500; }
        .adugna-status-inactive { color: #6c757d; }
        .adugna-status-upcoming { color: #ffc107; }
        .adugna-status-ended { color: #dc3545; }
        @media (max-width: 900px) {
            .admin-main, .adugna-admin-main { margin-left: 0 !important; padding: 1rem 0.3rem !important; }
            .adugna-preview-container, .adugna-survey-info { padding: 1rem !important; margin: 10px 0 !important; }
            .adugna-meta-grid { grid-template-columns: 1fr !important; gap: 10px !important; }
        }
        @media (max-width: 600px) {
            .admin-main, .adugna-admin-main { padding: 0.5rem 0.1rem !important; }
            .adugna-preview-container, .adugna-survey-info { padding: 0.5rem !important; margin: 6px 0 !important; }
            .adugna-preview-field { padding: 0.5rem !important; }
            .adugna-form-actions { flex-direction: column !important; gap: 10px !important; }
            .admin-header { flex-direction: column !important; align-items: flex-start !important; gap: 8px !important; }
            .page-title { font-size: 1.2rem !important; }
            .adugna-response-chart { height: 180px !important; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-dashboard adugna-admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main adugna-admin-main">
            <header class="admin-header" style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                <h1 class="page-title" style="margin: 0; flex: 1;"><?= htmlspecialchars($survey['title']) ?> Preview</h1>
                <div class="header-actions" style="flex-shrink: 0;">
                    <a href="surveys.php" class="adugna-btn adugna-btn-secondary" style="margin-right: 10px;">
                        <i class="fas fa-arrow-left"></i> Back to Surveys
                    </a>
                </div>
            </header>
            <div class="content" style="margin-top: 20px; width:100%; max-width:900px; margin-left:auto; margin-right:auto;">
                <div class="adugna-survey-info adugna-card" aria-label="Survey Information">
                    <div class="survey-meta">
                        <h2 style="color:#215967;">Survey Details</h2>
                        <div class="adugna-meta-grid">
                            <div class="adugna-meta-item">
                                <label>Description:</label>
                                <p><?= !empty($survey['description']) ? htmlspecialchars($survey['description']) : '<em>No description provided.</em>' ?></p>
                            </div>
                            <div class="adugna-meta-item">
                                <label>Target Audience:</label>
                                <p id="target-roles" aria-live="polite">Loading...</p>
                            </div>
                            <div class="adugna-meta-item">
                                <label>Status:</label>
                                <p>
                                    <?php if (!$survey['is_active']): ?>
                                        <span class="adugna-status-inactive">Inactive</span>
                                    <?php elseif (strtotime($survey['starts_at']) > time()): ?>
                                        <span class="adugna-status-upcoming">Upcoming</span>
                                    <?php elseif (strtotime($survey['ends_at']) < time()): ?>
                                        <span class="adugna-status-ended">Ended</span>
                                    <?php else: ?>
                                        <span class="adugna-status-active">Active</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="adugna-meta-item">
                                <label>Schedule:</label>
                                <p>
                                    <?php if (!empty($survey['starts_at']) && !empty($survey['ends_at'])): ?>
                                        <?= date('M j, Y', strtotime($survey['starts_at'])) ?> - 
                                        <?= date('M j, Y', strtotime($survey['ends_at'])) ?>
                                    <?php else: ?>
                                        <em>Not scheduled</em>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="adugna-preview-container adugna-card" aria-label="Survey Fields Preview">
                    <?php if (empty($fields)): ?>
                        <div class="alert alert-info" role="alert" style="text-align:center;">
                            No fields have been added to this survey yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($fields as $field): ?>
                            <div class="adugna-preview-field" tabindex="0" aria-label="Field Preview">
                                <div class="field-header" style="display: flex; align-items: center; gap: 8px;">
                                    <h3 style="margin: 0;"><?= htmlspecialchars($field['field_label']) ?>
                                        <?php if ($field['is_required']): ?>
                                            <span class="adugna-required" aria-label="Required">*</span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                                <?php if ($field['field_type'] === 'text'): ?>
                                    <input type="text" class="adugna-form-control" disabled placeholder="Text input" aria-label="Text input preview">
                                <?php elseif ($field['field_type'] === 'textarea'): ?>
                                    <textarea class="adugna-form-control" rows="4" disabled placeholder="Textarea input" aria-label="Textarea input preview"></textarea>
                                <?php elseif (in_array($field['field_type'], ['radio', 'checkbox', 'dropdown'])): ?>
                                    <div class="adugna-options" aria-label="Options">
                                        <?php
                                        $options = json_decode($field['field_options'], true);
                                        if (is_array($options) && count($options) > 0):
                                            foreach ($options as $option):
                                        ?>
                                            <div class="adugna-form-check">
                                                <input class="form-check-input" 
                                                       type="<?= $field['field_type'] === 'radio' ? 'radio' : 'checkbox' ?>" 
                                                       disabled>
                                                <label class="form-check-label">
                                                    <?= htmlspecialchars($option) ?>
                                                </label>
                                            </div>
                                        <?php
                                            endforeach;
                                        else:
                                        ?>
                                            <div class="adugna-form-check">
                                                <em>No options defined.</em>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($field['field_type'] === 'rating'): ?>
                                    <div class="adugna-rating-container" aria-label="Rating preview" style="display: flex; gap: 2px;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="adugna-rating-star" aria-hidden="true">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php elseif ($field['field_type'] === 'file'): ?>
                                    <div class="adugna-file-preview">
                                        <input type="file" class="adugna-form-control" disabled aria-label="File upload preview">
                                        <small class="form-text text-muted">File upload preview</small>
                                    </div>
                                <?php endif; ?>
                                <?php if (in_array($field['field_type'], ['radio', 'checkbox', 'select'])): ?>
                                    <canvas id="chart-<?= $field['id'] ?>" class="adugna-response-chart" aria-label="Response Chart"></canvas>
                                    <script>
                                        // Adugna Gizaw: Outstanding chart rendering with adugna- theme and animation
                                        document.addEventListener('DOMContentLoaded', function () {
                                            fetch(`../api/response_data.php?field_id=<?= $field['id'] ?>`)
                                                .then(response => response.json())
                                                .then(data => {
                                                    const ctx = document.getElementById('chart-<?= $field['id'] ?>').getContext('2d');
                                                    new Chart(ctx, {
                                                        type: 'bar',
                                                        data: {
                                                            labels: Object.keys(data),
                                                            datasets: [{
                                                                label: 'Responses',
                                                                data: Object.values(data),
                                                                backgroundColor: [
                                                                    '#4f46e5', '#10b981', '#f59e42', '#f43f5e', '#6366f1', '#fbbf24', '#0ea5e9', '#a21caf'
                                                                ],
                                                                borderColor: [
                                                                    '#4338ca', '#059669', '#ea580c', '#be123c', '#4f46e5', '#b45309', '#0369a1', '#701a75'
                                                                ],
                                                                borderWidth: 2,
                                                                borderRadius: 8,
                                                                hoverBackgroundColor: '#6366f1',
                                                                hoverBorderColor: '#1e293b'
                                                            }]
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            animation: {
                                                                duration: 1200,
                                                                easing: 'easeOutElastic'
                                                            },
                                                            plugins: {
                                                                legend: { display: false },
                                                                tooltip: {
                                                                    enabled: true,
                                                                    backgroundColor: '#4f46e5',
                                                                    titleColor: '#fff',
                                                                    bodyColor: '#fff',
                                                                    borderColor: '#6366f1',
                                                                    borderWidth: 1,
                                                                    padding: 12
                                                                },
                                                                title: {
                                                                    display: true,
                                                                    text: 'Live Responses',
                                                                    color: '#4f46e5',
                                                                    font: { size: 16, weight: 'bold', family: 'Inter, Segoe UI, Arial, sans-serif' }
                                                                }
                                                            },
                                                            scales: {
                                                                x: {
                                                                    grid: { color: '#e5e7eb', borderColor: '#e5e7eb' },
                                                                    ticks: { color: '#374151', font: { size: 13, weight: 'bold' } }
                                                                },
                                                                y: {
                                                                    beginAtZero: true,
                                                                    grid: { color: '#e5e7eb', borderColor: '#e5e7eb' },
                                                                    ticks: { color: '#374151', font: { size: 13, weight: 'bold' } }
                                                                }
                                                            },
                                                            layout: {
                                                                padding: { top: 10, bottom: 10, left: 10, right: 10 }
                                                            }
                                                        }
                                                    });
                                                });
                                        });
                                    </script>
                                <?php else: ?>
                                    <p><em>No graphical representation available for this field type.</em></p>
                                <?php endif; ?>
                                <div class="adugna-field-meta">
                                    <span><strong>Type:</strong> <?= ucfirst(str_replace('_', ' ', $field['field_type'])) ?></span>
                                    <span><strong>Technical Name:</strong> <code><?= htmlspecialchars($field['field_name']) ?></code></span>
                                    <span><strong>Required:</strong> <?= $field['is_required'] ? 'Yes' : 'No' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="adugna-form-actions">
                        <a href="results.php?survey_id=<?= $survey['id'] ?>" title="Results" class="adugna-btn adugna-btn-secondary"><i class="fas fa-chart-bar"></i> Results</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
        // Add smooth scrolling behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Fetch roles dynamically and update the target audience
        document.addEventListener('DOMContentLoaded', function () {
            const targetRoles = <?= json_encode($target_roles) ?>;
            fetch('../api/roles.php')
                .then(response => response.json())
                .then(data => {
                    const roles = targetRoles.map(roleId => data[roleId] || `Unknown Role (${roleId})`);
                    document.getElementById('target-roles').textContent = roles.join(', ');
                })
                .catch(error => {
                    console.error('Error fetching roles:', error);
                    document.getElementById('target-roles').textContent = 'Error loading roles.';
                });
        });
    </script>
</body>
</html>