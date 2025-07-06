<?php
require 'dbconn.php';

$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);

// Authenticate via cookie
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
include 'auth-user.php';
include 'vertical-navbar.php';

// Get job ID from URL query parameter
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Job Details</title>
	<link rel="stylesheet" href="./css/master.css">
	<link rel="stylesheet" href="./css/maps.css">
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">
		<main class="site-main">
			<section class="section-fullwidth">
				<div class="row">
					<?php if ($job): ?>
					<div class="job-single">
						<div class="job-main">
							<div class="job-card">
								<div class="job-primary">
									<header class="job-header">
										<h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
										<div class="job-meta">
											<span class="meta-company"><?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?></span>
											<span class="meta-date">Posted on <?php echo htmlspecialchars($job['created_at']); ?></span>
										</div>
										<div class="job-details">
											<span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
											<span class="job-type">Job ID: <?php echo htmlspecialchars($job['id']); ?></span>
											<span class="job-price"><?php echo htmlspecialchars($job['salary']); ?> лв.</span>
										</div>
									</header>

									<div class="job-body">
										<p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
									</div>
								</div>
							</div>
						</div>
						<aside class="job-secondary">
							<div class="job-logo">
								<div class="job-logo-box">
									<img src="https://i.imgur.com/ZbILm3F.png" alt="Company Logo">
								</div>
							</div>

							<!-- ✅ This link is correct and will pass the ID via GET -->
							<a href="apply-submission.php?id=<?php echo $job['id']; ?>" class="button button-wide">Apply now</a>
							<!-- ✅ Debug: Show ID -->
							<p style="margin-top: 1em;">Job ID: <?php echo $job['id']; ?></p>

							<a href="https://www.example.com/" target="_blank">example.com</a>
						</aside>
					</div>
					<?php else: ?>
						<p>Job not found.</p>
					<?php endif; ?>
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
