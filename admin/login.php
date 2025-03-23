<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../styles.css"> <!-- Link to your CSS file -->
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

    <?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        require_once '../config/db_config.php';

        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    // Successful login
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['username'] = $username;
                    header("Location: dashboard.php"); // Redirect to admin dashboard
                    exit();
                } else {
                    // Failed login
                    echo "<p>Invalid username or password.</p>";
                }
            } else {
                // Failed login
                echo "<p>Invalid username or password.</p>";
            }

            $stmt->close();
        } else {
            echo "<p>Database query failed: " . $conn->error . "</p>";
        }

        $conn->close();
    }
    ?>
</body>
</html>

