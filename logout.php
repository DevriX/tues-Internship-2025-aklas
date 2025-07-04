<?php
session_start();
require 'dbconn.php';

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);

    // Delete from DB
    $stmt = $connection->prepare("DELETE FROM login_tokens WHERE token_hash = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
}

// Clear all session variables
session_unset();
// Destroy the session
session_destroy();

// Clear cookie
setcookie('login_token', '', time() - 3600, "/", "", true, true);

header('Location: index.php');
exit;
