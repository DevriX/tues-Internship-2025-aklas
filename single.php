<?php
require 'dbconn.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        SELECT jobs.*, users.company_name, users.company_site 
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

// Fetch current job's categories
$current_job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category_ids = [];
if ($current_job_id) {
    $cat_stmt = $connection->prepare(
        "SELECT category_id FROM job_categories WHERE job_id = ?"
    );
    $cat_stmt->bind_param('i', $current_job_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    while ($row = $cat_result->fetch_assoc()) {
        $category_ids[] = $row['category_id'];
    }
    $cat_stmt->close();
}
$related_jobs = [];
if (!empty($category_ids)) {
    // Find other jobs with these categories, count matches, exclude current job
    $in = implode(',', array_fill(0, count($category_ids), '?'));
    $types = str_repeat('i', count($category_ids) + 1); // +1 for current_job_id
    $sql = "
        SELECT j.*, COUNT(*) as match_count
        FROM job_categories jc
        JOIN jobs j ON jc.job_id = j.id
        WHERE jc.category_id IN ($in) AND jc.job_id != ?
        GROUP BY jc.job_id
        ORDER BY match_count DESC, j.created_at DESC
        LIMIT 5
    ";
    $stmt = $connection->prepare($sql);
    $params = array_merge($category_ids, [$current_job_id]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($related_job = $result->fetch_assoc()) {
        if (empty($related_job['approved']) || !$related_job['approved']) continue; // Only show approved jobs
        // Fetch categories for each related job
        $cat_stmt = $connection->prepare(
            "SELECT c.name FROM job_categories jc JOIN categories c ON jc.category_id = c.id WHERE jc.job_id = ?"
        );
        $cat_stmt->bind_param('i', $related_job['id']);
        $cat_stmt->execute();
        $cat_result = $cat_stmt->get_result();
        $categories = [];
        while ($row = $cat_result->fetch_assoc()) {
            $categories[] = $row['name'];
        }
        $cat_stmt->close();
        $related_job['categories'] = $categories;
        $related_jobs[] = $related_job;
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
											<span class="meta-company"><?php echo htmlspecialchars($job['company_name'] ?? 'No Company'); ?></span>
											<span class="meta-date">Posted on <?php echo htmlspecialchars($job['created_at']); ?></span>
										</div>
										<div class="job-details">
											<span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
											<span class="job-price"><?php echo htmlspecialchars($job['salary']); ?> лв</span>
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


							<a href="apply-submission.php?id=<?php echo $job['id']; ?>" class="blue-apply-button">
   							 <?php echo 'Apply Submission'; ?>
							</a>

							<?php if (!empty($job['company_site'])): ?>
								<a href="<?= htmlspecialchars($job['company_site']) ?>" target="_blank">
									<?= parse_url($job['company_site'], PHP_URL_HOST) ?: 'Company Website' ?>
								</a>
							<?php endif; ?>
						</aside>
					</div>
					<?php else: ?>
						<p>Job not found.</p>
					<?php endif; ?>
				</div>
			</section>
			<section class="section-fullwidth">
				<div class="row">
					<h2 class="section-heading">Other related jobs:</h2>
					<ul class="jobs-listing">
						<?php foreach ($related_jobs as $job): ?>
						<li class="job-card">
							<div class="job-primary">
								<h2 class="job-title"><a href="single.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title']) ?></a></h2>
								<div class="job-meta">
									<span class="meta-company"><?= htmlspecialchars($job['company_name'] ?? 'Unknown Company') ?></span>
									<span class="meta-date">Posted <?= htmlspecialchars($job['created_at']) ?></span>
								</div>
								<div>
									<span class="category-type"><?= htmlspecialchars(implode(', ', $job['categories'])) ?></span>
								</div>
								<div class="job-details">
									<span class="job-location"><?= htmlspecialchars($job['location']) ?></span>
									<span class="job-type">Monthly Salary: <?= htmlspecialchars($job['salary']) ?> лв</span>
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
	<script src="main.js"></script>
</body>
</html>
