<?php
session_start();
require 'dbconn.php';

$errors = [];

if (!isset($_SESSION['2fa_email']) && isset($_GET['email'])) {
    $_SESSION['2fa_email'] = $_GET['email'];
}

if (!isset($_SESSION['2fa_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['2fa_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = trim($_POST['verification_code'] ?? '');

    if (!$entered_code || !ctype_digit($entered_code) || strlen($entered_code) !== 6) {
        $errors[] = "Please enter a valid 6-digit verification code.";
    } else {
        $entered_code_hash = hash('sha256', $entered_code);

        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND verification_token = ?");
        $stmt->bind_param("ss", $email, $entered_code_hash);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt = $connection->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            unset($_SESSION['2fa_email']);
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Invalid verification code.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Verification</title>
    <link rel="stylesheet" href="./css/master.css">
</head>
<body class="twofa-wrapper">
    <div class="twofa-form-container">
        <h2>Verify Your Email</h2>

        <?php if (!empty($errors)): ?>
            <div class="twofa-error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="twofa.php<?= isset($_GET['email']) ? '?email=' . urlencode($_GET['email']) : '' ?>">
            <input type="text" name="verification_code" placeholder="Enter 6-digit code" maxlength="6" required>
            <button type="submit">Verify</button>
        </form>
        
    </div>
</body>
</html>
