<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/ob_start();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../models/Survey.php';

$pageTitle = "Survey Builder";
$survey_id = $_GET['id'] ?? null;
$survey = null;

// Fetch survey details if editing an existing survey
if ($survey_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, GROUP_CONCAT(DISTINCT r.id) as target_roles
            FROM surveys s
            LEFT JOIN survey_roles sr ON s.id = sr.survey_id
            LEFT JOIN roles r ON sr.role_id = r.id
            WHERE s.id = ?
            GROUP BY s.id
        ");
        $stmt->execute([$survey_id]);
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($survey) {
            $survey['target_roles'] = $survey['target_roles'] ? 
                array_map('intval', explode(',', $survey['target_roles'])) : [];
            
            $questionStmt = $pdo->prepare("
                SELECT id, field_type, field_label, field_options, is_required, display_order
                FROM survey_fields
                WHERE survey_id = ?
                ORDER BY display_order
            ");
            $questionStmt->execute([$survey_id]);
            $survey['questions'] = $questionStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching survey data: " . $e->getMessage());
        $_SESSION['error'] = "Failed to load survey data: " . $e->getMessage();
        header("Location: surveys.php");
        exit();
    }
}

$roles = fetchAll($pdo, "SELECT id, role_name FROM roles ORDER BY role_name");
$categories = fetchAll($pdo, "SELECT id, name FROM survey_categories ORDER BY name");
$statuses = fetchAll($pdo, "SELECT id, status, label FROM survey_statuses ORDER BY id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $survey_data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => $_POST['category_id'] ?? null,
        'status' => $_POST['status'] ?? null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'is_public' => isset($_POST['is_public']) ? 1 : 0,
        'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
        'starts_at' => date('Y-m-d H:i:s', strtotime($_POST['starts_at'] ?? '+1 day')),
        'ends_at' => date('Y-m-d H:i:s', strtotime($_POST['ends_at'] ?? '+1 month'))
    ];

    $target_roles = isset($_POST['target_roles']) ? array_map('intval', $_POST['target_roles']) : [];
    $questions = prepareQuestions(
        $_POST['questions'] ?? [],
        $_POST['field_types'] ?? [],
        $_POST['options'] ?? [],
        $_POST['required'] ?? []
    );

    if (isset($_POST['is_public'])) {
        $survey_data['is_public'] = 1;
    } else {
        $survey_data['is_public'] = 0;
    }

    try {
        $pdo->beginTransaction();
        
        if ($survey_id) {
            updateSurvey($pdo, $survey_data, $survey_id);
        } else {
            $survey_id = createSurvey($pdo, $survey_data);
        }

        updateTargetRoles($pdo, $survey_id, $target_roles);
        updateSurveyFields($pdo, $survey_id, $questions);
        
        $pdo->commit();

        $_SESSION['success'] = $survey_id ? "Survey updated successfully!" : "Survey created successfully!";
        // Do not redirect, just reload the page to show the message and link
        header("Location: survey_builder.php?id=" . urlencode($survey_id));
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error saving survey: " . $e->getMessage());
        $_SESSION['error'] = "Failed to save survey: " . $e->getMessage();
    }
}

function fetchAll($pdo, $query) {
    try {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching data: " . $e->getMessage());
        return [];
    }
}

function prepareQuestions($questions, $field_types, $options, $required) {
    $preparedQuestions = [];
    foreach ($questions as $index => $question) {
        $fieldOptions = null;
        if (!empty($options[$index])) {
            $optionsArray = array_filter(
                array_map('trim', 
                    explode("\n", $options[$index])
                )
            );
            $fieldOptions = json_encode($optionsArray);
        }

        $preparedQuestions[] = [
            'field_type' => $field_types[$index] ?? 'text',
            'field_label' => trim($question),
            'field_options' => $fieldOptions,
            'is_required' => isset($required[$index]) ? 1 : 0,
            'display_order' => $index
        ];
    }
    return $preparedQuestions;
}

function createSurvey($pdo, $survey_data) {
    $stmt = $pdo->prepare("
        INSERT INTO surveys 
        (title, description, category_id, status, is_active, is_public, is_anonymous, starts_at, ends_at, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?,?, NOW(), ?)
    ");
    $stmt->execute(array_merge(
        array_values($survey_data),
        [$_SESSION['user_id']]
    ));
    return $pdo->lastInsertId();
}

function updateSurvey($pdo, $survey_data, $survey_id) {
    $stmt = $pdo->prepare("
        UPDATE surveys 
        SET title = ?, description = ?, category_id = ?, status = ?, 
            is_active = ?, is_public = ?, is_anonymous = ?, starts_at = ?, ends_at = ?
        WHERE id = ?
    ");
    $stmt->execute(array_merge(
        array_values($survey_data),
        [$survey_id]
    ));
}

function updateTargetRoles($pdo, $survey_id, $target_roles) {
    $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
    $stmt->execute([$survey_id]);

    if (!empty($target_roles)) {
        $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
        foreach ($target_roles as $role_id) {
            $stmt->execute([$survey_id, $role_id]);
        }
    }
}

function updateSurveyFields($pdo, $survey_id, $questions) {
    $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
    $stmt->execute([$survey_id]);

    if (!empty($questions)) {
        $stmt = $pdo->prepare("
            INSERT INTO survey_fields 
            (survey_id, field_type, field_label, field_options, is_required, display_order) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($questions as $question) {
            $stmt->execute([
                $survey_id,
                $question['field_type'],
                $question['field_label'],
                $question['field_options'],
                $question['is_required'],
                $question['display_order']
            ]);
        }
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
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting and clarity */
        body {
            background: linear-gradient(120deg, #f0f4ff 0%, #f9fafb 100%);
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            min-height: 100vh;
        }
        .adugna-admin-main {
            margin-left: 260px;
            padding: 1.2rem 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        /* Compact ERPNext/Jinja2/frappe-inspired card style */
        .adugna-card {
            background: #fff;
            border-radius: 0.7rem;
            box-shadow: 0 2px 12px 0 rgba(80, 112, 255, 0.08), 0 1.5px 6px 0 rgba(80, 112, 255, 0.03);
            border: 1px solid #e5e7eb;
            padding: 1.1rem 1.1rem;
            margin-bottom: 1.2rem;
            width: 100%;
            max-width: 900px;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        .adugna-form-container {
            background: #fff;
            border-radius: 0.7rem;
            box-shadow: 0 2px 12px 0 rgba(80, 112, 255, 0.08), 0 1.5px 6px 0 rgba(80, 112, 255, 0.03);
            border: 1px solid #e5e7eb;
            padding: 1.1rem 1.1rem;
            margin-bottom: 1.2rem;
            width: 100%;
            max-width: 900px;
        }
        .adugna-form-group {
            margin-bottom: 0.8rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .adugna-form-group label {
            font-weight: 600;
            color: #215967;
            margin-bottom: 0.2em;
            font-size: 0.98em;
            letter-spacing: 0.01em;
        }
        .adugna-form-group input[type="text"],
        .adugna-form-group input[type="datetime-local"],
        .adugna-form-group select,
        .adugna-form-group textarea {
            max-width: 350px;
            min-width: 120px;
            width: 100%;
            border-radius: 0.4em;
            border: 1.2px solid #e5e7eb;
            background: #f3f4f6;
            padding: 0.45em 0.8em;
            font-size: 0.97em;
            transition: border 0.18s, box-shadow 0.18s;
            box-sizing: border-box;
            margin-bottom: 0.05em;
        }
        .adugna-form-group textarea {
            min-height: 60px;
            resize: vertical;
            font-family: inherit;
        }
        .adugna-form-group input:focus,
        .adugna-form-group select:focus,
        .adugna-form-group textarea:focus {
            border: 1.2px solid #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 2px #a5b4fc33;
        }
        .adugna-form-group input[type="text"]:hover,
        .adugna-form-group input[type="datetime-local"]:hover,
        .adugna-form-group select:hover,
        .adugna-form-group textarea:hover {
            border: 1.2px solid #a5b4fc;
        }
        .adugna-roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 7px;
            width: 100%;
            max-width: 350px;
        }
        .adugna-question-box {
            border: 1px solid #e5e7eb;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 0.5em;
            background: #f9f9f9;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        .adugna-question-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
            font-weight: 600;
            color: #215967;
            align-items: center;
        }
        .adugna-question-header .adugna-btn {
            padding: 0.12rem 0.5rem;
            font-size: 0.85em;
        }
        .adugna-question-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .adugna-help-text {
            font-size: 0.85em;
            color: #666;
            margin-top: 3px;
        }
        /* Adugna: Compact, ERPNext/frappe-inspired button styles */
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.4em;
            padding: 0.18rem 0.7rem;
            font-size: 0.89em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 0.89em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        .adugna-btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        .adugna-btn-secondary:hover {
            background: #e5e7eb;
            color: #22223b;
        }
        .adugna-btn-primary {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
        }
        .adugna-btn-primary:hover {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
        }
        .adugna-add-question { margin-bottom: 12px; }
        .adugna-alert {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.7rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border: 1px solid #fca5a5;
            font-size: 0.95em;
        }
        @media (max-width: 900px) {
            .adugna-form-container, .adugna-admin-main { padding: 0.7rem; }
            .adugna-question-content { grid-template-columns: 1fr; gap: 7px; }
            .adugna-form-group input[type="text"],
            .adugna-form-group input[type="datetime-local"],
            .adugna-form-group select,
            .adugna-form-group textarea { max-width: 100%; }
        }
        @media (max-width: 600px) {
            .adugna-form-container, .adugna-admin-main { padding: 2px; }
            .adugna-question-header { flex-direction: column; gap: 4px; align-items: flex-start; }
            .adugna-question-content { grid-template-columns: 1fr; gap: 5px; }
            .adugna-btn { padding: 0.12rem 0.5rem; font-size: 0.85em; }
        }
        @media (max-width: 400px) {
            .adugna-card, .adugna-form-container { padding: 2px; }
            .adugna-form-group label { font-size: 0.93em; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-admin-main">
            <header class="admin-header" style="width:100%;max-width:900px;margin:0 auto 1.2rem auto;">
                <h1 style="color:#215967;font-weight:700;"><i class="fas fa-poll"></i> <?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="adugna-form-container adugna-card">
                <h2 style="color:#215967;font-weight:600;"><?= $survey ? "Edit Survey" : "Create New Survey" ?></h2>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="adugna-alert"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <form method="POST" class="survey-form">
                    <input type="hidden" name="id" value="<?= $survey_id ?? '' ?>">
                    <div class="adugna-form-group">
                        <label for="title">Survey Title *</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title'] ?? '') ?>" required>
                    </div>
                    <div class="adugna-form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($survey['description'] ?? '') ?></textarea>
                    </div>
                    <div class="adugna-form-group">
                        <label>Target Roles *</label>
                        <div class="adugna-roles-grid">
                            <?php foreach ($roles as $role): ?>
                                <div class="role-checkbox">
                                    <label>
                                        <input type="checkbox" name="target_roles[]" value="<?= $role['id'] ?>"
                                            <?= in_array($role['id'], $survey['target_roles'] ?? []) ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="adugna-form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"
                                    <?= ($category['id'] == ($survey['category_id'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adugna-form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['id'] ?>"
                                    <?= ($status['id'] == ($survey['status'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adugna-form-group">
                        <label for="starts_at">Start Date *</label>
                        <input type="datetime-local" id="starts_at" name="starts_at"
                               value="<?= date('Y-m-d\TH:i', strtotime($survey['starts_at'] ?? '+1 day')) ?>" required>
                    </div>
                    <div class="adugna-form-group">
                        <label for="ends_at">End Date *</label>
                        <input type="datetime-local" id="ends_at" name="ends_at"
                               value="<?= date('Y-m-d\TH:i', strtotime($survey['ends_at'] ?? '+1 month')) ?>" required>
                    </div>
                    <div class="adugna-form-group">
                        <label>
                            <input type="checkbox" name="is_anonymous"
                                <?= isset($survey['is_anonymous']) && $survey['is_anonymous'] ? 'checked' : '' ?>>
                            Make survey anonymous
                        </label>
                    </div>
                    <div class="adugna-form-group">
                        <label>
                            <input type="checkbox" name="is_active"
                                <?= isset($survey['is_active']) && $survey['is_active'] ? 'checked' : '' ?>>
                            Activate survey
                        </label>
                    </div>
                    <div class="adugna-form-group">
                        <label>
                            <input type="checkbox" name="is_public"
                                <?= isset($survey['is_public']) && $survey['is_public'] ? 'checked' : '' ?>>
                            Make survey public
                        </label>
                    </div>
                    <h3 style="color:#215967;">Survey Questions</h3>
                    <div id="questions-container">
                        <?php if (isset($survey['questions'])): ?>
                            <?php foreach ($survey['questions'] as $index => $question): ?>
                                <div class="adugna-question-box" data-index="<?= $index ?>">
                                    <div class="adugna-question-header">
                                        <span>Question <?= $index + 1 ?></span>
                                        <button type="button" class="adugna-btn adugna-btn-secondary remove-question"><i class="fas fa-trash"></i> Remove</button>
                                    </div>
                                    <div class="adugna-question-content">
                                        <div class="adugna-form-group">
                                            <label>Question Text *</label>
                                            <input type="text" name="questions[]" value="<?= htmlspecialchars($question['field_label']) ?>" required>
                                        </div>
                                        <div class="adugna-form-group">
                                            <label>Field Type *</label>
                                            <select name="field_types[]" required>
                                                <option value="text" <?= $question['field_type'] == 'text' ? 'selected' : '' ?>>Text</option>
                                                <option value="textarea" <?= $question['field_type'] == 'textarea' ? 'selected' : '' ?>>Textarea</option>
                                                <option value="radio" <?= $question['field_type'] == 'radio' ? 'selected' : '' ?>>Radio</option>
                                                <option value="checkbox" <?= $question['field_type'] == 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
                                                <option value="select" <?= $question['field_type'] == 'select' ? 'selected' : '' ?>>Select</option>
                                                <option value="number" <?= $question['field_type'] == 'number' ? 'selected' : '' ?>>Number</option>
                                                <option value="date" <?= $question['field_type'] == 'date' ? 'selected' : '' ?>>Date</option>
                                                <option value="rating" <?= $question['field_type'] == 'rating' ? 'selected' : '' ?>>Rating</option>
                                                <option value="file" <?= $question['field_type'] == 'file' ? 'selected' : '' ?>>File</option>
                                            </select>
                                        </div>
                                        <div class="adugna-form-group">
                                            <label>
                                                <input type="checkbox" name="required[]" <?= $question['is_required'] ? 'checked' : '' ?>>
                                                Required
                                            </label>
                                        </div>
                                        <div class="adugna-form-group">
                                            <label>Options (for radio, checkbox, select)</label>
                                            <textarea name="options[]" rows="3"><?= 
                                                isset($question['field_options']) ? 
                                                htmlspecialchars(implode("\n", json_decode($question['field_options']))) : 
                                                '' 
                                            ?></textarea>
                                            <p class="adugna-help-text">Enter each option on a new line</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="adugna-btn adugna-btn-secondary add-question"><i class="fas fa-plus"></i> Add Question</button>
                    <button type="submit" class="adugna-btn adugna-btn-primary"><i class="fas fa-save"></i> Save Survey</button>
                </form>
                <?php if (($survey['is_public'] ?? false) && ($survey['id'] ?? $survey_id ?? false)): ?>
                    <div class="adugna-card" style="margin-top:1.5rem;">
                        <h3 style="color:#215967;font-weight:600;margin-bottom:0.5em;">Share Survey</h3>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                            <input type="text" id="publicSurveyLink" value="https://crm.flipperschool.com/survey_response.php?id=<?= $survey['id'] ?? $survey_id ?>" readonly style="width:350px;max-width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:4px;font-size:1em;background:#f3f4f6;">
                            <button class="adugna-btn adugna-btn-secondary" id="copySurveyLink"><i class="fas fa-copy"></i> Copy Link</button>
                            <button class="adugna-btn adugna-btn-primary" id="copyShareMessage"><i class="fas fa-share-alt"></i> Copy Share Message</button>
                        </div>
                        <div style="font-size:0.95em;color:#666;margin-top:0.4em;">Share this link to allow anyone to fill the survey without login.</div>
                        <div style="margin-top:1.2em;max-width:420px;">
                            <div style="border:1px solid #e5e7eb;border-radius:7px;padding:1em;background:#f8fafc;box-shadow:0 1px 4px rgba(44,62,80,0.04);">
                                <div style="font-size:1.15em;font-weight:600;color:#215967;line-height:1.3;">
                                    <?= htmlspecialchars($survey['title'] ?? '') ?>
                                </div>
                                <div style="color:#444;margin:0.5em 0 0.7em 0;font-size:0.98em;">
                                    <?= nl2br(htmlspecialchars($survey['description'] ?? '')) ?>
                                </div>
                                <a href="https://crm.flipperschool.com/survey_response.php?id=<?= $survey['id'] ?? $survey_id ?>" target="_blank" style="color:#fff;background:#007bff;padding:7px 16px;border-radius:4px;text-decoration:none;font-size:0.97em;display:inline-block;">Take the Survey</a>
                            </div>
                        </div>
                    </div>
                    <script>
                    document.getElementById('copySurveyLink').onclick = function() {
                        var linkInput = document.getElementById('publicSurveyLink');
                        linkInput.select();
                        linkInput.setSelectionRange(0, 99999);
                        document.execCommand('copy');
                        this.textContent = 'Copied!';
                        setTimeout(() => { this.innerHTML = '<i class="fas fa-copy"></i> Copy Link'; }, 1200);
                    };
                    document.getElementById('copyShareMessage').onclick = function() {
                        var title = <?= json_encode($survey['title'] ?? '') ?>;
                        var desc = <?= json_encode($survey['description'] ?? '') ?>;
                        var link = document.getElementById('publicSurveyLink').value;
                        var msg = `${title}\n${desc ? desc + '\n' : ''}Take the survey: ${link}`;
                        var temp = document.createElement('textarea');
                        temp.value = msg;
                        document.body.appendChild(temp);
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                        this.textContent = 'Copied!';
                        setTimeout(() => { this.innerHTML = '<i class="fas fa-share-alt"></i> Copy Share Message'; }, 1200);
                    };
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Adugna Gizaw: Interactive add/remove question logic, compact and responsive
    $(document).ready(function() {
        // Add new question
        $('.add-question').click(function() {
            const index = $('#questions-container .question-box').length;
            const questionBox = `
                <div class="question-box" data-index="${index}">
                    <div class="question-header">
                        <span>Question ${index + 1}</span>
                        <button type="button" class="adugna-btn adugna-btn-secondary remove-question"><i class="fas fa-trash"></i> Remove</button>
                    </div>
                    <div class="question-content">
                        <div class="form-group">
                            <label>Question Text *</label>
                            <input type="text" name="questions[]" required>
                        </div>
                        <div class="form-group">
                            <label>Field Type *</label>
                            <select name="field_types[]" required>
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="radio">Radio</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="select">Select</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="rating">Rating</option>
                                <option value="file">File</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="required[]">
                                Required
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Options (for radio, checkbox, select)</label>
                            <textarea name="options[]" rows="3"></textarea>
                            <p class="help-text">Enter each option on a new line</p>
                        </div>
                    </div>
                </div>
            `;
            $('#questions-container').append(questionBox);
        });
        // Remove question
        $(document).on('click', '.remove-question', function() {
            $(this).closest('.question-box').remove();
            // Reindex remaining questions
            $('#questions-container .question-box').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('.question-header span').text(`Question ${index + 1}`);
            });
        });
    });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush();?>