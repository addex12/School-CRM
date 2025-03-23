// Author: Adugna Gizaw
// Email: gizawadugna@gmail.com
// Phone: +251925582067

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School-CRM</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .feature {
            flex: 1 1 calc(33.333% - 1rem);
            background-color: #f9f9f9;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .feature h2 {
            color: #4CAF50;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1rem;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome to School-CRM</h1>
        <p>Your comprehensive solution for managing educational institutions</p>
    </header>
    <div class="container">
        <h1>Features</h1>
        <div class="features">
            <div class="feature">
                <h2>Student Information Management</h2>
                <p>Manage student records efficiently and effectively.</p>
            </div>
            <div class="feature">
                <h2>Academic Progress Tracking</h2>
                <p>Track and monitor students' academic performance.</p>
            </div>
            <div class="feature">
                <h2>Attendance Management</h2>
                <p>Keep track of student attendance with ease.</p>
            </div>
            <div class="feature">
                <h2>Communication Tools</h2>
                <p>Facilitate communication between students, parents, and staff.</p>
            </div>
            <div class="feature">
                <h2>Reporting and Analytics</h2>
                <p>Generate insightful reports and analytics.</p>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2023 School-CRM. All rights reserved.</p>
    </footer>
</body>
</html>
