<?php
session_start();
require 'dbconn.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    }

    if (!$password) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);
                $expiry = date('Y-m-d H:i:s', time() + 7 * 86400); // 7 days

                // Store hashed token in DB
                $stmt2 = $connection->prepare("INSERT INTO login_tokens (user_id, token_hash, expiry) VALUES (?, ?, ?)");
                $stmt2->bind_param("iss", $user_id, $token_hash, $expiry);
                $stmt2->execute();

                // Set token cookie
                setcookie('login_token', $token, time() + (86400 * 7), "/", "", true, true); // Secure, HTTP-only

                $_SESSION['user_id'] = $user_id;
                header('Location: index.php');
                exit;
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "Email not found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="./css/master.css" />
</head>
<body>
    <div class="site-wrapper">
        <header class="site-header">
            <div class="row site-header-inner">
                <div class="site-header-branding">
                    <h1 class="site-title"><a href="index.php">Job Offers</a></h1>
                </div>
                <nav class="site-header-navigation">
                    <ul class="menu">
                        <li class="menu-item"><a href="index.php">Home</a></li>
                        <li class="menu-item"><a href="register.php">Register</a></li>
                        <li class="menu-item current-menu-item"><a href="login.php">Login</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main class="site-main">
            <section class="section-fullwidth section-login">
                <div class="row">
                    <div class="flex-container centered-vertically centered-horizontally">
                        <div class="form-box box-shadow">
                            <div class="section-heading">
                                <h2 class="heading-title">Login</h2>
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

                            <form action="login.php" method="POST" novalidate>
                                <div class="form-field-wrapper">
                                    <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                                </div>
                                <div class="form-field-wrapper">
                                    <input type="password" name="password" placeholder="Password" required />
                                </div>
                                <button type="submit" class="button">Login</button>
                            </form>
                            <a href="#" class="button button-inline">Forgot Password</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
