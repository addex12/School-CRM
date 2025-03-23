<?php
// Author: Adugna Gizaw
// Email: gizawadugna@gmail.com
// Phone: +251925582067

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'];
    $db_name = $_POST['db_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        $message = "Connection failed: " . $conn->connect_error;
    } else {
        $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
        if ($conn->query($sql) === TRUE) {
            $message = "Database created successfully";
        } else {
            $message = "Error creating database: " . $conn->error;
        }

        $conn->close();

        $config_content = "<?php\n";
        $config_content .= "define('DB_HOST', '$host');\n";
        $config_content .= "define('DB_USER', '$username');\n";
        $config_content .= "define('DB_PASS', '$password');\n";
        $config_content .= "define('DB_NAME', '$db_name');\n";
        $config_content .= "?>";

        if (file_put_contents('includes/config.php', $config_content)) {
            $message .= "<br>Configuration file created successfully.";
        } else {
            $message .= "<br>Error creating configuration file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install School-CRM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            color: #555;
        }
        input {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #e7f3e7;
            border: 1px solid #d4e5d4;
            color: #3c763d;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Install School-CRM</h1>
        <form method="POST" action="">
            <label for="host">Database Host</label>
            <input type="text" id="host" name="host" required>

            <label for="db_name">Database Name</label>
            <input type="text" id="db_name" name="db_name" required>

            <label for="username">Database Username</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Database Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Install</button>
        </form>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
