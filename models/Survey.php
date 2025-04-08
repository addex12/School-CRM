<?php
class Survey {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public static function model() {
        return new self();
    }

    public static function getStatuses() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT status, label, icon FROM survey_statuses ORDER BY id");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching survey statuses: " . $e->getMessage());
            return [];
        }
    }

    public function findByPk($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, 
                       sc.name as category_name,
                       u.username as created_by_user
                FROM surveys s
                LEFT JOIN survey_categories sc ON s.category_id = sc.id
                LEFT JOIN users u ON s.created_by = u.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $survey = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$survey) {
                return false;
            }
            
            // Get survey questions
            $stmt = $this->pdo->prepare("
                SELECT q.*, 
                       o.options 
                FROM survey_fields q
                LEFT JOIN survey_field_options o ON q.id = o.field_id
                WHERE q.survey_id = ?
                ORDER BY q.sort_order
            ");
            $stmt->execute([$id]);
            $survey['questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get survey roles
            $stmt = $this->pdo->prepare("
                SELECT r.role_name 
                FROM survey_roles sr
                JOIN roles r ON sr.role_id = r.id
                WHERE sr.survey_id = ?
            ");
            $stmt->execute([$id]);
            $survey['roles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $survey;
        } catch (PDOException $e) {
            error_log("Error finding survey by ID: " . $e->getMessage());
            return false;
        }
    }

    public function validate($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = "Title is required";
        }
        
        if (empty($data['description'])) {
            $errors[] = "Description is required";
        }
        
        if (empty($data['category_id'])) {
            $errors[] = "Category is required";
        }
        
        if (empty($data['status'])) {
            $errors[] = "Status is required";
        }
        
        if (empty($data['target_roles'])) {
            $errors[] = "At least one target role is required";
        }
        
        return $errors;
    }

    public function save($data) {
        global $pdo; // Ensure $pdo is available
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $pdo->beginTransaction();

            if (isset($data['id'])) {
                // Update existing survey
                $stmt = $pdo->prepare("
                    UPDATE surveys 
                    SET title = ?, 
                        description = ?,
                        category_id = ?,
                        status = ?,
                        is_active = ?,
                        is_anonymous = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['title'],
                    $data['description'],
                    $data['category_id'],
                    $data['status'],
                    isset($data['is_active']) ? 1 : 0,
                    isset($data['is_anonymous']) ? 1 : 0,
                    $data['id']
                ]);

                // Update survey roles
                $stmt = $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
                $stmt->execute([$data['id']]);

                foreach ($data['target_roles'] as $role_id) {
                    $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$data['id'], $role_id]);
                }
            } else {
                // Create new survey
                $stmt = $pdo->prepare("
                    INSERT INTO surveys 
                    (title, description, category_id, status, is_active, is_anonymous, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['title'],
                    $data['description'],
                    $data['category_id'],
                    $data['status'],
                    isset($data['is_active']) ? 1 : 0,
                    isset($data['is_anonymous']) ? 1 : 0,
                    $_SESSION['user_id']
                ]);
                $survey_id = $pdo->lastInsertId(); // Assign the new survey ID

                // Assign roles to the new survey
                foreach ($data['target_roles'] as $role_id) {
                    $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$survey_id, $role_id]);
                }
            }

            // Save questions (survey_fields table)
            $stmt = $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
            $stmt->execute([$data['id'] ?? $survey_id]); // Use $survey_id for new surveys
            if (!empty($data['questions'])) {
                $stmt = $pdo->prepare("INSERT INTO survey_fields (survey_id, field_label, field_type, field_options, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                $display_order = 0;
                foreach ($data['questions'] as $index => $question) {
                    $options = in_array($data['field_types'][$index], ['radio', 'checkbox', 'select']) 
                                ? json_encode(explode("\n", $data['options'][$index])) 
                                : null;
                    $stmt->execute([
                        $data['id'] ?? $survey_id, // Use $survey_id for new surveys
                        $question,
                        $data['field_types'][$index],
                        $options,
                        isset($data['required'][$index]) ? 1 : 0,
                        $display_order++
                    ]);
                }
            }

            $pdo->commit();
            $_SESSION['success'] = isset($data['id']) ? "Survey updated!" : "Survey created!";
            return ['success' => true];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error saving survey: " . $e->getMessage();
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();

            // Save survey main data
            $surveyData = [
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'category_id' => $_POST['category_id'],
                'status' => $_POST['status'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
                'target_roles' => json_encode($_POST['target_roles'] ?? []),
                'created_by' => $_SESSION['user_id'],
                'starts_at' => date('Y-m-d H:i:s'), // Adjust as needed
                'ends_at' => date('Y-m-d H:i:s', strtotime('+1 month')) // Adjust as needed
            ];

            if ($survey_id) {
                $stmt = $pdo->prepare("UPDATE surveys SET title=?, description=?, category_id=?, status=?, is_active=?, is_anonymous=?, target_roles=? WHERE id=?");
                $stmt->execute(array_values(array_merge($surveyData, [$survey_id])));
            } else {
                $stmt = $pdo->prepare("INSERT INTO surveys (title, description, category_id, status, is_active, is_anonymous, target_roles, created_by, starts_at, ends_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(array_values($surveyData));
                $survey_id = $pdo->lastInsertId();
            }

            // Save target roles (survey_roles table)
            $pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?")->execute([$survey_id]);
            if (!empty($_POST['target_roles'])) {
                $stmt = $pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                foreach ($_POST['target_roles'] as $role_id) {
                    $stmt->execute([$survey_id, $role_id]);
                }
            }

            // Save questions (survey_fields table)
            $pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?")->execute([$survey_id]);
            if (!empty($_POST['questions'])) {
                $stmt = $pdo->prepare("INSERT INTO survey_fields (survey_id, field_label, field_type, field_options, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                $display_order = 0;
                foreach ($_POST['questions'] as $index => $question) {
                    $options = in_array($_POST['field_types'][$index], ['radio', 'checkbox', 'select']) 
                                ? json_encode(explode("\n", $_POST['options'][$index])) 
                                : null;
                    $stmt->execute([
                        $survey_id,
                        $question,
                        $_POST['field_types'][$index],
                        $options,
                        isset($_POST['required'][$index]) ? 1 : 0,
                        $display_order++
                    ]);
                }
            }

            $pdo->commit();
            $_SESSION['success'] = $survey_id ? "Survey updated!" : "Survey created!";
            header("Location: surveys.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error saving survey: " . $e->getMessage();
        }
    } // Close save method

    public function deleteSurvey($survey_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM surveys WHERE id = ?");
            $stmt->execute([$survey_id]);
            $_SESSION['success'] = "Survey deleted!";
            header("Location: surveys.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting survey: " . $e->getMessage();
        }
    }
} // Close Survey class
