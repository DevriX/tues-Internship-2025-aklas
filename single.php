<?php
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
	<a href="/tues-Internship-2025-aklas/apply-submission.php" class="footer-vlink<?php if($current_page == 'apply-submission.php') echo ' active'; ?>">Apply Submission</a>
	<a href="/tues-Internship-2025-aklas/view-submission.php" class="footer-vlink<?php if($current_page == 'view-submission.php') echo ' active'; ?>">View Submission</a>
	<a href="/tues-Internship-2025-aklas/create-job.php" class="footer-vlink<?php if($current_page == 'create-job.php') echo ' active'; ?>">Create-Edit Job</a>
	<a href="/tues-Internship-2025-aklas/category-dashboard.php" class="footer-vlink<?php if($current_page == 'category-dashboard.php') echo ' active'; ?>">Category Dashboard</a>
	<a href="/tues-Internship-2025-aklas/profile.php" class="footer-vlink<?php if($current_page == 'profile.php') echo ' active'; ?>">My Profile</a>
    <a href="/tues-Internship-2025-aklas/login.php" class="footer-vlink<?php if($current_page == 'login.php') echo ' active'; ?>">Login</a>
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
	<link rel="stylesheet" href="./css/maps.css">
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
					<div class="job-single">
						<div class="job-main">
							<div class="job-card">
								<div class="job-primary">
									<header class="job-header">
										<h2 class="job-title"><a href="#">Front End Developer</a></h2>
										<div class="job-meta">
											<a class="meta-company" href="#">Company Awesome Ltd.</a>
											<span class="meta-date">Posted 14 days ago</span>
										</div>
										<div class="job-details">
											<span class="job-location">The Hague (The Netherlands)</span>
											<span class="job-type">Contract staff</span>
											<span class="job-price">1500лв.</span>
										</div>
									</header>

									<div class="job-body">
										<p>Our band of superheroes are looking for a self-driven, highly organised individual who will join the team in creating our most important products.</p>
										<p>Location is unimportant, as long as you are available, enthusiastic, committed, passionate, and know your stuff.</p>
										<p>For this role, we need a superhero who will take on the challenges of working in one of the leading WordPress companies, enhancing our website, products, and services, backed by a quality team of pros.</p>

										<h3>Responsibilities</h3>
										<p>You'll be part of a development team working on our flagship products. It's going to be epic!</p>
									</div>
								</div>
							</div>
						</div>
						<aside class="job-secondary">
							<div class="job-logo">
								<div class="job-logo-box">
									<img src="https://i.imgur.com/ZbILm3F.png" alt="">
								</div>
							</div>
							<a href="#" class="button button-wide">Apply now</a>
							<a href="https://www.apple.com/" target="_blank">apple.com</a>
						</aside>
					</div>
				</div>
			</section>
			<section class="section-fullwidth">
				<div class="row">
					<h2 class="section-heading">Other related jobs:</h2>
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
							<div class="job-logo">
								<div class="job-logo-box">
									<img src="https://i.imgur.com/ZbILm3F.png" alt="">
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
							<div class="job-logo">
								<div class="job-logo-box">
									<img src="https://i.imgur.com/ZbILm3F.png" alt="">
								</div>
							</div>
						</li>
					</ul>
				</div>
			</section>
		</main>
	</div>
	<script src="single.js"></script>

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