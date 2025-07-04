<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconn.php';

$user = null;
$is_logged_in = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($result);
    if ($user) {
        $is_logged_in = true;
    }
}
$current_page = basename($_SERVER['PHP_SELF']);
?>