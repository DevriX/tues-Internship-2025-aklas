<?php
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
    <style>
        body { background: #f8f9fb; }
        .job-minimal-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            max-width: 420px;
            margin: 2.5rem auto;
            padding: 2.2rem 1.5rem 1.5rem 1.5rem;
            box-shadow: 0 2px 8px rgba(80,0,120,0.04);
        }
        .job-minimal-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #4b0082;
            margin-bottom: 1.2rem;
        }
        .job-minimal-form-field {
            margin-bottom: 1.1rem;
        }
        .job-minimal-form-field input,
        .job-minimal-form-field textarea {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            background: #fff;
            transition: border 0.15s;
        }
        .job-minimal-form-field input:focus,
        .job-minimal-form-field textarea:focus {
            border: 1.5px solid #4b0082;
            outline: none;
        }
        .job-minimal-btn {
            width: 100%;
            padding: 0.85rem 0;
            background: #4b0082;
            color: #fff;
            font-size: 1.08rem;
            font-weight: 600;
            border: none;
            border-radius: 0.7rem;
            margin-top: 0.7rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .job-minimal-btn:hover {
            background: #2d1457;
        }
        .popup-success {
            background: #e7fbe7;
            color: #15803d;
            border-radius: 0.5rem;
            padding: 0.7rem 1rem;
            text-align: center;
            font-weight: 500;
            margin-bottom: 1rem;
            font-size: 1rem;
            border: 1px solid #bbf7d0;
        }
        .popup-error {
            background: #fef2f2;
            color: #b91c1c;
            border-radius: 0.5rem;
            padding: 0.7rem 1rem;
            text-align: center;
            font-weight: 500;
            margin-bottom: 1rem;
            font-size: 1rem;
            border: 1px solid #fecaca;
        }
        #selected-categories {
            margin-top: 0.5rem;
            color: #4b0082;
            font-weight: 500;
            font-size: 0.98rem;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(60, 0, 100, 0.10);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            border-radius: 0.7rem;
            padding: 1.2rem 1.2rem 1rem 1.2rem;
            box-shadow: 0 2px 12px rgba(80,0,120,0.07);
            max-width: 340px;
            margin: auto;
            position: relative;
            border: 1px solid #e5e7eb;
        }
        .close-btn {
            position: absolute;
            top: 0.7rem;
            right: 0.7rem;
            font-size: 1.5rem;
            color: #4b0082;
            background: none;
            border: none;
            cursor: pointer;
        }
        .category-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin: 1rem 0 1.2rem 0;
        }
        .category-list label {
            font-size: 1rem;
            color: #2d1457;
            font-weight: 500;
            cursor: pointer;
        }
        .category-list input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        @media (max-width: 500px) {
            .job-minimal-card { padding: 0.7rem 0.2rem; }
            .modal-content { padding: 0.7rem 0.2rem; }
        }
    </style>
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
                    <button type="submit" class="job-minimal-btn">Create</button>
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

<script>
    const modal = document.getElementById("categoryModal");
    const categoryInput = document.getElementById("category-input");
    const selectedDiv = document.getElementById("selected-categories");

    function openModal() {
        modal.style.display = "flex"; // Changed to flex to center content
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