<?php
require_once 'require_admin.php';
require_once 'require_login.php';

$update_error = false;
$edit_success = false;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'auth-user.php';
require_once 'dbconn.php';
$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);
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

// Handle Add New Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category_name'])) {
    $new_category = trim($_POST['new_category_name']);
    $error = '';
    if ($new_category !== '') {
        // Check for duplicate (case-insensitive)
        $stmt = $connection->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(?)');
        $stmt->bind_param('s', $new_category);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ($count > 0) {
            $error = 'Category already exists!';
        } else {
            $stmt = $connection->prepare('INSERT INTO categories (name) VALUES (?)');
            $stmt->bind_param('s', $new_category);
            $stmt->execute();
            $stmt->close();
            header('Location: category-dashboard.php');
            exit;
        }
    }

	$update_error = true;
}
// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $delete_id = intval($_POST['delete_category_id']);
    $error = '';
    try {
        $stmt = $connection->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->bind_param('i', $delete_id);
        $stmt->execute();
        $stmt->close();
        header('Location: category-dashboard.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        if (strpos($e->getMessage(), 'a foreign key constraint fails') !== false) {
            $error = "Category can't be deleted";
            $update_error = true;
        } else {
            throw $e;
        }
    }
}
// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category_id'], $_POST['edit_category_name'])) {
    $edit_id = intval($_POST['edit_category_id']);
    $edit_name = trim($_POST['edit_category_name']);
    $error = '';
    if ($edit_name !== '') {
        // Check for duplicate (case-insensitive, excluding self)
        $stmt = $connection->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(?) AND id != ?');
        $stmt->bind_param('si', $edit_name, $edit_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ($count > 0) {
            $error = 'Category already exists!';
            $update_error = true;
        } else {
            $stmt = $connection->prepare('UPDATE categories SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $edit_name, $edit_id);
            $stmt->execute();
            $stmt->close();
            $edit_success = true;
            // Show popup-edit after redirect
            $_SESSION['edit_success'] = true;
            header('Location: category-dashboard.php');
            exit;
        }
    }
}

include 'header.php';
include 'vertical-navbar.php';
include 'pagination.php';

// Pagination setup
$items_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $connection->prepare("SELECT * FROM categories WHERE name LIKE CONCAT('%', ?, '%') ORDER BY name ASC");
    $stmt->bind_param('s', $search);
    $stmt->execute();
    $categories = $stmt->get_result();
    $total_items = $categories->num_rows;
    // Show all on one page, no pagination
    $stmt->close();
} else {
    $total_items_result = mysqli_query($connection, "SELECT COUNT(*) FROM categories");
    $total_items = mysqli_fetch_row($total_items_result)[0];
    $offset = ($page - 1) * $items_per_page;
    $categories = mysqli_query($connection, "SELECT * FROM categories ORDER BY name ASC LIMIT $items_per_page OFFSET $offset");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Categories</title>
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="stylesheet" href="./css/master.css">
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">
		<main class="site-main">
			<section class="section-fullwidth section-jobs-dashboard">
				<div class="row">
					<div class="jobs-dashboard-header">
						<div class="primary-container">
							<ul class="tabs-menu">
								<li class="menu-item">
									<a href="dashboard.php">Jobs</a>
								</li>
								<li class="menu-item current-menu-item">
									<a href="#">Categories</a>
								</li>
							</ul>
						</div>
						<div class="secondary-container">
							<div class="form-box category-form">
								<form method="POST">
									<div class="flex-container justified-vertically">
										<div class="form-field-wrapper">
											<input type="text" name="new_category_name" placeholder="Enter Category To Create..." required/>
										</div>
										<button class="button" type="submit">Add New</button>
									</div>
									<?php if ($update_error): ?>
										<div id="popup-error" class="popup-error" style="margin-top:10px; color:white; font-weight:bold;"> <?= htmlspecialchars($error) ?> </div>
									<?php endif; ?>
									<?php if (isset($_SESSION['edit_success']) && $_SESSION['edit_success']): ?>
										<div id="popup-edit" class="popup-success" style="margin-top:10px; color:white; font-weight:bold;">Category updated successfully.</div>
										<?php unset($_SESSION['edit_success']); ?>
									<?php endif; ?>
								</form>
							</div>
						</div>
					</div>
					<ul class="jobs-listing">
<?php
while ($cat = mysqli_fetch_assoc($categories)):
?>
						<li class="job-card">
							<div class="job-primary">
							<?php if (isset($_GET['edit']) && $_GET['edit'] == $cat['id']): ?>
								<form method="POST" style="display:flex; align-items:center; gap:10px;">
									<input type="hidden" name="edit_category_id" value="<?= $cat['id'] ?>" />
									<input type="text" name="edit_category_name" value="<?= htmlspecialchars($cat['name']) ?>" required style="padding:6px 10px; border-radius:6px; border:1px solid #bbb; font-size:1.1em;" />
									<button class="button button-inline" type="submit" style="background:#1976d2; color:#fff; border-radius:6px;">Save</button>
									<a href="category-dashboard.php" class="button button-inline" style="background:#eee; color:#333; border-radius:6px;">Cancel</a>
								</form>
								<?php else: ?>
									<h2 class="job-title" style="margin-bottom:0;"> <?= htmlspecialchars($cat['name']) ?> </h2>
								<?php endif; ?>
							</div>
							<div class="job-secondary centered-content">
								<div class="job-actions" style="display:flex; gap:10px;">
									<form method="POST" style="display:inline;">
										<input type="hidden" name="delete_category_id" value="<?= $cat['id'] ?>" />
										<button class="button button-inline" type="submit" style="background:#d32f2f; color:#fff; border-radius:6px;" onclick="return confirm('Delete this category?')">Delete</button>
									</form>
									<a href="category-dashboard.php?edit=<?= $cat['id'] ?>&page=<?= $page; ?>" class="button button-inline" style="background:#fbc02d; color:#333; border-radius:6px;">Edit</a>
								</div>
							</div>
						</li>
					<?php endwhile; ?>
					</ul>
					<?php if (!$search) render_pagination($total_items, $items_per_page, $page, basename($_SERVER['PHP_SELF'])); ?>
				</div>
			</section>
		</main>
	</div>
	<script src="main.js"></script>

<?php if ($update_error): ?>
	<script>
		setTimeout(() => {
			const popup = document.getElementById('popup-error');
			if (popup) popup.classList.add('hide');
		}, 3000);
	</script>
<?php endif; ?>
<?php if (isset($edit_success) && $edit_success): ?>
<script>
	setTimeout(() => {
		const popup = document.getElementById('popup-edit');
		if (popup) popup.classList.add('hide');
	}, 3000);
</script>
<?php endif; ?>
</body>
</html>