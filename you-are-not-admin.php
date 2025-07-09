<?php 
include_once 'config.php';
require_once 'require_login.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Access Only</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #e3f2fd, #bbdefb);
            color: #2c3e50;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: #ffffff;
            color: #2c3e50;
            padding: 40px 50px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
        }

        h1 {
            color: #1565c0;
            margin-bottom: 20px;
            font-weight: 400;
        }

        p {
            font-size: 18px;
            font-weight: 300;
            color: #546e7a;
        }

        .btn {
            margin-top: 25px;
            padding: 10px 20px;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 300;
        }

        .btn:hover {
            background-color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Access Denied</h1>
        <p>Sorry, only an admin can access this page.</p>
        <a class="btn" href="/<?= $project_path ?>/index.php">Return to Home</a>
    </div>
</body>
</html>