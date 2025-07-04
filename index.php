<?php

require 'dbconn.php';
$user_logged_in = false;
$display_name = '';
$user = null;
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.is_admin
        FROM login_tokens lt
        JOIN users u ON lt.user_id = u.id
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $is_admin);
        $stmt->fetch();
        $user_logged_in = true;
        $display_name = $first_name;
        $user = [
            'id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'is_admin' => $is_admin
        ];
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
	<link rel="stylesheet" href="./css/maps.css">
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">

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
									WHERE jobs.approved = 1
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