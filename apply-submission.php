<?php
session_start();
require 'dbconn.php';

require_once 'config.php';
$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page !== 'register.php' && $current_page !== 'login.php'):

if (!isset($_SESSION['user_id']) || !isset($_COOKIE['login_token'])) {
    die('You must be logged in to apply.');
}

// Validate login token
$token = $_COOKIE['login_token'];
$token_hash = hash('sha256', $token);

$stmt = $connection->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number
    FROM login_tokens lt 
    JOIN users u ON lt.user_id = u.id 
    WHERE lt.token_hash = ? AND lt.expiry > NOW()
");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id, $first_name, $last_name, $email, $phone_number);
    $stmt->fetch();
    $user_logged_in = true;
    $display_name = $first_name;
} else {
    die('Invalid login session.');
}
$stmt->close();

// Get job_id from query param
$job_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$job = null;

if ($job_id > 0) {
    $stmt = $connection->prepare("
        SELECT jobs.*, users.company_name 
        FROM jobs 
        LEFT JOIN users ON jobs.user_id = users.id 
        WHERE jobs.id = ?
    ");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $job = $result->fetch_assoc();
    }
    $stmt->close();
}

// Optional: check if the job exists
$stmt = $connection->prepare("SELECT id FROM jobs WHERE id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    die("Invalid job ID.");
}
$stmt->close();

// Check if user has already applied to this job
$already_applied = false;
$check_stmt = $connection->prepare("
    SELECT id FROM apply_submissions 
    WHERE job_id = ? AND user_id = ?
");
$check_stmt->bind_param("ii", $job_id, $user_id);
$check_stmt->execute();
$check_stmt->store_result();
if ($check_stmt->num_rows > 0) {
    $already_applied = true;
}
$check_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {
    $message = mysqli_real_escape_string($connection, $_POST['message']);
    $cv_file_path = null;

    // Handle file upload
    if (isset($_FILES['cv_file_path']) && $_FILES['cv_file_path']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = "/{$project_path}/uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = basename($_FILES['cv_file_path']['name']);
        $unique_filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $filename;
        $cv_file_path = $upload_dir . $unique_filename;
        move_uploaded_file($_FILES['cv_file_path']['tmp_name'], $cv_file_path);
    }

    // Insert application
    $sql = "INSERT INTO apply_submissions 
        (job_id, user_id, first_name, last_name, email, phone_number, message, cv_file_path, applied_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("iissssss", $job_id, $user_id, $first_name, $last_name, $email, $phone_number, $message, $cv_file_path);

    if ($stmt->execute()) {
        $stmt->close();
        $connection->close();
        header("Location: index.php");
        exit();
    } else {
        echo "<p style='color: red; text-align: center;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }
}

include 'header.php';
include 'vertical-navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Application</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/master.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .center-heading {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="site-wrapper">
        <?php include 'vertical-navbar.php'; ?>

        <main class="site-main">
            <section class="section-fullwidth">
                <div class="row">    
                    <div class="flex-container centered-vertically centered-horizontally">
                        <div class="form-box box-shadow">
                            <div class="section-heading center-heading">
                                <h2 class="heading-title">Submit application for <?php echo htmlspecialchars($job['title']); ?></h2>
                            </div>

                            <form method="POST" enctype="multipart/form-data" action="">
                                <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">

                                <div class="flex-container justified-horizontally flex-wrap">

                                    <!-- Uneditable Inputs + Hidden Values -->
                                    <div class="form-field-wrapper width-medium">
                                        <input type="text" value="<?php echo htmlspecialchars($first_name); ?>" disabled />
                                        <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" />
                                    </div>
                                    <div class="form-field-wrapper width-medium">
                                        <input type="text" value="<?php echo htmlspecialchars($last_name); ?>" disabled />
                                        <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" />
                                    </div>
                                    <div class="form-field-wrapper width-medium">
                                        <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled />
                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" />
                                    </div>
                                    <div class="form-field-wrapper width-medium">
                                        <input type="text" value="<?php echo htmlspecialchars($phone_number); ?>" disabled />
                                        <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" />
                                    </div>

                                    <!-- Editable Fields -->
                                    <div class="form-field-wrapper width-large">
                                        <textarea name="message" placeholder="Custom Message*" required></textarea>
                                    </div>
                                    <div class="form-field-wrapper width-large">
                                        <input type="file" name="cv_file_path" />
                                    </div>
                                </div>

                                <?php if ($already_applied): ?>
                                    <!-- Custom popup message -->
                                    <div id="error-popup" class="popup-error">
                                        You have already submitted an application for this job.
                                    </div>

                                    <!-- Disabled button (non-clickable) -->
                                    <button class="button" type="button" disabled>Submit</button>
                                <?php else: ?>
                                    <!-- Submit button (form will POST) -->
                                    <button class="button" type="submit">Submit</button>
                                <?php endif; ?>

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

<?php endif; ?>
