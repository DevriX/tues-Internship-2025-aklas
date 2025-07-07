<?php
require 'dbconn.php';

$user_logged_in = false;
$user_id = null;

$update_success = false;

$first_name = $last_name = $email = $phone = $description = $company_name = $company_site = '';
$is_admin = 0;

// Check login token
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);

    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.description, u.company_name, u.company_site, u.is_admin
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $phone, $description, $company_name, $company_site, $is_admin);
        $stmt->fetch();
        $user_logged_in = true;
    }

    $stmt->close();
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && $user_logged_in) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $company_site = trim($_POST['company_site'] ?? '');

    $update = $connection->prepare("
        UPDATE users SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone_number = ?, 
            description = ?, 
            company_name = ?, 
            company_site = ?
        WHERE id = ?
    ");
    $update->bind_param("sssssssi", $first_name, $last_name, $email, $phone, $description, $company_name, $company_site, $user_id);
    $update->execute();
    $update->close();

    $update_success = true;
}

if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Add user array for navbar logic
$user = [
    'id' => $user_id,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'is_admin' => $is_admin
];
$display_name = $first_name;
$current_page = basename($_SERVER['PHP_SELF']);
include 'header.php';
include 'vertical-navbar.php';


?>

<?php if ($user_logged_in): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="./css/master.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="row">
                <div class="flex-container centered-vertically centered-horizontally">
                    <div class="form-box box-shadow">
                        <div class="section-heading">
                            <h2 class="heading-title">My Profile</h2>
                        </div>

                        <?php if ($update_success): ?>
                            <div id="success-popup" class="popup-success">
                                Profile updated successfully.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="profile.php">
                            <div class="flex-container justified-horizontally">
                                <div class="primary-container">
                                    <h4 class="form-title">About me</h4>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="first_name" placeholder="First Name*" value="<?= htmlspecialchars($first_name) ?>" required/>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="last_name" placeholder="Last Name*" value="<?= htmlspecialchars($last_name) ?>" required/>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="email" name="email" placeholder="Email*" value="<?= htmlspecialchars($email) ?>" required/>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($phone) ?>" />
                                    </div>
                                </div>

                                <div class="secondary-container">
                                    <h4 class="form-title">My Company</h4>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="company_name" placeholder="Company Name" value="<?= htmlspecialchars($company_name) ?>" />
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="company_site" placeholder="Company Site" value="<?= htmlspecialchars($company_site) ?>" />
                                    </div>
                                    <div class="form-field-wrapper">
                                        <textarea name="description" placeholder="Description"><?= htmlspecialchars($description) ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <button class="button" type="submit">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
	<script src="main.js"></script>
</div>

<?php if ($update_success): ?>
    <script>
        const popup = document.getElementById('success-popup');
        setTimeout(() => {
            popup.classList.add('hide');
        }, 3000);
    </script>
<?php endif; ?>

</body>
</html>
<?php else: ?>
    <p>You are not logged in. <a href="login.php">Login here</a></p>
<?php endif; ?>