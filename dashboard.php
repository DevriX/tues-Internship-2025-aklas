<?php
require_once 'require_admin.php';
require_once 'require_login.php';
require_once 'dbconn.php';
include 'auth-user.php';
include 'job-listing-functions.php';

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
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$show_pagination = true;
if ($category_filter) {
    $stmt = $connection->prepare(
        "SELECT jobs.*
         FROM jobs
         JOIN job_categories jc ON jobs.id = jc.job_id
         JOIN categories c ON jc.category_id = c.id
         WHERE LOWER(c.name) = LOWER(?)
         ORDER BY jobs.id DESC"
    );
    $stmt->bind_param('s', $category_filter);
    $stmt->execute();
    $jobs_result = $stmt->get_result();
    $total_items = $jobs_result->num_rows;
    $stmt->close();
    $show_pagination = false;
} else if ($search !== '') {
    $stmt = $connection->prepare(
        "SELECT jobs.*
         FROM jobs
         LEFT JOIN job_categories jc ON jobs.id = jc.job_id
         LEFT JOIN categories c ON jc.category_id = c.id
         WHERE jobs.title LIKE CONCAT('%', ?, '%')
            OR jobs.description LIKE CONCAT('%', ?, '%')
            OR c.name LIKE CONCAT('%', ?, '%')
         GROUP BY jobs.id
         ORDER BY jobs.id DESC"
    );
    $stmt->bind_param('sss', $search, $search, $search);
    $stmt->execute();
    $jobs_result = $stmt->get_result();
    $total_items = $jobs_result->num_rows;
    $stmt->close();
    $show_pagination = false;
} else {
    $total_items_result = mysqli_query($connection, "SELECT COUNT(*) FROM jobs");
    $total_items = mysqli_fetch_row($total_items_result)[0];
    $offset = ($page - 1) * $items_per_page;
    $jobs_result = mysqli_query(
        $connection,
        "SELECT jobs.*, users.company_name, users.company_image FROM jobs LEFT JOIN users ON jobs.user_id = users.id ORDER BY approved ASC, created_at DESC LIMIT $items_per_page OFFSET $offset"
    );
    $show_pagination = true;
}

// Fetch categories for bulk assignment
$categories_bulk = [];
$cat_result_bulk = mysqli_query($connection, 'SELECT id, name FROM categories ORDER BY name ASC');
while ($cat = mysqli_fetch_assoc($cat_result_bulk)) {
    $categories_bulk[] = $cat;
}

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
									<a href="category-dashboard.php">Categories</a>
								</li>
							</ul>
						</div>
						<div class="secondary-container">
							<div class="flex-container centered-vertically">
								<form class="search-form-wrapper" method="get" action="dashboard.php" style="display:inline;">
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
						</div>
					</div>
					<!-- Bulk Edit Controls -->
					<div id="bulk-edit-controls" style="margin: 1em 0;">
						<button id="toggle-bulk-edit" type="button" class="button">Bulk Edit</button>
						<form id="bulk-assign-form" style="display:none; margin-top: 1em; align-items:center; gap:1em;">
							<select id="bulk-category-select" name="category_id" required>
								<option value="">Assign Category…</option>
								<?php foreach ($categories_bulk as $cat): ?>
									<option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
								<?php endforeach; ?>
							</select>
							<button type="submit" class="button">Assign to Selected Jobs</button>
						</form>
					</div>
					<ul class="jobs-listing">
<?php
$bulk_edit_mode = true; // JS will toggle this
if ($category_filter || $search !== '') {
    // Show jobs from $jobs_result directly
    while ($job = $jobs_result->fetch_assoc()) {
        if (empty($job['title']) || empty($job['location'])) continue;
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
        <li class="job-card">
            <?php if ($bulk_edit_mode): ?>
                <input type="checkbox" class="bulk-job-checkbox" value="<?php echo $job['id']; ?>" style="display:none; margin-right:10px;" />
            <?php endif; ?>
            <div class="job-primary">
                <h2 class="job-title">
                    <a href="single.php?id=<?php echo $job['id']; ?>">
                        <?php echo htmlspecialchars($job['title']); ?>
                    </a>
                </h2>
                <div class="job-meta">
                    <span class="meta-company"><?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?></span>
                    <span class="meta-date">Posted <?php echo htmlspecialchars($job['created_at']); ?></span>
                </div>
                <div>
                    <span class="category-type"><?php echo htmlspecialchars($category_names); ?></span>
                </div>
                <div class="job-details">
                    <span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
                    <span class="job-type">Monthly Salary: <?php echo htmlspecialchars($job['salary']); ?> лв</span>
                </div>
            </div>
            <div class="job-logo">
                <div class="job-logo-box">
                    <img src="<?= !empty($job['company_image']) ? htmlspecialchars($job['company_image']) : 'https://i.imgur.com/ZbILm3F.png' ?>" alt="Company Logo">
                </div>
            </div>
        </li>
        <?php
    }
} else {
    // Use the paginated function
    render_jobs_listing($connection, $items_per_page, $offset, $current_page);
}
?>
					</ul>
					<?php if ($show_pagination && $total_items > $items_per_page) render_pagination($total_items, $items_per_page, $page, basename($_SERVER['PHP_SELF'])); ?>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Bulk Edit Mode Toggle
  const toggleBtn = document.getElementById('toggle-bulk-edit');
  const bulkForm = document.getElementById('bulk-assign-form');
  let bulkMode = false;
  toggleBtn.addEventListener('click', function() {
    bulkMode = !bulkMode;
    document.querySelectorAll('.bulk-job-checkbox').forEach(cb => {
      cb.style.display = bulkMode ? 'inline-block' : 'none';
      cb.checked = false;
    });
    bulkForm.style.display = bulkMode ? 'flex' : 'none';
    toggleBtn.textContent = bulkMode ? 'Exit Bulk Edit' : 'Bulk Edit';
  });

  // Bulk Assign Submit
  bulkForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const selected = Array.from(document.querySelectorAll('.bulk-job-checkbox:checked')).map(cb => cb.value);
    const categoryId = document.getElementById('bulk-category-select').value;
    if (!categoryId || selected.length === 0) {
      alert('Please select at least one job and a category.');
      return;
    }
    fetch('bulk-assign-category.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ job_ids: selected, category_id: categoryId })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Category assigned successfully!');
        location.reload();
      } else {
        alert('Error: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(() => alert('Request failed.'));
  });
});

// Restore Google Maps modal and job details modal functionality

document.addEventListener('DOMContentLoaded', function() {
  // Location link click: open Google Maps modal, stop propagation
  document.querySelectorAll('.job-location-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const location = link.getAttribute('data-location');
      const iframe = document.getElementById('maps-iframe');
      const modal = document.getElementById('maps-modal');
      if (iframe && modal) {
        iframe.src = `https://www.google.com/maps?q=${encodeURIComponent(location)}&output=embed`;
        modal.style.display = 'flex';
      }
      // Optionally update the maps-link href
      const mapsLink = document.getElementById('maps-link');
      if (mapsLink) {
        mapsLink.href = `https://www.google.com/maps?q=${encodeURIComponent(location)}`;
      }
    });
  });

  // Job card click: open job details modal
  document.querySelectorAll('.job-card').forEach(function(card) {
    card.addEventListener('click', function(e) {
      // Prevent opening modal if clicking on approve/reject buttons, location link, or bulk edit checkbox
      if (
        e.target.closest('form') ||
        e.target.classList.contains('job-location-link') ||
        (e.target.classList && e.target.classList.contains('bulk-job-checkbox'))
      ) return;
      const job = {
        title: card.getAttribute('data-title'),
        company: card.getAttribute('data-company'),
        location: card.getAttribute('data-location'),
        salary: card.getAttribute('data-salary'),
        description: card.getAttribute('data-description'),
        created_at: card.getAttribute('data-created_at'),
        approved: card.getAttribute('data-approved') === '1',
        categories: card.getAttribute('data-categories')
      };
      openJobDetailsModal(job);
    });
  });
});
</script>
</body>
</html>