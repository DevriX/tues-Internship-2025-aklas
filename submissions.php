<?php
require_once 'dbconn.php';
require_once 'require_admin.php';

$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.id, u.first_name, u.last_name
        FROM login_tokens lt 
        JOIN users u ON lt.user_id = u.id 
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $first_name, $last_name);
        $stmt->fetch();
        $user_logged_in = true;
        $display_name = $first_name;
		$display_first_name = $first_name;
		$display_last_name = $last_name;
    }
    $stmt->close();
}

include 'header.php';
include 'auth-user.php';
include 'vertical-navbar.php';
include 'pagination.php';
include 'submission-details-popup.php';

// Pagination setup
$items_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_items_result = mysqli_query($connection, "SELECT COUNT(*) FROM apply_submissions");
$total_items = mysqli_fetch_row($total_items_result)[0];
$max_page = max(1, ceil($total_items / $items_per_page));
$page = min($page, $max_page); // Prevent navigating to empty pages
$offset = ($page - 1) * $items_per_page;

// Remove rejected submissions from the database
$connection->query("DELETE FROM apply_submissions WHERE status = 'rejected'");

// Fetch submissions for current page
$submissions = [];
$stmt = $connection->prepare("
    SELECT a.id, u.first_name, u.last_name, u.email, u.phone_number, a.message, a.cv_file_path, a.applied_at, a.company_name, a.job_title
    FROM apply_submissions a
    JOIN users u ON a.user_id = u.id
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$stmt->bind_result($id, $fname, $lname, $email, $phone, $message, $cv, $applied_at, $company_name, $job_title);
while ($stmt->fetch()) {
    $files = json_decode($cv, true) ?: [];
    $submissions[] = [
        'id' => $id,
        'first_name' => $fname,
        'last_name' => $lname,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'files' => $files,
        'applied_at' => $applied_at,
		'company_name' => $company_name,
		'job_title' => $job_title
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Submissions</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="./css/master.css">
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">
		<main class="site-main">
			<section class="section-fullwidth">
				<div class="row">

					<div class="submission-form">
						<h3 style="text-align: center; font-weight: 600; font-size: 2rem; margin-bottom: 1.5rem;">
							Applicant Submissions
						</h3>
						<?php if (count($submissions) > 0): ?>
							<?php foreach ($submissions as $submission): ?>
								<div class="submission-entry">
									<span class="name">
										<?= htmlspecialchars($submission['first_name']) ?> <?= htmlspecialchars($submission['last_name']) ?>
									</span>
									<button class="view-btn"
										data-name="<?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name'], ENT_QUOTES) ?>"
										data-email="<?= htmlspecialchars($submission['email'], ENT_QUOTES) ?>"
										data-phone="<?= htmlspecialchars($submission['phone'], ENT_QUOTES) ?>"
										data-date="<?= htmlspecialchars($submission['applied_at'], ENT_QUOTES) ?>"
										data-files='<?= json_encode($submission['files'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
										data-company-name="<?= htmlspecialchars($submission['company_name'], ENT_QUOTES) ?>"
										data-job-title="<?= htmlspecialchars($submission['job_title'], ENT_QUOTES) ?>"
										data-cover="<?= htmlspecialchars($submission['message'], ENT_QUOTES) ?>"
									>View</button>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p style="color:white;">No submissions found.</p>
						<?php endif; ?>
					</div>

					<?php if ($max_page > 1): ?>
						<div class="jobs-pagination-wrapper">
							<div class="nav-links">
								<?php render_pagination($total_items, $items_per_page, $page, basename($_SERVER['PHP_SELF'])); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</section>
		</main>
	</div>
	<script src="main.js"></script>
	<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.view-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      let files = [];
      try {
        files = JSON.parse(btn.getAttribute('data-files'));
      } catch (err) {}
      const sub = {
        name: btn.getAttribute('data-name'),
        email: btn.getAttribute('data-email'),
        phone: btn.getAttribute('data-phone'),
        date: btn.getAttribute('data-date'),
        files: files,
        job_title: btn.getAttribute('data-job-title'),
        company_name: btn.getAttribute('data-company-name'),
        cover: btn.getAttribute('data-cover'),
      };
      openSubmissionDetailsModal(sub);
    });
  });
});
</script>
</body>
</html>
