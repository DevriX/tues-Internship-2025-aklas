<?php
require_once 'require_login.php';
require 'dbconn.php';

$user_logged_in = false;
$user_id = null;
$update_success = false;

$first_name = $last_name = $email = $phone = $description = $company_name = $company_site = $company_role = $company_image = $profile_image = '';
$is_admin = 0;

// Login token check
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);

    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.description, u.company_name, u.company_site, u.is_admin, u.company_role, u.company_image, u.profile_image
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $phone, $description, $company_name, $company_site, $is_admin, $company_role, $company_image, $profile_image);
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

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = basename($_FILES['profile_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('profile_', true) . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_filename;
            move_uploaded_file($file_tmp, $upload_path);
            $profile_image = $upload_path;
        }
    }

    // Handle company image upload
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
            company_image = ?,
            profile_image = ?
        WHERE id = ?
    ");
    $update->bind_param("sssssssssi", $first_name, $last_name, $email, $phone, $description, $company_name, $company_site, $company_image, $profile_image, $user_id);
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f7f8fa; }
        .profile-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 32px rgba(80,0,120,0.10);
            padding: 2.5rem 2.5rem 2rem 2.5rem;
            max-width: 600px;
            margin: 2.5rem auto;
            position: relative;
        }
        .profile-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e0d7f7 0%, #f0e6ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #4b0082;
            box-shadow: 0 2px 12px rgba(80,0,120,0.08);
            margin: 0 auto 1.2rem auto;
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .profile-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: #2d1457;
            margin-bottom: 0.5rem;
        }
        .profile-section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #7c3aed;
            margin-top: 1.5rem;
            margin-bottom: 0.7rem;
        }
        .profile-form-field {
            margin-bottom: 1.1rem;
        }
        .profile-form-field input,
        .profile-form-field textarea {
            width: 100%;
            padding: 0.85rem 1.1rem;
            border: 1.5px solid #e0e0e0;
            border-radius: 0.7rem;
            font-size: 1.08rem;
            background: #fafaff;
            transition: border 0.18s;
        }
        .profile-form-field input:focus,
        .profile-form-field textarea:focus {
            border: 1.5px solid #7c3aed;
            outline: none;
            background: #fff;
        }
        .profile-save-btn {
            width: 100%;
            padding: 1rem 0;
            background: linear-gradient(90deg, #7c3aed 0%, #4b0082 100%);
            color: #fff;
            font-size: 1.2rem;
            font-weight: 600;
            border: none;
            border-radius: 1.2rem;
            margin-top: 1.2rem;
            cursor: pointer;
            box-shadow: 0 2px 12px rgba(80,0,120,0.08);
            transition: background 0.18s, transform 0.15s;
        }
        .profile-save-btn:hover {
            background: linear-gradient(90deg, #4b0082 0%, #7c3aed 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .popup-success {
            background: #d1fae5;
            color: #059669;
            border-radius: 0.7rem;
            padding: 1rem 1.5rem;
            text-align: center;
            font-weight: 600;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(80,0,120,0.07);
        }
        @media (max-width: 700px) {
            .profile-card { padding: 1.2rem 0.5rem; }
        }
    </style>
</head>
<body>

<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php if (!empty($profile_image)): ?>
                        <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile Image">
                    <?php else: ?>
                        <?= strtoupper(mb_substr($first_name,0,1).mb_substr($last_name,0,1)) ?>
                    <?php endif; ?>
                </div>
                <div class="profile-title">My Profile</div>
                <?php if ($update_success): ?>
                    <div id="success-popup" class="popup-success">
                        Profile updated successfully.
                    </div>
                <?php endif; ?>
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <div class="profile-section-title">About Me</div>
                    <div class="profile-form-field">
                        <input type="text" name="first_name" placeholder="First Name*" value="<?= htmlspecialchars($first_name) ?>" required>
                    </div>
                    <div class="profile-form-field">
                        <input type="text" name="last_name" placeholder="Last Name*" value="<?= htmlspecialchars($last_name) ?>" required>
                    </div>
                    <div class="profile-form-field">
                        <input type="email" name="email" placeholder="Email*" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="profile-form-field">
                        <input type="text" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($phone) ?>">
                    </div>
                    <div class="profile-section-title">Profile Image</div>
                    <div class="profile-form-field">
                        <input type="file" name="profile_image" accept="image/*">
                        <?php if (!empty($profile_image)): ?>
                            <img src="<?= htmlspecialchars($profile_image) ?>" alt="Current Profile Image" style="width: 60px; height: 60px; border-radius: 50%; margin-top: 0.7rem;">
                        <?php endif; ?>
                    </div>
                    <div class="profile-section-title">My Company</div>
                    <div class="profile-form-field">
                        <input type="text" name="company_name" placeholder="Company Name" value="<?= htmlspecialchars($company_name) ?>">
                    </div>
                    <div class="profile-form-field">
                        <input type="url" name="company_site" placeholder="Company Site" value="<?= htmlspecialchars($company_site) ?>">
                    </div>
                    <div class="profile-form-field">
                        <textarea name="description" placeholder="Brief Description (max 250 chars)" maxlength="250" rows="2"><?= htmlspecialchars($description) ?></textarea>
                    </div>
                    <div class="profile-form-field">
                        <input type="text" name="company_role" value="<?= htmlspecialchars($company_role) ?>" readonly disabled>
                    </div>
                    <div class="profile-form-field">
                        <label style="font-weight:500; color:#4b0082;">Company Image:</label>
                        <input type="file" name="company_image" accept="image/*">
                        <?php if (!empty($company_image)): ?>
                            <img src="<?= htmlspecialchars($company_image) ?>" alt="Current Company Image" style="width: 60px; height: 60px; border-radius: 50%; margin-top: 0.7rem;">
                        <?php endif; ?>
                    </div>
                    <button class="profile-save-btn" type="submit">Save</button>
                </form>
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
