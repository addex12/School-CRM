<?php
class Survey {
    private static $pdo;
    private $pdoInstance;
    
    public function __construct($pdo) {
        $this->pdoInstance = $pdo;
        self::$pdo = $pdo;
    }

    public static function model($pdo) {
        return new self($pdo);
    }

    public static function getStatuses() {
        try {
            $stmt = self::$pdo->query("SELECT status, label, icon FROM survey_statuses ORDER BY id");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching survey statuses: " . $e->getMessage());
            return [];
        }
    }

    public function findByPk($id) {
        try {
            $stmt = $this->pdoInstance->prepare("
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
            $stmt = $this->pdoInstance->prepare("
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
            $stmt = $this->pdoInstance->prepare("
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
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->pdoInstance->beginTransaction();

            if (isset($data['id'])) {
                // Update existing survey
                $stmt = $this->pdoInstance->prepare("
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
                $stmt = $this->pdoInstance->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
                $stmt->execute([$data['id']]);

                foreach ($data['target_roles'] as $role_id) {
                    $stmt = $this->pdoInstance->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$data['id'], $role_id]);
                }
            } else {
                // Create new survey
                $stmt = $this->pdoInstance->prepare("
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
                $survey_id = $this->pdoInstance->lastInsertId(); // Assign the new survey ID

                // Assign roles to the new survey
                foreach ($data['target_roles'] as $role_id) {
                    $stmt = $this->pdoInstance->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$survey_id, $role_id]);
                }
            }

            // Save questions (survey_fields table)
            $stmt = $this->pdoInstance->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
            $stmt->execute([$data['id'] ?? $survey_id]); // Use $survey_id for new surveys
            if (!empty($data['questions'])) {
                $stmt = $this->pdoInstance->prepare("INSERT INTO survey_fields (survey_id, field_label, field_type, field_options, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
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

            $this->pdoInstance->commit();
            $_SESSION['success'] = isset($data['id']) ? "Survey updated!" : "Survey created!";
            return ['success' => true];
        } catch (Exception $e) {
            $this->pdoInstance->rollBack();
            $_SESSION['error'] = "Error saving survey: " . $e->getMessage();
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    public function saveSurvey() {
        try {
            $this->pdoInstance->beginTransaction();

            // Save survey main data
            $surveyData = [
                'title' => trim($_POST['title']),
            ];

            $stmt = $this->pdoInstance->prepare("INSERT INTO surveys (title) VALUES (?)");
            $stmt->execute([$surveyData['title']]);
            $survey_id = $this->pdoInstance->lastInsertId();

            // Save questions and answers
            foreach ($_POST['questions'] as $question) {
                $stmt = $this->pdoInstance->prepare("INSERT INTO survey_questions (survey_id, question_text) VALUES (?, ?)");
                $stmt->execute([$survey_id, $question]);
                $question_id = $this->pdoInstance->lastInsertId();

                if (isset($_POST['answers'][$question_id])) {
                    foreach ($_POST['answers'][$question_id] as $answer) {
                        $stmt = $this->pdoInstance->prepare("INSERT INTO survey_answers (question_id, answer_text) VALUES (?, ?)");
                        $stmt->execute([$question_id, $answer]);
                    }
                }
            }

            $this->pdoInstance->commit();
            $_SESSION['success'] = "Survey saved successfully!";
            return ['success' => true];
        } catch (Exception $e) {
            $this->pdoInstance->rollBack();
            $_SESSION['error'] = "Error saving survey: " . $e->getMessage();
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    public function deleteSurvey($survey_id) {
        try {
            $stmt = $this->pdoInstance->prepare("DELETE FROM surveys WHERE id = ?");
            $stmt->execute([$survey_id]);
            $_SESSION['success'] = "Survey deleted!";
            header("Location: surveys.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting survey: " . $e->getMessage();
        }
    }
} // Close Survey class
?>
