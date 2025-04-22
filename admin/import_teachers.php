<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
    $file = fopen($_FILES['import_file']['tmp_name'], 'r');
    $header = fgetcsv($file);

    $required = ['username', 'email'];
    $success = 0;
    $fail = 0;
    $rowNum = 1;

    while (($row = fgetcsv($file)) !== false) {
        $rowNum++;
        $data = array_combine($header, $row);

        // Basic validation
        if (empty($data['username']) || empty($data['email'])) {
            $fail++;
            continue;
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Create user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, role_id, created_at) VALUES (?, ?, (SELECT id FROM roles WHERE LOWER(role_name) = 'teacher' LIMIT 1), NOW())");
            $stmt->execute([$data['username'], $data['email']]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $user['id'];
        }

        // Get class_id if class_name provided
        $classId = null;
        if (!empty($data['class_name'])) {
            $stmt = $pdo->prepare("SELECT id FROM classes WHERE class_name = ?");
            $stmt->execute([$data['class_name']]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($class) {
                $classId = $class['id'];
            }
        }

        // Check if teacher record exists
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            // Update teacher
            $stmt = $pdo->prepare("UPDATE teachers SET phone=?, address=?, date_of_birth=?, gender=?, qualification=?, subject_specialization=?, status=?, class_id=? WHERE user_id=?");
            $stmt->execute([
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null,
                $data['qualification'] ?? null,
                $data['subject_specialization'] ?? null,
                $data['status'] ?? null,
                $classId,
                $userId
            ]);
        } else {
            // Insert teacher
            $stmt = $pdo->prepare("INSERT INTO teachers (user_id, phone, address, date_of_birth, gender, qualification, subject_specialization, status, class_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $userId,
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null,
                $data['qualification'] ?? null,
                $data['subject_specialization'] ?? null,
                $data['status'] ?? null,
                $classId
            ]);
        }
        $success++;
    }
    fclose($file);

    header("Location: teachers.php?imported=$success&failed=$fail");
    exit;
} else {
    header("Location: teachers.php?error=1");
    exit;
}
