<?php
require_once 'require_login.php';
require 'dbconn.php';

$user_logged_in = false;
$user_id = null;
$update_success = false;

$first_name = $last_name = $email = $phone = $description = $company_name = $company_site = $company_role = $company_image = '';
$is_admin = 0;

// Login token check
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);

    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.description, u.company_name, u.company_site, u.is_admin, u.company_role, u.company_image
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $phone, $description, $company_name, $company_site, $is_admin, $company_role, $company_image);
        $stmt->fetch();
        $user_logged_in = true;
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $user_logged_in) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $company_site = trim($_POST['company_site'] ?? '');

    // Handle image upload
    if (isset($_FILES['company_image']) && $_FILES['company_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['company_image']['tmp_name'];
        $file_name = basename($_FILES['company_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('img_', true) . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_filename;
            move_uploaded_file($file_tmp, $upload_path);
            $company_image = $upload_path;
        }
    }

    $update = $connection->prepare("
        UPDATE users SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone_number = ?, 
            description = ?, 
            company_name = ?, 
            company_site = ?,
            company_image = ?
        WHERE id = ?
    ");
    $update->bind_param("ssssssssi", $first_name, $last_name, $email, $phone, $description, $company_name, $company_site, $company_image, $user_id);
    $update->execute();
    $update->close();

    $update_success = true;
}

if (!$user_id) {
    header('Location: login.php');
    exit;
}

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

</head>
<body>

<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="row">
                <div class="flex-container centered-vertically centered-horizontally">
                    <div class="form-box">
                        <h2 class="heading-title">My Profile</h2>

                        <?php if ($update_success): ?>
                            <div id="success-popup" class="popup-success">
                                Profile updated successfully.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <div class="flex-container">
                                <div class="primary-container">
                                    <h4 class="form-title">About Me</h4>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="first_name" placeholder="First Name*" value="<?= htmlspecialchars($first_name) ?>" required>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="last_name" placeholder="Last Name*" value="<?= htmlspecialchars($last_name) ?>" required>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="email" name="email" placeholder="Email*" value="<?= htmlspecialchars($email) ?>" required>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($phone) ?>">
                                    </div>
                                </div>

                                <div class="secondary-container">
                                    <h4 class="form-title">My Company</h4>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="company_name" placeholder="Company Name" value="<?= htmlspecialchars($company_name) ?>">
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="url" name="company_site" placeholder="Company Site" value="<?= htmlspecialchars($company_site) ?>">
                                    </div>
                                    <div class="form-field-wrapper">
                                        <textarea name="description" placeholder="Brief Description (max 250 chars)" maxlength="250" class="smaller-textarea"><?= htmlspecialchars($description) ?></textarea>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <input type="text" name="company_role" value="<?= htmlspecialchars($company_role) ?>" readonly disabled>
                                    </div>
                                    <div class="form-field-wrapper">
                                        <label>Company Image:</label>
                                        <input type="file" name="company_image" accept="image/*">
                                        <?php if (!empty($company_image)): ?>
                                            <img src="<?= htmlspecialchars($company_image) ?>" alt="Current Image">
                                        <?php endif; ?>
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

<script>
    const popup = document.getElementById('success-popup');
    if (popup) {
        setTimeout(() => {
            popup.classList.add('hide');
        }, 3000);
    }

    
</script>

<script src="main.js"></script>

</body>
</html>
<?php else: ?>
    <p>You are not logged in. <a href="login.php">Login here</a></p>
<?php endif; ?>
