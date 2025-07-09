<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'dbconn.php';
$user_id = $_SESSION['user_id'];
$stmt = $connection->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($is_admin);
$stmt->fetch();
$stmt->close();
if (!$is_admin) {
    // Optionally, you can redirect or show a 403 error
    header('Location: you-are-not-admin.php');
    exit();
} 