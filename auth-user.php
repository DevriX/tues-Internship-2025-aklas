<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconn.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($result);
}
$current_page = basename($_SERVER['PHP_SELF']);
?>