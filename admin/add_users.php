<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = "Add New User";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = bin2hex(random_bytes(8)); // Generate random password
    
    try {
        // Check for existing user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Email already exists");
        }

        // Insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $role, $hashedPassword]);

        // Send email
        $subject = "Your New Account";
        $message = "Your login credentials:\n\nUsername: $username\nPassword: $password";
        sendEmail($email, $subject, $message);

        $_SESSION['success'] = "User created and notification sent!";
        header("Location: users.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get roles
$roles = $pdo->query("SELECT role_name FROM roles")->fetchAll();

// Email sending function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $message) {
    require '../includes/PHPMailer/src/Exception.php';
    require '../includes/PHPMailer/src/PHPMailer.php';
    require '../includes/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com'; // Replace with your email
        $mail->Password = 'your_password'; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('your_email@example.com', 'School CRM'); // Replace with your email and name
        $mail->addAddress($to);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>

<!DOCTYPE html>
<html>
<!-- Add user form with dynamic role dropdown -->