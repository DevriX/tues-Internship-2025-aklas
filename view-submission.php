<?php
require_once 'dbconn.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info (assume session is started and user is logged in)
session_start();
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user = [];
if ($user_id) {
    $result = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($result);
}

// Get submission ID from URL
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$submission = null;

if ($submission_id > 0) {
    $sql = "SELECT * FROM apply_submissions WHERE id = $submission_id";
    $result = mysqli_query($connection, $sql);
    $submission = mysqli_fetch_assoc($result);
}


include 'header.php';
include 'auth-user.php';
include 'vertical-navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>View Submission</title>
	<link rel="preconnect" href="https://fonts.gstatic.com">

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
								<h2 class="heading-title">
									<?= htmlspecialchars($submission['job_name'] ?? 'Job Name') ?> - 
									<?= htmlspecialchars($submission['first_name'] ?? 'Applicant Name') ?>
								</h2>
							</div>
							<form>
								<div class="flex-container justified-horizontally flex-wrap">
									<div class="form-field-wrapper width-medium">
										<input type="text" value="<?= htmlspecialchars($submission['email'] ?? '') ?>" placeholder="Email" readonly />
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="text" value="<?= htmlspecialchars($submission['phone_number'] ?? '') ?>" placeholder="Phone Number" readonly />
									</div>			
									<div class="form-field-wrapper width-large">
										<textarea readonly><?= htmlspecialchars($submission['message'] ?? '') ?></textarea>
									</div>
								</div>	
								<?php if (!empty($submission['cv_file_path'])): ?>
									<a href="<?= htmlspecialchars($submission['cv_file_path']) ?>" class="button" download>Download CV</a>
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