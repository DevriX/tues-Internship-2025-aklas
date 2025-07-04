<?php
require 'dbconn.php';
$user_logged_in = false;
$display_name = '';
require 'dbconn.php';
$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.first_name
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($first_name);
        $stmt->fetch();
        $user_logged_in = true;
        $display_name = $first_name;
    }
    $stmt->close();
}
include 'header.php';
include 'vertical-navbar.php';
?>

<nav class="footer-vertical-menu">
	<button class="menu-toggle-arrow" aria-label="Toggle menu">
		<svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" stroke="#222" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</button>
	<a href="/tues-Internship-2025-aklas/index.php" class="footer-vlink<?php if($current_page == 'index.php') echo ' active'; ?>">Home</a>
	<a href="/tues-Internship-2025-aklas/dashboard.php" class="footer-vlink<?php if($current_page == 'dashboard.php') echo ' active'; ?>">Jobs Dashboard</a>
	<a href="/tues-Internship-2025-aklas/submissions.php" class="footer-vlink<?php if($current_page == 'submissions.php') echo ' active'; ?>">Submissions</a>
	<a href="/tues-Internship-2025-aklas/view-submission.php" class="footer-vlink<?php if($current_page == 'view-submission.php') echo ' active'; ?>">View Submission</a>
	<a href="/tues-Internship-2025-aklas/create-job.php" class="footer-vlink<?php if($current_page == 'create-job.php') echo ' active'; ?>">Create-Edit Job</a>
	<a href="/tues-Internship-2025-aklas/category-dashboard.php" class="footer-vlink<?php if($current_page == 'category-dashboard.php') echo ' active'; ?>">Category Dashboard</a>
	<a href="/tues-Internship-2025-aklas/profile.php" class="footer-vlink<?php if($current_page == 'profile.php') echo ' active'; ?>">My Profile</a>
    <a href="/tues-Internship-2025-aklas/logout.php" class="footer-vlink<?php if($current_page == 'logout.php') echo ' active'; ?>">Logout</a>
	<a href="/tues-Internship-2025-aklas/register.php" class="footer-vlink<?php if($current_page == 'register.php') echo ' active'; ?>">Register</a>
</nav>

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.first_name
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($first_name);
        $stmt->fetch();
        $user_logged_in = true;
        $display_name = $first_name;
    }
    $stmt->close();
}
include 'header.php';
include 'vertical-navbar.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Jobs</title>
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
								<h2 class="heading-title">Job Name - Applicant Name</h2>
							</div>
							<form>
								<div class="flex-container justified-horizontally flex-wrap">
									<div class="form-field-wrapper width-medium">
										<input type="text" placeholder="Email" readonly />
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="text" placeholder="Phone Number" readonly />
									</div>			
									<div class="form-field-wrapper width-large">
										<textarea placeholder="Custom Message" readonly ></textarea>
									</div>
								</div>	
								<button type="submit" class="button">
									Download CV
								</button>
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