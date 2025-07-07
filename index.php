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
					<ul class="tags-list" id="category-tags-list" style="flex-wrap:wrap; max-height:unset; overflow:hidden; position:relative;">
						<?php
						$cat_result = mysqli_query($connection, 'SELECT * FROM categories ORDER BY name ASC');
						while ($cat = mysqli_fetch_assoc($cat_result)) {
							echo '<li class="list-item"><a href="#" class="list-item-link">' . htmlspecialchars($cat['name']) . '</a></li>';
						}
						?>
						<li class="list-item show-more-li" style="display:none;"><button id="show-more-categories" class="list-item-link">+</button></li>
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
							// 1. Set items per page
							$items_per_page = 5;

							// 2. Get current page from query string
							$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

							// 3. Count total jobs (approved)
							$total_items_result = mysqli_query($connection, "SELECT COUNT(*) FROM jobs WHERE approved = 1");
							$total_items = mysqli_fetch_row($total_items_result)[0];

							// 4. Calculate offset for SQL
							$offset = ($current_page - 1) * $items_per_page;

							// 5. Fetch jobs for current page
							$sql = "SELECT jobs.*, users.company_name
									FROM jobs
									LEFT JOIN users ON jobs.user_id = users.id
									WHERE jobs.approved = 1
									ORDER BY jobs.id DESC
									LIMIT $items_per_page OFFSET $offset";
							$result = mysqli_query($connection, $sql);
							if ($result && mysqli_num_rows($result) > 0) {
								while ($job = mysqli_fetch_assoc($result)) {
									if(empty($job['title']) || empty($job['location'])){
										continue;
									}
								
									?>
									<li class="job-card">
										<div class="job-primary">
											<h2 class="job-title">
												<a href="single.php?id=<?php echo $job['id']; ?>">
													<?php echo htmlspecialchars( $job['title'] ); ?>
												</a>
											</h2>
											<div class="job-meta">
												<span class="meta-company"><?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?></span>
												<span class="meta-date">Posted <?php echo htmlspecialchars($job['created_at']); ?></span>
											</div>
											<div class="job-details">
												<span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
												<span class="job-type">Salary: <?php echo htmlspecialchars($job['salary']); ?></span>
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

					<?php
					// 6. Set base URL (without page param)
					$base_url = 'index.php';

					// 7. Include and render pagination
					include 'pagination.php';
					render_pagination($total_items, $items_per_page, $current_page, $base_url, 'page');
					?>
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