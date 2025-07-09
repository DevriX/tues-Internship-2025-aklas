<?php
require_once 'require_login.php';
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
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: linear-gradient(to right, #ffffff, #fefcf8);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background-color: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .form-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
        }

        .form-container input[type="text"] {
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: border 0.3s ease;
        }

        .form-container input[type="text"]:focus {
            border-color: #667eea;
            outline: none;
        }

        .form-container button {
            padding: 12px;
            font-size: 16px;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-container button:hover {
            background-color: #556cd6;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Verify Your Email</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
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
