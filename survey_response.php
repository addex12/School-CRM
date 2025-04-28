<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
require_once __DIR__ . '/includes/db.php';

// Check if the database connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch survey responses for public surveys
$sql = "SELECT sr.id, s.title AS survey_title, sr.submitted_at, sr.answers 
        FROM survey_responses sr
        JOIN surveys s ON sr.survey_id = s.id
        WHERE s.is_public = 1";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Survey Responses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f4f4f4;
        }
        .buttons {
            margin-bottom: 20px;
        }
        .buttons a {
            text-decoration: none;
            padding: 10px 15px;
            margin-right: 10px;
            background-color: #007BFF;
            color: white;
            border-radius: 5px;
        }
        .buttons a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="buttons">
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
    </div>
    <h1>Public Survey Responses</h1>
    <table>
        <thead>
            <tr>
                <th>Survey Title</th>
                <th>Submitted At</th>
                <th>Answers</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['survey_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['submitted_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['answers']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No responses found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close();
?>
