<?php
/**
 * Database Configuration
 * 
 * This file contains the database configuration settings.
 * It is used to establish a connection to the database.
 * 
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * 
 * @package School-CRM
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_crm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
