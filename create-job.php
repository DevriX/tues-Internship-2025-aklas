<?php
session_start();
require 'dbconn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$job_title = $_POST['job-title'] ?? '';
$location = $_POST['location'] ?? '';
$salary = $_POST['salary'] ?? '';
$description = $_POST['description'] ?? '';

// Insert job with user_id only (company info is in users table)
$sql = "INSERT INTO jobs (title, location, salary, description, user_id) VALUES ('$job_title', '$location', '$salary', '$description', '$user_id')";

mysqli_query($connection, $sql);

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
							<a href="/tues-Internship-2025-aklas/dashboard.html">Jobs Dashboard</a>
						</li>
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/profile.html">My Profile</a>				
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

		<footer class="site-footer">
			<div class="row">
				<p>Copyright 2020 | Developer links: 
					<a href="/tues-Internship-2025-aklas/index.php">Home</a>,
					<a href="/tues-Internship-2025-aklas/dashboard.html">Jobs Dashboard</a>,
					<a href="/tues-Internship-2025-aklas/single.html">Single</a>,
					<a href="/tues-Internship-2025-aklas/login.php">Login</a>,
					<a href="/tues-Internship-2025-aklas/register.php">Register</a>,
					<a href="/tues-Internship-2025-aklas/submissions.html">Submissions</a>,
					<a href="/tues-Internship-2025-aklas/apply-submission.html">Apply Submission</a>,
					<a href="/tues-Internship-2025-aklas/view-submission.html">View Submission</a>,
					<a href="/tues-Internship-2025-aklas/create-job.php">Create-Edit Job</a>,
					<a href="/tues-Internship-2025-aklas/category-dashboard.html">Category Dashboard</a>,
					<a href="/tues-Internship-2025-aklas/profile.html">My Profile</a>
				</p>
			</div>
		</footer>
	</div>
</body>
</html>