<?php
session_start();
require 'dbconn.php';

$errors = [];

if (!isset($_SESSION['2fa_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['2fa_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = trim($_POST['verification_code'] ?? '');

    if (!$entered_code || !ctype_digit($entered_code)) {
        $errors[] = "Please enter a valid 6-digit verification code.";
    } else {
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND verification_token = ?");
        $stmt->bind_param("ss", $email, $entered_code);
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
    <title>2FA Verification</title>
    <link rel="stylesheet" href="./css/master.css">
</head>
<body>
<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="row">
                <div class="flex-container centered-vertically centered-horizontally">
                    <div class="form-box box-shadow">
                        <div class="section-heading">
                            <h2 class="heading-title">Verify Your Email</h2>
                            <p>Please enter the 6-digit code sent to your email.</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div style="color:red; margin-bottom:1em;">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="twofa.php" method="POST">
                            <div class="form-field-wrapper">
                                <input type="text" name="verification_code" placeholder="6-digit code" required>
                            </div>
                            <button class="button" type="submit">Verify</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>