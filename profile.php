<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $userId = $_SESSION['user_id'];

    $query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $name, $email, $userId);

    if ($stmt->execute()) {
        $successMessage = "Profile updated successfully.";
    } else {
        $errorMessage = "Failed to update profile.";
    }
}

// Fetch user details
$userId = $_SESSION['user_id'];
$query = "SELECT name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile</title>
</head>
<body>
    <h1>Manage Your Profile</h1>
    <?php if (isset($successMessage)) echo "<p style='color: green;'>$successMessage</p>"; ?>
    <?php if (isset($errorMessage)) echo "<p style='color: red;'>$errorMessage</p>"; ?>
    <form method="POST">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        <br>
        <button type="submit">Update Profile</button>
    </form>
</body>
</html>
