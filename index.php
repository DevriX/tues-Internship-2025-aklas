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
include 'pagination.php';
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
				<ul class="tags-list" id="category-tags-list">
					<?php
					$cat_result = mysqli_query($connection, 'SELECT * FROM categories ORDER BY name ASC');
					$active_category = isset($_GET['category']) ? $_GET['category'] : '';
					$cat_index = 0;
					while ($cat = mysqli_fetch_assoc($cat_result)) {
						$cat_name = htmlspecialchars($cat['name']);
						$is_active = ($active_category === $cat['name']);
						$hidden_class = $cat_index >= 10 ? ' hidden-category' : '';
						echo '<li class="list-item' . $hidden_class . '"><a href="?category=' . urlencode($cat['name']) . '" class="list-item-link' . ($is_active ? ' active' : '') . '">' . $cat_name . '</a></li>';
						$cat_index++;
					}
					?>
					<li class="list-item show-more-li" style="display: none;">
    					<button id="show-more-categories" class="list-item-link">+</button>
					</li>
				</ul>


					<div class="flex-container centered-vertically">
						<form class="search-form-wrapper" method="get" action="index.php" style="display:inline;">
							<div class="search-form-field"> 
								<input class="search-form-input" type="text" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search…" name="search"> 
							</div>
						</form>
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

					<?php
					// Jobs query with category filter
					$items_per_page = 5;
					$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
					$search = isset($_GET['search']) ? trim($_GET['search']) : '';
					$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
					$show_pagination = true;

					if ($category_filter) {
						$stmt = $connection->prepare(
							"SELECT jobs.*, users.company_name
							 FROM jobs
							 LEFT JOIN users ON jobs.user_id = users.id
							 JOIN job_categories jc ON jobs.id = jc.job_id
							 JOIN categories c ON jc.category_id = c.id
							 WHERE jobs.approved = 1 AND LOWER(c.name) = LOWER(?)
							 ORDER BY jobs.id DESC"
						);
						$stmt->bind_param('s', $category_filter);
						$stmt->execute();
						$result = $stmt->get_result();
						$jobs = [];
						while ($job = $result->fetch_assoc()) {
							$jobs[] = $job;
						}
						$total_items = count($jobs);
						$stmt->close();
						$show_pagination = false;
						// Show all on one page, no pagination
					} else if ($search !== '') {
						$stmt = $connection->prepare(
							"SELECT jobs.*, users.company_name
							 FROM jobs
							 LEFT JOIN users ON jobs.user_id = users.id
							 LEFT JOIN job_categories jc ON jobs.id = jc.job_id
							 LEFT JOIN categories c ON jc.category_id = c.id
							 WHERE jobs.approved = 1 AND (
								jobs.title LIKE CONCAT('%', ?, '%')
								OR jobs.description LIKE CONCAT('%', ?, '%')
								OR c.name LIKE CONCAT('%', ?, '%')
							 )
							 GROUP BY jobs.id
							 ORDER BY jobs.id DESC"
						);
						$stmt->bind_param('sss', $search, $search, $search);
						$stmt->execute();
						$result = $stmt->get_result();
						$jobs = [];
						while ($job = $result->fetch_assoc()) {
							$jobs[] = $job;
						}
						$total_items = count($jobs);
						$stmt->close();
						$show_pagination = false;
						// Show all on one page, no pagination
					} else {
						$total_items_result = mysqli_query($connection, "SELECT COUNT(*) FROM jobs WHERE approved = 1");
						$total_items = mysqli_fetch_row($total_items_result)[0];
						$offset = ($page - 1) * $items_per_page;
						$sql = "SELECT jobs.*, users.company_name FROM jobs LEFT JOIN users ON jobs.user_id = users.id WHERE jobs.approved = 1 ORDER BY jobs.id DESC LIMIT $items_per_page OFFSET $offset";
						$result = mysqli_query($connection, $sql);
						$jobs = [];
						while ($job = mysqli_fetch_assoc($result)) {
							$jobs[] = $job;
						}
						$show_pagination = true;
					}
					?>
					<ul class="jobs-listing">
						<?php foreach ($jobs as $job):
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
									<div>
										<span class= "category-type"><?php echo htmlspecialchars($job['category']); ?></span>
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
						<?php endforeach; ?>
					</ul>
					<?php if ($show_pagination && $total_items > $items_per_page) {
    render_pagination($total_items, $items_per_page, $page, basename($_SERVER['PHP_SELF']));
} ?>
				</div>
			</section>	
		</main>

		<footer class="site-footer">
			<div class="row">
				<p style="font-size:12px; margin-top:10px;">Copyright 2020</p>
			</div>
		</footer>
	</div>


	<!-- Google Maps Modal -->
	<div id="maps-modal">
		<div class="maps-modal-content">
			<button id="close-maps-modal">&times;</button>
			<iframe id="maps-iframe" src="" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
			<a id="maps-link" href="#" target="_blank">Open in Google Maps</a>
		</div>
	</div>
<script src="main.js"></script>

</body>
</html>