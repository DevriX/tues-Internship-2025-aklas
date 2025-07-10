<?php
require_once 'require_login.php';
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
    SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.is_admin
    FROM login_tokens lt 
    JOIN users u ON lt.user_id = u.id 
    WHERE lt.token_hash = ? AND lt.expiry > NOW()
");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id, $first_name, $last_name, $email, $phone_number, $is_admin);
    $stmt->fetch();
    $user_logged_in = true;
    $display_name = $first_name;
    // Set $user for navbar
    $user = [
        'id' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone_number' => $phone_number,
        'is_admin' => $is_admin
    ];
} else {
    die('Invalid login session.');
}
$stmt->close();

// Get job_id from query param
$job_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$job = null;

if ($job_id > 0) {
    $stmt = $connection->prepare("
        SELECT jobs.*, users.company_name, users.company_image 
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
    $cv_file_path = null; // Default to null
    $company_name = isset($_POST['company_name']) ? $_POST['company_name'] : null;
    $job_title = isset($_POST['job_title']) ? $_POST['job_title'] : null;

    // Handle file upload (server-side)
    $uploaded_files = [];
    if (isset($_FILES['cv_file_path']) && isset($_FILES['cv_file_path']['name']) && is_array($_FILES['cv_file_path']['name'])) {
        $allowed_exts = ['pdf', 'doc', 'docx', 'png'];
        $max_files = 5;
        $file_count = count($_FILES['cv_file_path']['name']);
        if ($file_count > $max_files) {
            echo "<p style='color: red; text-align: center;'>You can upload a maximum of 5 files.</p>";
            exit;
        }
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['cv_file_path']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['cv_file_path']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_exts)) {
                    echo "<p style='color: red; text-align: center;'>Invalid file type: ".htmlspecialchars($ext).". Only PDF, DOC, DOCX allowed.</p>";
                    exit;
                }
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = time() . '_' . basename($_FILES['cv_file_path']['name'][$i]);
                $target_path = $upload_dir . $filename;
                $relative_path = 'uploads/' . $filename;
                if (move_uploaded_file($_FILES['cv_file_path']['tmp_name'][$i], $target_path)) {
                    $uploaded_files[] = $relative_path;
                }
            }
        }
        // Save file paths as JSON or comma-separated string
        $cv_file_path = json_encode($uploaded_files);
    }

    // Insert application (now with company_name and job_title)
    $sql = "INSERT INTO apply_submissions 
        (job_id, user_id, first_name, last_name, email, phone_number, message, cv_file_path, company_name, job_title, applied_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("iissssssss", $job_id, $user_id, $first_name, $last_name, $email, $phone_number, $message, $cv_file_path, $company_name, $job_title);

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
        body { background: #f7f8fa; }
        .apply-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 32px rgba(80,0,120,0.10);
            padding: 2.5rem 2.5rem 2rem 2.5rem;
            max-width: 500px;
            margin: 2.5rem auto;
            position: relative;
        }
        .apply-logo {
            width: 72px;
            height: 72px;
            border-radius: 1rem;
            background: #f3f0ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem auto;
            overflow: hidden;
        }
        .apply-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .apply-title {
            text-align: center;
            font-size: 1.7rem;
            font-weight: 700;
            color: #4b0082;
            margin-bottom: 0.7rem;
        }
        .apply-form-field {
            margin-bottom: 1.2rem;
        }
        .apply-form-field input,
        .apply-form-field textarea {
            width: 100%;
            padding: 0.95rem 1.1rem;
            border: 1.5px solid #e0e0e0;
            border-radius: 0.7rem;
            font-size: 1.08rem;
            background: #fafaff;
            transition: border 0.18s;
        }
        .apply-form-field input:focus,
        .apply-form-field textarea:focus {
            border: 1.5px solid #7c3aed;
            outline: none;
            background: #fff;
        }
        .apply-file-label {
            display: block;
            font-weight: 500;
            color: #4b0082;
            margin-bottom: 0.5rem;
        }
        .apply-file-input {
            width: 100%;
            padding: 0.7rem 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background: #fff;
            font-size: 1rem;
        }
        .apply-file-types {
            color: #7c3aed;
            font-size: 0.98rem;
            margin-top: 0.3rem;
        }
        .apply-btn {
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
        .apply-btn:hover {
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
        .popup-error {
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 0.7rem;
            padding: 1rem 1.5rem;
            text-align: center;
            font-weight: 600;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(80,0,120,0.07);
        }
        @media (max-width: 700px) {
            .apply-card { padding: 1.2rem 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="site-wrapper">
        <main class="site-main">
            <section class="section-fullwidth">
                <div class="apply-card">
                    <div class="apply-logo">
                        <?php if (!empty($job['company_image'])): ?>
                            <img src="<?= htmlspecialchars($job['company_image']) ?>" alt="Company Logo">
                        <?php else: ?>
                            <img src="https://i.imgur.com/ZbILm3F.png" alt="Company Logo">
                        <?php endif; ?>
                    </div>
                    <div class="apply-title">Submit application for <?= htmlspecialchars($job['title']) ?></div>
                    <form method="POST" enctype="multipart/form-data" action="">
                        <input type="hidden" name="job_id" value="<?= htmlspecialchars($job_id) ?>">
                        <input type="hidden" name="company_name" value="<?= htmlspecialchars($job['company_name'] ?? '') ?>">
                        <input type="hidden" name="job_title" value="<?= htmlspecialchars($job['title'] ?? '') ?>">
                        <div class="apply-form-field">
                            <input type="text" value="<?= htmlspecialchars($job['company_name'] ?? '') ?>" disabled />
                        </div>
                        <div class="apply-form-field">
                            <input type="text" value="<?= htmlspecialchars($job['title'] ?? '') ?>" disabled />
                        </div>
                        <div class="apply-form-field">
                            <input type="text" value="<?= htmlspecialchars($first_name) ?>" disabled />
                            <input type="hidden" name="first_name" value="<?= htmlspecialchars($first_name) ?>" />
                        </div>
                        <div class="apply-form-field">
                            <input type="text" value="<?= htmlspecialchars($last_name) ?>" disabled />
                            <input type="hidden" name="last_name" value="<?= htmlspecialchars($last_name) ?>" />
                        </div>
                        <div class="apply-form-field">
                            <input type="email" value="<?= htmlspecialchars($email) ?>" disabled />
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>" />
                        </div>
                        <div class="apply-form-field">
                            <input type="text" value="<?= htmlspecialchars($phone_number) ?>" disabled />
                            <input type="hidden" name="phone_number" value="<?= htmlspecialchars($phone_number) ?>" />
                        </div>
                        <div class="apply-form-field">
                            <textarea name="message" placeholder="Custom Message*" required rows="3"></textarea>
                        </div>
                        <div class="apply-form-field">
                            <label class="apply-file-label">Upload CV/Files</label>
                            <input type="file" name="cv_file_path[]" accept=".png,.pdf,.doc,.docx" multiple required class="apply-file-input" />
                            <div class='apply-file-types'>Accepted file types: png, pdf, doc, docx. Max. files: 5.</div>
                        </div>
                        <button class="apply-btn" type="submit">Submit</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
    <script src="main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.querySelector('input[type="file"][name="cv_file_path[]"]');
        fileInput.addEventListener('change', function(e) {
            const files = Array.from(fileInput.files);
            if (files.length > 5) {
                alert('You can upload a maximum of 5 files.');
                fileInput.value = '';
                return;
            }
            const allowed = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/png'
            ];
            for (const file of files) {
                if (!allowed.includes(file.type)) {
                    alert('Only PNG, PDF, DOC, and DOCX files are allowed.');
                    fileInput.value = '';
                    return;
                }
            }
        });
    });
    </script>
</body>
</html>

<?php endif; ?>
