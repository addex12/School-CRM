<?php
define('DB_HOST', 'your_db_host');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');

function createParentSurveysTable($conn) {
    $sql = "
    CREATE TABLE IF NOT EXISTS parent_surveys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";

    if ($conn->query($sql) === TRUE) {
        echo "Parent surveys table created successfully.";
    } else {
        echo "Error creating parent surveys table: " . $conn->error;
    }
}
?>