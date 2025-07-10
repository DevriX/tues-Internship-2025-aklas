<?php
require 'dbconn.php';
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.first_name
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($first_name);
        $stmt->fetch();
        $user_logged_in = true;
        $display_name = $first_name;
    }
    $stmt->close();
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidPassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/', $password);
}

function isValidPhoneBG($phone) {
    return preg_match('/^(\+359|0)\d{8,9}$/', $phone);
}

function isValidURL($url) {
    return empty($url) || filter_var($url, FILTER_VALIDATE_URL);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function sendVerificationEmail($toEmail, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME']; 
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($toEmail, 'TUES Internship');
        $mail->addAddress($toEmail);

        $verification_link = "http://localhost/tues-Internship-2025-aklas/twofa.php";

        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "
            <p>Your verification code is: <strong>$token</strong></p>
            <p>Or click <a href='$verification_link'>here</a> to verify your account.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $repeat_password = $_POST['repeat_password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $company_name = sanitize($_POST['company_name'] ?? '');
    $company_site = sanitize($_POST['company_site'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $company_role = sanitize($_POST['company_role'] ?? '');
    $company_image_path = null;

    // Basic validations
    if (!$first_name) $errors[] = "First name is required.";
    if (!$last_name) $errors[] = "Last name is required.";
    if (!$email || !isValidEmail($email)) $errors[] = "Valid email is required.";
    if (!$password || !isValidPassword($password)) $errors[] = "Password must include uppercase, lowercase, special char, and be 8+ chars.";
    if ($password !== $repeat_password) $errors[] = "Passwords do not match.";
    if ($phone && !isValidPhoneBG($phone)) $errors[] = "Invalid Bulgarian phone format.";
    if (!isValidURL($company_site)) $errors[] = "Invalid company site URL.";

    // Handle company image upload
    if (isset($_FILES['company_image']) && $_FILES['company_image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp = $_FILES['company_image']['tmp_name'];
        $image_name = basename($_FILES['company_image']['name']);
        $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($image_ext, $allowed_exts)) {
            $new_filename = uniqid('img_', true) . '.' . $image_ext;
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $company_image_path = $upload_dir . $new_filename;
            move_uploaded_file($image_tmp, $company_image_path);
        } else {
            $errors[] = "Invalid image file type.";
        }
    }

    // Check for existing email
    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) $errors[] = "Email is already registered.";
        $stmt->close();
    }

    // Register new user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $is_admin = preg_match('/@devrix\.com$/i', $email) ? 1 : 0;
        $verification_token = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $verification_token_hash = hash('sha256', $verification_token);
        $verified = 0;

        $stmt = $connection->prepare("INSERT INTO users 
            (first_name, last_name, email, password, phone_number, is_admin, description, created_at, company_name, company_site, verified, verification_token, company_role, company_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssisssssss",
            $first_name,
            $last_name,
            $email,
            $hashed_password,
            $phone,
            $is_admin,
            $description,
            $company_name,
            $company_site,
            $verified,
            $verification_token_hash,
            $company_role,
            $company_image_path
        );

        if ($stmt->execute()) {
            if (sendVerificationEmail($email, $verification_token)) {
                session_start();
                $_SESSION['2fa_email'] = $email;
                header("Location: twofa.php");
                exit;
            } else {
                $errors[] = "Registered, but failed to send verification email.";
            }
        } else {
            $errors[] = "Database error: Could not register user.";
        }
        $stmt->close();
    }
}

include 'header.php';
include 'auth-user.php';
include 'vertical-navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
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
                                <h2 class="heading-title">Register</h2>
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

                            <form action="register.php" method="POST" enctype="multipart/form-data" novalidate>
                                <div class="flex-container justified-horizontally">
                                    <div class="primary-container">
                                        <h4 class="form-title">About me</h4>
                                        <div class="form-field-wrapper"><input type="text" name="first_name" placeholder="First Name*" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"></div>
                                        <div class="form-field-wrapper"><input type="text" name="last_name" placeholder="Last Name*" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"></div>
                                        <div class="form-field-wrapper"><input type="email" name="email" placeholder="Email*" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
                                        <div class="form-field-wrapper"><input type="password" name="password" placeholder="Password*" required></div>
                                        <div class="form-field-wrapper"><input type="password" name="repeat_password" placeholder="Repeat Password*" required></div>
                                        <div class="form-field-wrapper"><input type="tel" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div>
                                    </div>

                                    <div class="secondary-container">
                                        <h4 class="form-title">My Company</h4>
                                        <div class="form-field-wrapper"><input type="text" name="company_name" placeholder="Company Name" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"></div>
                                        <div class="form-field-wrapper"><input type="url" name="company_site" placeholder="Company Site" value="<?= htmlspecialchars($_POST['company_site'] ?? '') ?>"></div>
                                        
                                        <div class="form-field-wrapper">
                                            <textarea 
                                                name="description" 
                                                placeholder="Description" 
                                                maxlength="250" 
                                                rows="1"
                                                class="smaller-textarea"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                        </div>

                                        <div class="form-field-wrapper custom-select-wrapper">
                                      
                                            <select name="company_role" id="company_role" class="custom-select" required>
                                                <option value="">Select Role</option>
                                                <option value="CEO" <?= ($_POST['company_role'] ?? '') === 'CEO' ? 'selected' : '' ?>>CEO</option>
                                                <option value="Manager" <?= ($_POST['company_role'] ?? '') === 'Manager' ? 'selected' : '' ?>>Manager</option>
                                                <option value="HR" <?= ($_POST['company_role'] ?? '') === 'HR' ? 'selected' : '' ?>>HR</option>
                                                <option value="Other" <?= ($_POST['company_role'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                            </select>
                                        </div>

                                        <div class="form-field-wrapper">
                                            <label for="company_image" class="custom-file-label">Upload Company Logo</label>
                                            <input type="file" name="company_image" id="company_image" class="custom-file-input" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                                <button class="button" type="submit">Register</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
