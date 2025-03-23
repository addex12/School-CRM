<?php
// filepath: /school-crm-db-setup/school-crm-db-setup/src/setup.php

include 'db_connection.php';

// Execute create_tables.sql
$createTablesSql = file_get_contents('../sql/create_tables.sql');
if ($conn->multi_query($createTablesSql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    echo "Error creating tables: " . $conn->error;
}

// Execute seed_data.sql
$seedDataSql = file_get_contents('../sql/seed_data.sql');
if ($conn->multi_query($seedDataSql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    echo "Error seeding data: " . $conn->error;
}

$conn->close();
echo "Database setup completed successfully.";
?>