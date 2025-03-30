<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Get available surveys
$role = $_SESSION['role'] ?? 'guest'; // Provide a default value if 'role' is not set
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM survey_responses r 
            WHERE r.survey_id = s.id AND r.user_id = ?) as completed
    FROM surveys s
    WHERE s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
    AND JSON_CONTAINS(s.target_roles, JSON_QUOTE(?))
    ORDER BY s.ends_at ASC
");

// Execute the survey query
$stmt->execute([$_SESSION['user_id'], $role]);
$surveys = $stmt->fetchAll();

// Get completed surveys count
$completedCount = $pdo->prepare("
    SELECT COUNT(DISTINCT survey_id) 
    FROM survey_responses 
    WHERE user_id = ?
");
$completedCount->execute([$_SESSION['user_id']]);
$completedSurveys = $completedCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Updated styles for a more attractive layout */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            flex: 1;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
            color: #333;
        }
        .stat-card p {
            font-size: 1.5em;
            color: #3498db;
            font-weight: bold;
        }
        .quick-access {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .main-menu {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        .menu-item {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .menu-item:hover {
            background: #2980b9;
        }
        .survey-list {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .survey-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .survey-card {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .survey-card.completed {
            background: #d4edda;
        }
        .survey-card h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
            color: #333;
        }
        .survey-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
        }
        .survey-meta {
            font-size: 0.8em;
            color: #555;
        }
        .survey-status.completed {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #28a745;
            font-size: 1.2em;
        }
        .btn-primary {
            display: inline-block;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .no-surveys {
            text-align: center;
            color: #888;
            font-size: 1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <div class="stats-grid">