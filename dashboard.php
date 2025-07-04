<?php
require 'dbconn.php';
$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'register.php' && $current_page !== 'login.php'):
include 'header.php';
include 'auth-user.php';
include 'vertical-navbar.php';
?>

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
	<link rel="stylesheet" href="./css/maps.css">
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">

		<main class="site-main">
			<section class="section-fullwidth section-jobs-dashboard">
				<div class="row">
					<div class="jobs-dashboard-header flex-container centered-vertically justified-horizontally">
						<div class="primary-container">							
							<ul class="tabs-menu">
								<li class="menu-item current-menu-item">
									<a href="#">Jobs</a>					
								</li>
								<li class="menu-item">
									<a href="#">Categories</a>
								</li>
							</ul>
						</div>
						<div class="secondary-container">
							<div class="flex-container centered-vertically">
								<div class="search-form-wrapper">
									<div class="search-form-field"> 
										<input class="search-form-input" type="text" value="" placeholder="Search…" name="search" > 
									</div> 
								</div>
								<div class="filter-wrapper">
									<div class="filter-field-wrapper">
										<select>
											<option value="1">Date</option>
											<option value="2">Date</option>
											<option value="3">Date</option>
											<option value="4">Type</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<ul class="jobs-listing">
						<li class="job-card">
							<div class="job-primary">
								<h2 class="job-title"><a href="#">Front End Developer</a></h2>
								<div class="job-meta">
									<a class="meta-company" href="#">Company Awesome Ltd.</a>
									<span class="meta-date">Posted 14 days ago</span>
								</div>
								<div class="job-details">
									<span class="job-location">The Hague (The Netherlands)</span>
									<span class="job-type">Contract staff</span>
								</div>
							</div>
							<div class="job-secondary">
								<div class="job-actions">
									<a href="#">Approve</a>
									<a href="#">Reject</a>
								</div>
								<div class="job-edit">
									<a href="#">View Submissions</a>
									<a href="#">Edit</a>
								</div>
							</div>
						</li>
						<li class="job-card">
							<div class="job-primary">
								<h2 class="job-title"><a href="#">Front End Developer</a></h2>
								<div class="job-meta">
									<a class="meta-company" href="#">Company Awesome Ltd.</a>
									<span class="meta-date">Posted 14 days ago</span>
								</div>
								<div class="job-details">
									<span class="job-location">The Hague (The Netherlands)</span>
									<span class="job-type">Contract staff</span>
								</div>
							</div>
							<div class="job-secondary">
								<div class="job-actions">
									<a href="#">Approve</a>
									<a href="#">Reject</a>
								</div>
								<div class="job-edit">
									<a href="#">View Submissions</a>
									<a href="#">Edit</a>
								</div>
							</div>
						</li>
						<li class="job-card">
							<div class="job-primary">
								<h2 class="job-title"><a href="#">Front End Developer</a></h2>
								<div class="job-meta">
									<a class="meta-company" href="#">Company Awesome Ltd.</a>
									<span class="meta-date">Posted 14 days ago</span>
								</div>
								<div class="job-details">
									<span class="job-location">The Hague (The Netherlands)</span>
									<span class="job-type">Contract staff</span>
								</div>
							</div>
							<div class="job-secondary">
								<div class="job-edit">
									<a href="#">View Submissions</a>
									<a href="#">Edit</a>
								</div>
							</div>
						</li>
						<li class="job-card">
							<div class="job-primary">
								<h2 class="job-title"><a href="#">Front End Developer</a></h2>
								<div class="job-meta">
									<a class="meta-company" href="#">Company Awesome Ltd.</a>
									<span class="meta-date">Posted 14 days ago</span>
								</div>
								<div class="job-details">
									<span class="job-location">The Hague (The Netherlands)</span>
									<span class="job-type">Contract staff</span>
								</div>
							</div>
							<div class="job-secondary">
								<div class="job-edit">
									<a href="#">View Submissions</a>
									<a href="#">Edit</a>
								</div>
							</div>
						</li>
						<li class="job-card">
							<div class="job-primary">
								<h2 class="job-title"><a href="#">Front End Developer</a></h2>
								<div class="job-meta">
									<a class="meta-company" href="#">Company Awesome Ltd.</a>
									<span class="meta-date">Posted 14 days ago</span>
								</div>
								<div class="job-details">
									<span class="job-location">The Hague (The Netherlands)</span>
									<span class="job-type">Contract staff</span>
								</div>
							</div>
							<div class="job-secondary">
								<div class="job-edit">
									<a href="#">View Submissions</a>
									<a href="#">Edit</a>
								</div>
							</div>
						</li>
					</ul>
					<div class="jobs-pagination-wrapper">
						<div class="nav-links"> 
							<a class="page-numbers current">1</a> 
							<a class="page-numbers">2</a> 
							<a class="page-numbers">3</a> 
							<a class="page-numbers">4</a> 
							<a class="page-numbers">5</a> 
						</div>
					</div>
				</div>
			</section>
		</main>
	</div>
	<script src="main.js"></script>
	<!-- Google Maps Modal -->
	<div id="maps-modal">
		<div class="maps-modal-content">
			<button id="close-maps-modal">&times;</button>
			<iframe id="maps-iframe" src="" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
			<a id="maps-link" href="#" target="_blank">Open in Google Maps</a>
		</div>
	</div>
</body>
</html>