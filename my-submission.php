<?php
require_once 'require_login.php';
require_once 'dbconn.php';
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

// Fetch all submissions for the logged-in user
$submissions = [];
if ($user_logged_in && isset($user['id'])) {
    $stmt = $connection->prepare("
        SELECT id, company_name, job_title
        FROM apply_submissions
        WHERE user_id = ?
        ORDER BY applied_at DESC
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $stmt->bind_result($id, $company_name, $job_title);
    while ($stmt->fetch()) {
        $submissions[] = [
            'id' => $id,
            'company_name' => $company_name,
            'job_title' => $job_title,
        ];
    }
    $stmt->close();
}

//Functionality of the delete button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    // Make sure the user can only delete their own submissions!
    $stmt = $connection->prepare("DELETE FROM apply_submissions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user['id']);
    $stmt->execute();
    $stmt->close();
    // Optionally, reload the page to update the list
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>View Submission</title>
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="stylesheet" href="./css/master.css">
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">

		<main class="site-main">
			<section class="section-fullwidth">
				<div class="row">
    <div class="flex-container centered-vertically centered-horizontally" style="flex-direction: column; width: 100%;">
        <?php if (count($submissions) > 0): ?>
            <?php foreach ($submissions as $submission): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="delete_button" value="1">
                    <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($submission['id']); ?>">
                    <div class="form-box box-shadow" style="width:700px; margin-bottom: 2rem; position: relative; min-height: 120px;">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap;">
                            <div style="flex: 1 1 0; min-width: 0; text-align: left; word-break: break-word;">
                                <h2 class="heading-title" style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 600; margin-top: 17px">
                                    <?php echo htmlspecialchars(($submission['company_name'] ?? 'Company') . ' - ' . ($submission['job_title'] ?? 'Position')); ?>
                                </h2>
                            </div>
                            <div style="margin-left: 2rem; display: flex; align-items: flex-start;">
                                <button type="submit" name="delete_button" class="button delete-application-btn">Delete Application</button>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#888;">You have not submitted any applications yet.</p>
        <?php endif; ?>
    </div>
</div>
			</section>	
		</main>
	</div>
	<script src="main.js"></script>

<!-- Confirmation Modal for Deleting Application -->
<div id="confirm-delete-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
  <div style="background:#fff; padding:2rem; border-radius:8px; min-width:300px; max-width:90vw; box-shadow:0 2px 16px rgba(0,0,0,0.2); text-align:center;">
    <h3 style="margin-bottom:1rem;">Confirm Deletion</h3>
    <p style="margin-bottom:2rem;">Are you sure you want to delete this application? This action cannot be undone.</p>
    <button id="confirm-delete-yes" class="button" style="margin-right:1rem;" name="confirm-delete-button">Yes, Delete</button>
    <button id="confirm-delete-no" class="button button-secondary">Cancel</button>
  </div>
</div>
<script>
let formToDelete = null;
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.delete-application-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      formToDelete = btn.closest('form');
      document.getElementById('confirm-delete-modal').style.display = 'flex';
    });
  });
  document.getElementById('confirm-delete-yes').onclick = function() {
    document.getElementById('confirm-delete-modal').style.display = 'none';
    if (formToDelete) {
      formToDelete.submit();
      formToDelete = null;
    }
  };
  document.getElementById('confirm-delete-no').onclick = function() {
    document.getElementById('confirm-delete-modal').style.display = 'none';
    formToDelete = null;
  };
  document.getElementById('confirm-delete-modal').onclick = function(e) {
    if (e.target === this) {
      this.style.display = 'none';
      formToDelete = null;
    }
  };
});
</script>
</body>
</html>