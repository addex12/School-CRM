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
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->pdo->beginTransaction();

            if (isset($data['id'])) {
                // Update existing survey
                $stmt = $this->pdo->prepare("
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
                $stmt = $this->pdo->prepare("DELETE FROM survey_roles WHERE survey_id = ?");
                $stmt->execute([$data['id']]);

                foreach ($data['target_roles'] as $role_id) {
                    $stmt = $this->pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$data['id'], $role_id]);
                }
            } else {
                // Create new survey
                $stmt = $this->pdo->prepare("
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
                $data['id'] = $this->pdo->lastInsertId();

                // Assign roles to the new survey
                foreach ($data['target_roles'] as $role_id) {
                    $stmt = $this->pdo->prepare("INSERT INTO survey_roles (survey_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$data['id'], $role_id]);
                }
            }

            // Save survey questions
            if (isset($data['questions'])) {
                $stmt = $this->pdo->prepare("DELETE FROM survey_fields WHERE survey_id = ?");
                $stmt->execute([$data['id']]);

                foreach ($data['questions'] as $index => $question) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO survey_fields 
                        (survey_id, field_type, question, options, is_required, sort_order) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $data['id'],
                        $data['field_types'][$index],
                        $question,
                        isset($data['options'][$index]) ? $data['options'][$index] : null,
                        isset($data['required'][$index]) ? 1 : 0,
                        $index
                    ]);
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'id' => $data['id']];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error saving survey: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
}
