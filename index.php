<?php

require 'dbconn.php';
$user_logged_in = false;
$display_name = '';

$is_logged_in = false;
if (isset($_COOKIE['login_token'])) {
	$is_logged_in = true;
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

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'register.php' && $current_page !== 'login.php'):
?>

<?php if ($is_logged_in): ?>
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
						<li class="menu-item current-menu-item">
							<a href="/tues-Internship-2025-aklas/index.php">Home</a>					
						</li>

						<?php if ($user_logged_in): ?>
							<li class="menu-item">
								<span style="color: black; margin-right: 10px;">Hi, <?= htmlspecialchars($display_name) ?></span>
							</li>
							<li class="menu-item">
								<a href="/tues-Internship-2025-aklas/logout.php">Logout</a>
							</li>
						<?php else: ?>
							<li class="menu-item">
								<a href="/tues-Internship-2025-aklas/register.php">Register</a>
							</li>
							<li class="menu-item">
								<a href="/tues-Internship-2025-aklas/login.php">Login</a>					
							</li>
						<?php endif; ?>
					</ul>
				</nav>
				<button class="menu-toggle">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
						<path fill="none" d="M0 0h24v24H0z"/>
						<path fill="currentColor" class="menu-toggle-bars" d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
					</svg>
				</button>
			</div>
		</header>

		<main class="site-main">
			<section class="section-fullwidth section-jobs-preview">
				<div class="row">	
					<ul class="tags-list">
						<li class="list-item"><a href="#" class="list-item-link">IT</a></li>
						<li class="list-item"><a href="#" class="list-item-link">Manufactoring</a></li>
						<li class="list-item"><a href="#" class="list-item-link">Commerce</a></li>
						<li class="list-item"><a href="#" class="list-item-link">Architecture</a></li>
						<li class="list-item"><a href="#" class="list-item-link">Marketing</a></li>
					</ul>

					<div class="flex-container centered-vertically">
						<div class="search-form-wrapper">
							<div class="search-form-field"> 
								<input class="search-form-input" type="text" value="" placeholder="Search…" name="search"> 
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

					<ul class="jobs-listing">
							<?php
							$sql = "SELECT jobs.*, users.company_name
									FROM jobs
									LEFT JOIN users ON jobs.user_id = users.id
									ORDER BY jobs.id DESC";
							$result = mysqli_query($connection, $sql);

							if ($result && mysqli_num_rows($result) > 0) {
								while ($job = mysqli_fetch_assoc($result)) {
									if(empty($job['title']) || empty($job['location'])){
										continue;
									}
									?>
									<li class="job-card">
										<div class="job-primary">
											<h2 class="job-title"><a href="#"><?php echo htmlspecialchars($job['title']); ?></a></h2>
											<div class="job-meta">
												<span class="meta-company"><?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?></span>
												<span class="meta-date">Posted <?php echo htmlspecialchars($job['created_at']); ?></span>
											</div>
											<div class="job-details">
												<span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
												<span class="job-type">Salary: <?php echo htmlspecialchars($job['salary']); ?></span>
											</div>
											<div class="job-description">
												<?php echo nl2br(htmlspecialchars($job['description'])); ?>
											</div>
										</div>
										<div class="job-logo">
											<div class="job-logo-box">
												<img src="https://i.imgur.com/ZbILm3F.png" alt="">
											</div>
										</div>
									</li>
									<?php
								}
							} else {
								echo "<li>No jobs found.</li>";
							}
							?>
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

		<footer class="site-footer">
			<div class="row">
				<p style="font-size:12px; margin-top:10px;">Copyright 2020</p>
			</div>
		</footer>
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
<?php endif; ?>