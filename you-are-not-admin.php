<?php 
include_once 'config.php';
require_once 'require_login.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Access Only</title>
    <link rel="stylesheet" href="./css/master.css">
</head>
<body class="admin-denied-wrapper">
    <div class="container">
        <h1>Access Denied</h1>
        <p>Sorry, only an admin can access this page.</p>
        <a class="btn" href="/<?= $project_path ?>/index.php">Return to Home</a>
    </div>
</body>
</html>
