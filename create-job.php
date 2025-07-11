<?php
include_once 'validate-location.php';
require_once 'require_login.php';
require 'dbconn.php';
$user_logged_in = false;
$display_name = '';
$user = null;
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.is_admin , u.company_name
        FROM login_tokens lt
        JOIN users u ON lt.user_id = u.id
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $is_admin , $company_name);
        $stmt->fetch();
        $user_logged_in = true;
        $is_company_name = true;
        $display_name = $first_name;
        if( trim($company_name) === ''){
         $is_company_name = false;
        }
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

// Fetch categories from database
$categories = [];
$result = mysqli_query($connection, "SELECT name FROM categories");
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row['name'];
}

// Form data
$job_title = $_POST['job-title'] ?? null;
$location = $_POST['location'] ?? null;
$salary_raw = $_POST['salary'] ?? null;
$salary = str_replace(' ', '', trim($salary_raw));
$description = $_POST['description'] ?? null;
$selected_categories = $_POST['category'] ?? [];
$created_at = date('Y-m-d H:i:s');
$error_message = '';
$success_message = '';
$result = isValidLocation($location);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($job_title == null) {
        $error_message = 'Job title is required';
    } elseif ($location == null) {
        $error_message = 'Location is required';
    } elseif ($salary == null) {
        $error_message = 'Salary is required';
    } elseif (strlen($description) > 700) {
        $error_message = 'The description should be NO MORE than 500 symbols';
    } elseif (!preg_match('/^[a-zA-Z\s\-]+$/', $job_title)) {
        $error_message = "Job title should only contain letters, spaces, or hyphens.";
    } elseif (filter_var($salary, FILTER_VALIDATE_INT) === false) {
        $error_message = "Salary should only be made by numbers";
    } elseif (empty($selected_categories)) {
        $error_message = "Please select at least one category.";
    } elseif($result == 0){
        $error_message = "Please enter a valid location";
    } else {
        $stmt = $connection->prepare("INSERT INTO jobs (title, location, salary, description, user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssi", $job_title, $location, $salary, $description, $user_id);

        if ($stmt->execute()) {
            $job_id = $stmt->insert_id;

            if (!empty($selected_categories)) {
                foreach ($selected_categories as $cat_name) {
                    $cat_stmt = $connection->prepare("SELECT id FROM categories WHERE name = ?");
                    $cat_stmt->bind_param("s", $cat_name);
                    $cat_stmt->execute();
                    $result = $cat_stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $cat_id = $row['id'];
                        $jc_stmt = $connection->prepare("INSERT INTO job_categories (job_id, category_id) VALUES (?, ?)");
                        $jc_stmt->bind_param("ii", $job_id, $cat_id);
                        $jc_stmt->execute();
                        $jc_stmt->close();
                    }
                    $cat_stmt->close();
                }
            }

            $success_message = 'Job created SUCCESSFULLY, waiting for approval';
            $job_title = $location = $salary = $description = '';
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

</head>
<body>
<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="job-minimal-card">
                <div class="job-minimal-title">New Job</div>
                <?php if ($error_message): ?>
                    <div id="error-popup" class="popup-error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div id="success-popup" class="popup-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <form action="create-job.php" method="POST">
                    <div class="job-minimal-form-field">
                        <input type="text" placeholder="Job title*" name="job-title" value="<?= htmlspecialchars($job_title) ?>"/>
                    </div>
                    <div class="job-minimal-form-field">
                        <input type="text" placeholder="Location*" name="location" value="<?= htmlspecialchars($location) ?>"/>
                    </div>
                    <div class="job-minimal-form-field">
                        <input type="text" placeholder="Salary (in leva)*" name="salary" value="<?= htmlspecialchars($salary) ?>"/>
                    </div>
                    <div class="job-minimal-form-field">
                        <textarea placeholder="Description" name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>
                    </div>
                    <input type="hidden" id="category-input" name="category[]" multiple />
                    <button type="button" onclick="openModal()" class="job-minimal-btn" style="margin-bottom:0.5rem;">Select Categories</button>
                    <div id="selected-categories"></div>

                    <?php if ($is_company_name): ?>
                        <button type="submit" class="job-minimal-btn">Create</button>
                    <?php else: ?>
                        <div class="popup-error">You must have a company to create a job</div>
                        <button type="button" class="job-minimal-btn">Create</button>
                    <?php endif; ?>

                </form>
            </div>
        </section>
    </main>
</div>
<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 style="text-align:center; color:#4b0082; font-weight:700; margin-bottom:1rem;">Select Categories</h3>
        <div class="category-list">
            <?php foreach ($categories as $category): ?>
                <label><input type="checkbox" value="<?= htmlspecialchars($category) ?>" class="category-checkbox"> <?= htmlspecialchars($category) ?></label>
            <?php endforeach; ?>
        </div>
        <button onclick="saveCategories()" class="job-minimal-btn">Save Selection</button>
    </div>
</div>
<script src="main.js"></script>
<script src="create-job.js"></script>

</body>
</html>