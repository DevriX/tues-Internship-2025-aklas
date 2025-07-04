<?php
require 'dbconn.php';

$user_logged_in = false;
$user_id = null;

$update_success = false;

$first_name = $last_name = $email = $phone = $description = $company_name = $company_site = '';

// Check login token
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);

    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.description, u.company_name, u.company_site
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $phone, $description, $company_name, $company_site);
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
?>

<?php if ($user_logged_in): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="./css/master.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .popup-success {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }

        .popup-success.hide {
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="site-wrapper">
    <header class="site-header">
        <div class="row site-header-inner">
            <div class="site-header-branding">
                <h1 class="site-title"><a href="/tues-Internship-2025-aklas/index.php">Job Offers</a></h1>
            </div>
            <nav class="site-header-navigation">
                <ul class="menu">
                    <li class="menu-item"><a href="/tues-Internship-2025-aklas/index.php">Home</a></li>
                    <li class="menu-item"><a href="/tues-Internship-2025-aklas/dashboard.php">Dashboard</a></li>
                    <li class="menu-item current-menu-item"><a href="/tues-Internship-2025-aklas/profile.php">My Profile</a></li>
                    <li class="menu-item"><a href="/tues-Internship-2025-aklas/logout.php">Sign Out</a></li>
                </ul>
            </nav>
        </div>
    </header>

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
