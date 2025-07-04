<?php
session_start();	
include 'dbconn.php';

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'register.php' && $current_page !== 'login.php'):

if (isset($_SESSION['user_id'])) {
	$user_id = $_SESSION['user_id'];
} else {
	// Handle the case where the user is not logged in
	die('You must be logged in to apply.');
}

// If you need more user info:
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($connection, $sql);
$user = mysqli_fetch_assoc($result);
// Now $user['first_name'], $user['email'], etc. are available

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// 1. Get and sanitize input
	$first_name = mysqli_real_escape_string($connection, $_POST['first_name']);
	$last_name = mysqli_real_escape_string($connection, $_POST['last_name']);
	$email = mysqli_real_escape_string($connection, $_POST['email']);
	$phone_number = mysqli_real_escape_string($connection, $_POST['phone_number']);
	$message = mysqli_real_escape_string($connection, $_POST['message']);

	// 2. Handle file upload (optional)
	$cv_file_path = null;
	if (isset($_FILES['cv_file_path']) && $_FILES['cv_file_path']['error'] == UPLOAD_ERR_OK) {
		$upload_dir = 'uploads/';
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
		$filename = basename($_FILES['cv_file_path']['name']);
		$unique_filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $filename;
		$cv_file_path = $upload_dir . $unique_filename;
		move_uploaded_file($_FILES['cv_file_path']['tmp_name'], $cv_file_path);
	}

	// 3. Insert into database with current timestamp for applied_at
	$sql = "INSERT INTO apply_submissions (first_name, last_name, email, phone_number, message, cv_file_path, applied_at)
			VALUES ('$first_name', '$last_name', '$email', '$phone_number', '$message', '$cv_file_path', NOW())";

	if (mysqli_query($connection, $sql)) {
		echo "<p>Application submitted successfully!</p>";
	} else {
		echo "<p>Error: " . mysqli_error($connection) . "</p>";
	}
}

$_SESSION['user_id'] = $user['id'];
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
<?php endif; ?> 

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
		<header class="site-header">
			<div class="row site-header-inner">
				<div class="site-header-branding">
					<h1 class="site-title"><a href="/tues-Internship-2025-aklas/index.php">Job Offers</a></h1>
				</div>
				<nav class="site-header-navigation">
					<ul class="menu">
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/index.php">Home</a>					
						</li>
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/register.php">Register</a>
						</li>
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/login.php">Login</a>					
						</li>
					</ul>
				</nav>
				<button class="menu-toggle">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path fill="currentColor" class='menu-toggle-bars' d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
				</button>
			</div>
		</header>

		<main class="site-main">
			<section class="section-fullwidth">
				<div class="row">	
					<div class="flex-container centered-vertically centered-horizontally">
						<div class="form-box box-shadow">
							<div class="section-heading">
								<h2 class="heading-title">Submit application to
									Company Name</h2>
							</div>
							<form method="POST" enctype="multipart/form-data" action="apply-submission.php">
								<div class="flex-container justified-horizontally flex-wrap">									
									<div class="form-field-wrapper width-medium">
										<input type="text" name="first_name" placeholder="First Name*" required/>
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="text" name="last_name" placeholder="Last Name*" required/>
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="email" name="email" placeholder="Email*" required/>
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="text" name="phone_number" placeholder="Phone Number"/>
									</div>			
									<div class="form-field-wrapper width-large">
										<textarea name="message" placeholder="Custom Message*" required></textarea>
									</div>
									<div class="form-field-wrapper width-large">
										<input type="file" name="cv_file_path" />
									</div>
								</div>	
								<button class="button" type="submit">
									Submit
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