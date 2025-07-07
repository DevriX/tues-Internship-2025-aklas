<?php
require 'dbconn.php';
$user_logged_in = false;
$display_name = '';
$user = null;
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.is_admin
        FROM login_tokens lt
        JOIN users u ON lt.user_id = u.id
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $is_admin);
        $stmt->fetch();
        $user_logged_in = true;
        $display_name = $first_name;
        $user = [
            'id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'is_admin' => $is_admin
        ];
    }
    $stmt->close();
}

include 'header.php';
include 'vertical-navbar.php';

$user_id = null;
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("SELECT user_id FROM login_tokens WHERE token_hash = ? AND expiry > NOW() LIMIT 1");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
    }
    $stmt->close();
}

if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Get categories
$categories = [];
$cat_result = mysqli_query($connection, "SELECT id, name FROM categories");
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row;
}

// Form data
$job_title = $_POST['job-title'] ?? null;
$location = $_POST['location'] ?? null;
$salary_raw = $_POST['salary'] ?? null;
$salary = str_replace(' ', '', trim($salary_raw));
$description = $_POST['description'] ?? null;
$category_id = $_POST['category'] ?? null;
$created_at = date('Y-m-d H:i:s');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($job_title == null) {
        $error_message = 'Job title is required';
    } elseif ($location == null) {
        $error_message = 'Location is required';
    } elseif ($salary == null) {
        $error_message = 'Salary is required';
    } elseif ($category_id == null) {
        $error_message = 'Category is required';
    } elseif (strlen($description) > 700) {
        $error_message = 'The description should be NO MORE than 500 symbols';
    } elseif (!preg_match('/^[a-zA-Z\s\-]+$/', $job_title)) {
        $error_message = "Job title should only contain letters, spaces, or hyphens.";
    } elseif (filter_var($salary, FILTER_VALIDATE_INT) == false) {
        $error_message = "Salary should only be made by numbers";
    } elseif ($salary && $location && $job_title && $category_id) {
        $stmt = $connection->prepare("INSERT INTO jobs (title, location, salary, description, user_id, created_at, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissii", $job_title, $location, $salary, $description, $user_id, $created_at, $category_id);
        if ($stmt->execute()) {

            $success_message = 'Job created SUCCESSFULLY, waiting for approval';

            $job_title = $location = $salary = $description = '';
            $category_id = null;
        } else {
            $error_message = 'Error creating job. Please try again.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Job</title>
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
                            <h2 class="heading-title">New job</h2>
                        </div>
                        <?php if (!empty($error_message)): ?>
                            <div id="error-popup" class="popup-error">
                                <?= htmlspecialchars($error_message) ?>
                            </div>
                            <script>
                                setTimeout(() => {
                                    let popup = document.getElementById('error-popup');
                                    if (popup) popup.classList.add('hide');
                                }, 3000);
                            </script>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div id="success-popup" class="popup-success">
                                <?= htmlspecialchars($success_message) ?>
                            </div>
                            <script>
                                setTimeout(() => {
                                    let popup = document.getElementById('success-popup');
                                    if (popup) popup.classList.add('hide');
                                }, 3000);
                            </script>
                        <?php endif; ?>
                        <form action="create-job.php" method="POST">
                            <div class="flex-container flex-wrap">
                                <div class="form-field-wrapper width-large">
                                    <input type="text" placeholder="Job title*" name="job-title" value="<?= htmlspecialchars($job_title) ?>"/>
                                </div>
                                <div class="form-field-wrapper width-large">
                                    <input type="text" placeholder="Location*" name="location" value="<?= htmlspecialchars($location) ?>"/>
                                </div>
                                <div class="form-field-wrapper width-large">
                                    <input type="text" placeholder="Salary (in leva)*" name="salary" value="<?= htmlspecialchars($salary) ?>"/>
                                </div>
                                <div class="form-field-wrapper width-large">
                                    <textarea placeholder="Description" name="description"><?= htmlspecialchars($description) ?></textarea>
                                </div>
                                <div class="form-field-wrapper width-large">
                                    <select name="category">
                                        <option value="">Select category*</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="button">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="main.js"></script>
</body>
</html>
