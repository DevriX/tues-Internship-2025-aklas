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
					$selected_categories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
					$categories = [];
					while ($cat = mysqli_fetch_assoc($cat_result)) {
						$categories[] = $cat;
					}

					// Separate selected and unselected
					$selected = [];
					$unselected = [];
					foreach ($categories as $cat) {
						if (in_array($cat['name'], $selected_categories)) {
							$selected[] = $cat;
						} else {
							$unselected[] = $cat;
						}
					}

					// Render selected categories first (always visible)
					foreach ($selected as $cat) {
						echo '<li class="list-item selected-category"><a href="#" data-category="' . htmlspecialchars($cat['name']) . '" class="list-item-link active">' . htmlspecialchars($cat['name']) . '</a></li>';
					}

					// Render up to 10 unselected categories
					$max_visible = 10;
					foreach ($unselected as $i => $cat) {
						$hidden_class = $i >= $max_visible ? ' hidden-category' : '';
						echo '<li class="list-item' . $hidden_class . '"><a href="#" data-category="' . htmlspecialchars($cat['name']) . '" class="list-item-link">' . htmlspecialchars($cat['name']) . '</a></li>';
					}

					// Show More/Less button if there are more than 10 unselected categories
					if (count($unselected) > $max_visible) {
						echo '<li class="list-item show-more-li"><button id="show-more-categories" class="list-item-link">+</button></li>';
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
					</div>

					<?php
					// Jobs query with category filter
					$items_per_page = 5;
					$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
					$search = isset($_GET['search']) ? trim($_GET['search']) : '';
					$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
					$show_pagination = true;

					if (!empty($selected_categories)) {
						$placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
						$types = str_repeat('s', count($selected_categories));
						$sql = "
							SELECT jobs.*, users.company_name, users.company_image
							FROM jobs
							LEFT JOIN users ON jobs.user_id = users.id
							JOIN job_categories jc ON jobs.id = jc.job_id
							JOIN categories c ON jc.category_id = c.id
							WHERE jobs.approved = 1 AND c.name IN ($placeholders)
							GROUP BY jobs.id
							HAVING COUNT(DISTINCT c.name) = ?
							ORDER BY jobs.id DESC
						";
						$stmt = $connection->prepare($sql);
						$bind_params = array_merge($selected_categories, [count($selected_categories)]);
						$stmt->bind_param($types . 'i', ...$bind_params);
						$stmt->execute();
						$result = $stmt->get_result();
						$jobs = [];
						while ($job = $result->fetch_assoc()) {
							$jobs[] = $job;
						}
						$total_items = count($jobs);
						$stmt->close();
						$show_pagination = false;
					} else if ($search !== '') {
						$stmt = $connection->prepare(
							"SELECT jobs.*, users.company_name, users.company_image
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
						$sql = "SELECT jobs.*, users.company_name, users.company_image FROM jobs LEFT JOIN users ON jobs.user_id = users.id WHERE jobs.approved = 1 ORDER BY jobs.id DESC LIMIT $items_per_page OFFSET $offset";
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
							// Fetch company image for the company name (not just the job poster)
							$company_logo_src = '';
							$company_name = $job['company_name'] ?? '';
							if (!empty($company_name)) {
								$logo_stmt = $connection->prepare("SELECT company_image FROM users WHERE company_name = ? AND company_image IS NOT NULL AND company_image != '' LIMIT 1");
								$logo_stmt->bind_param('s', $company_name);
								$logo_stmt->execute();
								$logo_stmt->bind_result($company_image);
								if ($logo_stmt->fetch() && !empty($company_image)) {
									$company_logo_src = '/tues-Internship-2025-aklas/' . ltrim($company_image, '/');
								}
								$logo_stmt->close();
							}
							if (empty($company_logo_src)) {
								$company_logo_src = 'https://i.imgur.com/ZbILm3F.png';
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
										<?php
										// Fetch categories for this job
										$cat_stmt = $connection->prepare(
											"SELECT c.name FROM job_categories jc
											 JOIN categories c ON jc.category_id = c.id
											 WHERE jc.job_id = ?"
										);
										$cat_stmt->bind_param('i', $job['id']);
										$cat_stmt->execute();
										$cat_result = $cat_stmt->get_result();
										$categories = [];
										while ($row = $cat_result->fetch_assoc()) {
											$categories[] = $row['name'];
										}
										$cat_stmt->close();
										$category_names = implode(', ', $categories);
										?>
										<span class="category-type"><?php echo htmlspecialchars($category_names); ?></span>
									</div>
									<div class="job-details">
										<span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
										<span class="job-type">Monthly Salary: <?php echo htmlspecialchars($job['salary']); ?> лв</span>
									</div>
								
								</div>
								<div class="job-logo">
									<div class="job-logo-box">
                                        <img src="<?= htmlspecialchars($company_logo_src) ?>" alt="Company Logo">
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