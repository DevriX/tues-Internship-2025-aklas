<?php
require 'dbconn.php';

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

$job_title = $_POST['job-title'] ?? '';
$location = $_POST['location'] ?? '';
$salary = $_POST['salary'] ?? '';
$description = $_POST['description'] ?? '';

// Insert job with user_id only (company info is in users table)
$sql = "INSERT INTO jobs (title, location, salary, description, user_id) VALUES ('$job_title', '$location', '$salary', '$description', '$user_id')";

mysqli_query($connection, $sql);

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'register.php' && $current_page !== 'login.php'):
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
					<h1 class="site-title"><a href="/index.php">Job Offers</a></h1>
				</div>
				<nav class="site-header-navigation">
					<ul class="menu">
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/index.php">Home</a>					
						</li>
						<li class="menu-item current-menu-item">
							<a href="/tues-Internship-2025-aklas/dashboard.php">Jobs Dashboard</a>
						</li>
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/profile.php">My Profile</a>				
						</li>
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/login.php">SignOut</a>	
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
								<h2 class="heading-title">New job</h2>
							</div>
							<form action="create-job.php" method="POST">
								<div class="flex-container flex-wrap">
									<div class="form-field-wrapper width-large">
										<input type="text" placeholder="Job title*" name="job-title"/>
									</div>
									<div class="form-field-wrapper width-large">
										<input type="text" placeholder="Location" name="location"/>
									</div>
									<div class="form-field-wrapper width-large">
										<input type="text" placeholder="Salary" name="salary"/>
									</div>
									<div class="form-field-wrapper width-large">
										<textarea placeholder="Description*" name="description"></textarea>
									</div>	
								</div>
								<button type="submit" class="button">
									Create
								</button>
							</form>
						</div>
					</div>
				</div>
			</section>	
		</main>
	</div>
</body>
</html>