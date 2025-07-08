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
    } else {
        $category_names = implode(', ', array_map('htmlspecialchars', $selected_categories));
        $stmt = $connection->prepare("INSERT INTO jobs (title, location, salary, description, user_id, created_at, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $job_title, $location, $salary, $description, $user_id, $created_at, $category_names);

        if ($stmt->execute()) {
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
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            padding-top: 100px;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            width: 40%;
            border-radius: 8px;
        }

        .category-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .category-list label {
            display: block;
            margin-bottom: 8px;
        }

        .close-btn {
            float: right;
            font-size: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="row">
                <div class="flex-container centered-vertically centered-horizontally">
                    <div class="form-box box-shadow">
                        <div class="section-heading">
                            <h2 class="heading-title">New Job</h2>
                        </div>

                        <?php if ($error_message): ?>
                            <div id="error-popup" class="popup-error"><?= htmlspecialchars($error_message) ?></div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div id="success-popup" class="popup-success"><?= htmlspecialchars($success_message) ?></div>
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

                                <!-- Hidden category selections -->
                                <input type="hidden" id="category-input" name="category[]" multiple />

                                <div class="form-field-wrapper width-large">
                                    <button type="button" onclick="openModal()" class="button">Select Categories</button>
                                    <div id="selected-categories" style="margin-top: 10px; color: #555;"></div>
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

<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Select Categories</h3>
        <div class="category-list">
            <?php foreach ($categories as $category): ?>
                <label><input type="checkbox" value="<?= htmlspecialchars($category) ?>" class="category-checkbox"> <?= htmlspecialchars($category) ?></label>
            <?php endforeach; ?>
        </div>
        <button onclick="saveCategories()" class="button">Save Selection</button>
    </div>
</div>
<script src="main.js"></script>

<script>
    const modal = document.getElementById("categoryModal");
    const categoryInput = document.getElementById("category-input");
    const selectedDiv = document.getElementById("selected-categories");

    function openModal() {
        modal.style.display = "block";
    }

    function closeModal() {
        modal.style.display = "none";
    }

    function saveCategories() {
        const checkboxes = document.querySelectorAll('.category-checkbox:checked');
        const selected = [];
        checkboxes.forEach(cb => selected.push(cb.value));

        selectedDiv.innerHTML = "Selected: " + selected.join(', ');
        closeModal();

        // Clear and recreate hidden inputs
        const form = document.querySelector('form');
        document.querySelectorAll('input[name="category[]"]').forEach(e => e.remove());
        selected.forEach(val => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'category[]';
            input.value = val;
            form.appendChild(input);
        });
    }

    // Close modal on outside click
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

</body>
</html>
