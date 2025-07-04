<?php
require 'dbconn.php';

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

    if (!$first_name) $errors[] = "First name is required.";
    if (!$last_name) $errors[] = "Last name is required.";
    if (!$email || !isValidEmail($email)) $errors[] = "Valid email is required.";
    if (!$password || !isValidPassword($password)) $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, and special character.";
    if ($password !== $repeat_password) $errors[] = "Passwords do not match.";
    if ($phone && !isValidPhoneBG($phone)) $errors[] = "Phone number is invalid. Use Bulgarian format.";
    if (!isValidURL($company_site)) $errors[] = "Company site URL is invalid.";

    if (empty($errors)) {
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered.";
        }
        $stmt->close();

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $is_admin = preg_match('/@devrix\.com$/i', $email) ? 1 : 0;
            $verification_token = bin2hex(random_bytes(16));
            $verified = 0;

            $stmt = $connection->prepare("INSERT INTO users 
                (first_name, last_name, email, password, phone_number, is_admin, description, created_at, company_name, company_site, verified, verification_token) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");

            $stmt->bind_param(
                "sssssisssis",
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
                $verification_token
            );

            if ($stmt->execute()) {
                header('Location: login.php');
                exit;
            } else {
                $errors[] = "Database error: Could not register user.";
            }
            $stmt->close();
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'register.php' && $current_page !== 'login.php'):
?>
<nav class="footer-vertical-menu">
    <button class="menu-toggle-arrow" aria-label="Toggle menu">
		<svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" stroke="#222" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</button>
	<a href="/tues-Internship-2025-aklas/index.php" class="footer-vlink<?php if($current_page == 'index.php') echo ' active'; ?>">Home</a>
	<a href="/tues-Internship-2025-aklas/dashboard.php" class="footer-vlink<?php if($current_page == 'dashboard.php') echo ' active'; ?>">Jobs Dashboard</a>
	<a href="/tues-Internship-2025-aklas/submissions.php" class="footer-vlink<?php if($current_page == 'submissions.php') echo ' active'; ?>">Submissions</a>
	<a href="/tues-Internship-2025-aklas/view-submission.php" class="footer-vlink<?php if($current_page == 'view-submission.php') echo ' active'; ?>">View Submission</a>
	<a href="/tues-Internship-2025-aklas/create-job.php" class="footer-vlink<?php if($current_page == 'create-job.php') echo ' active'; ?>">Create-Edit Job</a>
	<a href="/tues-Internship-2025-aklas/category-dashboard.php" class="footer-vlink<?php if($current_page == 'category-dashboard.php') echo ' active'; ?>">Category Dashboard</a>
	<a href="/tues-Internship-2025-aklas/profile.php" class="footer-vlink<?php if($current_page == 'profile.php') echo ' active'; ?>">My Profile</a>
    <a href="/tues-Internship-2025-aklas/logout.php" class="footer-vlink<?php if($current_page == 'logout.php') echo ' active'; ?>">Logout</a>
	<a href="/tues-Internship-2025-aklas/register.php" class="footer-vlink<?php if($current_page == 'register.php') echo ' active'; ?>">Register</a>
</nav>
<?php endif; ?> 

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

                        <form action="register.php" method="POST" novalidate>
                            <div class="flex-container justified-horizontally">
                                <div class="primary-container">
                                    <h4 class="form-title">About me</h4>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="first_name" placeholder="First Name*" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="last_name" placeholder="Last Name*" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="email" name="email" placeholder="Email*" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="password" name="password" placeholder="Password*" required>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="password" name="repeat_password" placeholder="Repeat Password*" required>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="tel" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="secondary-container">
                                    <h4 class="form-title">My Company</h4>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="company_name" placeholder="Company Name" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="url" name="company_site" placeholder="Company Site" value="<?= htmlspecialchars($_POST['company_site'] ?? '') ?>">
                                    </div>
                                    <div class="form-field-wrapper">
                                        <textarea name="description" placeholder="Description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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
