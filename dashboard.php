<?php
require_once 'dbconn.php';
include 'auth-user.php';

// Handle Approve/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_job_id'])) {
        $job_id = intval($_POST['approve_job_id']);
        mysqli_query($connection, "UPDATE jobs SET approved = 1 WHERE id = $job_id");
    } elseif (isset($_POST['reject_job_id'])) {
        $job_id = intval($_POST['reject_job_id']);
        mysqli_query($connection, "DELETE FROM jobs WHERE id = $job_id");
    }
}

$user_logged_in = false;
$display_name = '';
$user = null;
$current_page = basename($_SERVER['PHP_SELF']);
$update_success = false;

$first_name = $last_name = $email = $phone = $description = $company_name = $company_site = '';

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.description, u.company_name, u.company_site, u.is_admin
        FROM login_tokens lt
        JOIN users u ON lt.user_id = u.id
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name, $email, $phone, $description, $company_name, $company_site, $is_admin);
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

if (!$user_logged_in) {
    header('Location: login.php');
    exit;
}

include 'header.php';
include 'vertical-navbar.php';
include 'pagination.php';

// Pagination setup
$items_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_items_result = mysqli_query($connection, "SELECT COUNT(*) FROM jobs");
$total_items = mysqli_fetch_row($total_items_result)[0];
$offset = ($page - 1) * $items_per_page;


// Fetch jobs for current page
$jobs_result = mysqli_query($connection, "SELECT * FROM jobs LIMIT $items_per_page OFFSET $offset");
include 'job-details-popup.php';
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
<?php
while ($job = mysqli_fetch_assoc($jobs_result)):
    if (empty($job['title']) || empty($job['location']) || empty($job['salary'])) {
        continue;
    }
    // Fetch company name for this job (if needed)
    $company = '';
    $user_id = intval($job['user_id']);
    $company_query = mysqli_query($connection, "SELECT company_name FROM users WHERE id = $user_id LIMIT 1");
    if ($company_row = mysqli_fetch_assoc($company_query)) {
        $company = $company_row['company_name'];
    }
?>
						<li class="job-card" 
							data-title="<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>"
							data-company="<?= htmlspecialchars($company, ENT_QUOTES) ?>"
							data-location="<?= htmlspecialchars($job['location'], ENT_QUOTES) ?>"
							data-salary="<?= htmlspecialchars($job['salary'], ENT_QUOTES) ?>"
							data-description="<?= htmlspecialchars($job['description'], ENT_QUOTES) ?>"
							data-created_at="<?= htmlspecialchars($job['created_at'], ENT_QUOTES) ?>"
							data-approved="<?= $job['approved'] ? '1' : '0' ?>"
							style="cursor:pointer;"
						>
							<div class="job-primary">
								<h2 class="job-title"><?= htmlspecialchars($job['title']) ?></h2>
								<div class="job-meta">
									<span class="meta-company">User ID: <?= htmlspecialchars($job['user_id']) ?></span>
								</div>
								<div class="job-details">
									<span class="job-location">Location: <?= htmlspecialchars($job['location']) ?></span>
									<span class="job-salary">Salary: <?= htmlspecialchars($job['salary']) ?></span>
								</div>
							</div>
							<div class="job-secondary">
								<?php if (!$job['approved']): ?>
									<form method="POST" style="display:inline;">
										<input type="hidden" name="approve_job_id" value="<?= $job['id'] ?>">
										<button type="submit" class="btn-approve">Approve</button>
									</form>
									<form method="POST" style="display:inline;">
										<input type="hidden" name="reject_job_id" value="<?= $job['id'] ?>">
										<button type="submit" class="btn-reject">Reject</button>
									</form>
								<?php else: ?>
									<span class="approved-label">Approved</span>
								<?php endif; ?>
							</div>
						</li>
					<?php endwhile; ?>
					</ul>
					<?php render_pagination($total_items, $items_per_page, $page, basename($_SERVER['PHP_SELF'])); ?>
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
<script src="main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.job-card').forEach(function(card) {
    card.addEventListener('click', function(e) {
      // Prevent opening modal if clicking on approve/reject buttons
      if (e.target.closest('form')) return;
      const job = {
        title: card.getAttribute('data-title'),
        company: card.getAttribute('data-company'),
        location: card.getAttribute('data-location'),
        salary: card.getAttribute('data-salary'),
        description: card.getAttribute('data-description'),
        created_at: card.getAttribute('data-created_at'),
        approved: card.getAttribute('data-approved') === '1',
      };
      openJobDetailsModal(job);
    });
  });
});
</script>
</body>
</html>