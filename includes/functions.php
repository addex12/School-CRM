<?php
// Check if the autoload file exists
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Error: Composer autoload file not found. Please run 'composer install' in the project root directory.");
}

require_once $autoloadPath;

require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Register new user
function registerUser($username, $email, $password, $role = 'parent') {
    global $pdo;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $hashed_password, $role]);
    } catch (PDOException $e) {
        error_log("Error registering user: " . $e->getMessage());
        return false;
    }
}

// Login user
function loginUser($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return false;
    }
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Get all active surveys
function getActiveSurveys() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM surveys WHERE is_active = TRUE");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching active surveys: " . $e->getMessage());
        return [];
    }
}

// Get survey questions
function getSurveyQuestions($survey_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ?");
        $stmt->execute([$survey_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching survey questions: " . $e->getMessage());
        return [];
    }
}

// Apply system settings to the application
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit();
}
/**
 * Format date for display
 */
function formatDate($dateString, $format = 'M j, Y g:i A') {
    if (empty($dateString) || $dateString === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    $date = new DateTime($dateString);
    return $date->format($format);
}

if (!defined('DATETIMEFORMAT')) {
    define('DATETIMEFORMAT', 'Y-m-d H:i:s'); // Default datetime format
}

function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals);
}

function formatPhone($phone) {
    return preg_replace('/\D/', '', $phone);
}
function formatEmail($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}
function formatUrl($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}
function formatText($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
function formatHtml($html) {
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}
function formatJson($json) {
    return json_encode($json, JSON_PRETTY_PRINT);
}
function formatXml($xml) {
    return htmlspecialchars($xml, ENT_QUOTES, 'UTF-8');
}
function formatArray($array) {
    return implode(', ', $array);
}
function formatObject($object) {
    return json_encode($object, JSON_PRETTY_PRINT);
}
function formatBoolean($boolean) {
    return $boolean ? 'Yes' : 'No';
}
function formatCurrency($amount, $currency = 'USD') {
    return number_format($amount, 2) . ' ' . strtoupper($currency);
}
function formatPercentage($number, $decimals = 2) {
    return number_format($number, $decimals) . '%';
}
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Clean input data
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Log activity
 */
function log_activity($user_id, $activity_type, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log 
            (user_id, activity_type, description, ip_address) 
            VALUES (:user_id, :activity_type, :description, :ip_address)
        ");
        
        $stmt->execute([
            ':user_id' => $user_id,
            ':activity_type' => $activity_type,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
    header("Location: $url");
    exit();
}

/**
 * Get hashed password
 */
function get_hashed_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Execute a database query with error handling
 */
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database Query Error: " . $e->getMessage());
        $_SESSION['error'] = "A system error occurred. Please try again later.";
        header("Location: ../error.php");
        exit();
    }
}
function countUnreadNotifications(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
function logActivity(PDO $pdo, string $message, string $type, int $userId): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (message, type, user_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$message, $type, $userId]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
/**
 * Get icon class for field type
 */
function getFieldTypeIcon($type) {
    $icons = [
        'text' => 'fa-font',
        'textarea' => 'fa-align-left',
        'radio' => 'fa-dot-circle',
        'checkbox' => 'fa-check-square',
        'select' => 'fa-caret-square-down',
        'number' => 'fa-hashtag',
        'date' => 'fa-calendar-alt',
        'rating' => 'fa-star',
        'file' => 'fa-file-upload'
    ];
    return $icons[$type] ?? 'fa-question-circle';
}
/**
 * Build sort query string
 */
function buildSortQuery($column) {
    $query = $_GET;
    $currentSort = $_GET['sort'] ?? '';
    $currentOrder = $_GET['order'] ?? 'asc';
    
    if ($currentSort === $column) {
        $query['order'] = $currentOrder === 'asc' ? 'desc' : 'asc';
    } else {
        $query['sort'] = $column;
        $query['order'] = 'asc';
    }
    
    return http_build_query($query);
}

/**
 * Get color for survey status
 */
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'active': return '#2ecc71';
        case 'draft': return '#3498db';
        case 'inactive': return '#f39c12';
        case 'archived': return '#95a5a6';
        default: return '#7f8c8d';
    }
}
/**
 * Export data as CSV
 */
function exportAsCSV($data, $surveyTitle) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($surveyTitle) . '_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Export survey info
    fputcsv($output, ['Survey Information']);
    foreach ($data['survey'] as $key => $value) {
        fputcsv($output, [ucwords(str_replace('_', ' ', $key)), $value]);
    }
    fputcsv($output, []); // Empty row
    
    // Export questions if available
    if (isset($data['questions'])) {
        fputcsv($output, ['Survey Questions']);
        fputcsv($output, ['ID', 'Question Text', 'Field Type', 'Required', 'Display Order']);
        
        foreach ($data['questions'] as $question) {
            fputcsv($output, [
                $question['id'],
                $question['field_label'],
                $question['field_type'],
                $question['is_required'] ? 'Yes' : 'No',
                $question['display_order']
            ]);
        }
        fputcsv($output, []); // Empty row
    }
    
    // Export responses if available
    if (isset($data['responses'])) {
        fputcsv($output, ['Survey Responses']);
        
        // Prepare headers
        $headers = ['Response ID', 'Respondent', 'Submission Date'];
        if (isset($data['questions'])) {
            foreach ($data['questions'] as $question) {
                $headers[] = $question['field_label'];
            }
        }
        fputcsv($output, $headers);
        
        // Export each response
        foreach ($data['responses'] as $response) {
            $row = [
                $response['id'],
                $response['respondent_name'] ?? ($data['survey']['is_anonymous'] ? 'Anonymous' : 'N/A'),
                $response['submitted_at_formatted']
            ];
            
            if (isset($data['questions'])) {
                // Map answers to questions
                $answers = [];
                foreach ($response['answers'] as $answer) {
                    $answers[$answer['field_id']] = $answer['field_value'];
                }
                
                foreach ($data['questions'] as $question) {
                    $row[] = $answers[$question['id']] ?? '';
                }
            }
            
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

/**
 * Export data as JSON
 */
function exportAsJSON($data, $surveyTitle) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($surveyTitle) . '_export_' . date('Y-m-d') . '.json"');
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

/**
 * Export data as Excel (using PHPExcel or PhpSpreadsheet)
 */
function exportAsExcel($data, $surveyTitle) {
    // Check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        throw new Exception("PhpSpreadsheet library not installed");
    }
    
    require_once 'vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator($_SESSION['username'] ?? 'Admin')
        ->setTitle($surveyTitle . ' Export')
        ->setSubject('Survey Data Export')
        ->setDescription('Exported survey data from ' . date('Y-m-d'));
    
    // Add survey information
    $sheet->setTitle('Survey Info');
    $sheet->setCellValue('A1', 'Survey Information');
    $sheet->mergeCells('A1:B1');
    $sheet->getStyle('A1')->getFont()->setBold(true);
    
    $row = 2;
    foreach ($data['survey'] as $key => $value) {
        $sheet->setCellValue('A' . $row, ucwords(str_replace('_', ' ', $key)));
        $sheet->setCellValue('B' . $row, $value);
        $row++;
    }
    $row++;
    
    // Add questions if available
    if (isset($data['questions'])) {
        $sheet->setCellValue('A' . $row, 'Survey Questions');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'ID');
        $sheet->setCellValue('B' . $row, 'Question Text');
        $sheet->setCellValue('C' . $row, 'Field Type');
        $sheet->setCellValue('D' . $row, 'Required');
        $sheet->setCellValue('E' . $row, 'Display Order');
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
        $row++;
        
        foreach ($data['questions'] as $question) {
            $sheet->setCellValue('A' . $row, $question['id']);
            $sheet->setCellValue('B' . $row, $question['field_label']);
            $sheet->setCellValue('C' . $row, $question['field_type']);
            $sheet->setCellValue('D' . $row, $question['is_required'] ? 'Yes' : 'No');
            $sheet->setCellValue('E' . $row, $question['display_order']);
            $row++;
        }
    }
    
    // Add responses if available
    if (isset($data['responses'])) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Responses');
        
        $row = 1;
        $sheet->setCellValue('A' . $row, 'Survey Responses');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // Prepare headers
        $headers = ['Response ID', 'Respondent', 'Submission Date'];
        if (isset($data['questions'])) {
            foreach ($data['questions'] as $question) {
                $headers[] = $question['field_label'];
            }
        }
        
        // Write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':' . $col . $row)->getFont()->setBold(true);
        $row++;
        
        // Write response data
        foreach ($data['responses'] as $response) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $response['id']);
            $sheet->setCellValue($col++ . $row, $response['respondent_name'] ?? ($data['survey']['is_anonymous'] ? 'Anonymous' : 'N/A'));
            $sheet->setCellValue($col++ . $row, $response['submitted_at_formatted']);
            
            if (isset($data['questions'])) {
                // Map answers to questions
                $answers = [];
                foreach ($response['answers'] as $answer) {
                    $answers[$answer['field_id']] = $answer['field_value'];
                }
                
                foreach ($data['questions'] as $question) {
                    $sheet->setCellValue($col++ . $row, $answers[$question['id']] ?? '');
                }
            }
            
            $row++;
        }
    }
    
    // Set the first sheet as active
    $spreadsheet->setActiveSheetIndex(0);
    
    // Generate file
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($surveyTitle) . '_export_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit();
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $filename);
    return substr($filename, 0, 50);
}

function fetchAllUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all users: " . $e->getMessage());
        return [];
    }
}
function personalizeMessage($message, $recipient) {
    $placeholders = [
        '{name}' => $recipient['name'] ?? 'User',
        '{email}' => $recipient['email'] ?? '',
        '{username}' => $recipient['username'] ?? ''
    ];
    return strtr($message, $placeholders);
}
function getUsersByRole(PDO $pdo, int $role_id): array {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role_id = :role_id");
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching users by role: " . $e->getMessage());
        return [];
    }
}
function getAllUsers(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT id, email, name, username FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all users: " . $e->getMessage());
        return [];
    }
}

function sendEmail($to, $subject, $body, $from = 'gizawadugna@gmail.com', $fromName = 'Admin') {
    $mail = new PHPMailer(true);

    try {
        // Enable verbose debug output
        $mail->SMTPDebug = 2; // Set to 2 for detailed debug output
        $mail->Debugoutput = function ($str, $level) {
            error_log("PHPMailer [Level $level]: $str");
        };

        // Use the system's default mail configuration
        $mail->isMail();

        // Recipients
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send email
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent to $to. Error: " . $mail->ErrorInfo);
        return false;
    }
}