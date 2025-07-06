<?php
session_start();	
require 'dbconn.php';

$user_logged_in = false;
$display_name = '';
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page !== 'register.php' && $current_page !== 'login.php'):

if (!isset($_SESSION['user_id']) || !isset($_COOKIE['login_token'])) {
	die('You must be logged in to apply.');
}

// Validate login token
$token = $_COOKIE['login_token'];
$token_hash = hash('sha256', $token);

$stmt = $connection->prepare("
	SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number
	FROM login_tokens lt 
	JOIN users u ON lt.user_id = u.id 
	WHERE lt.token_hash = ? AND lt.expiry > NOW()
");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
	$stmt->bind_result($user_id, $first_name, $last_name, $email, $phone_number);
	$stmt->fetch();
	$user_logged_in = true;
} else {
	die('Invalid login session.');
}
$stmt->close();

	// Get job_id from query param
			$job_id = 0;
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$job_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		} elseif (isset($_GET['id'])) {
			$job_id = (int) $_GET['id'];
		}

	// Optional: check if the job exists
	$stmt = $connection->prepare("SELECT id FROM jobs WHERE id = ?");
	$stmt->bind_param("i", $job_id);
	$stmt->execute();
	$stmt->store_result();
	if ($stmt->num_rows === 0) {
		die("Invalid job ID.");
	}
	$stmt->close();

include 'header.php';
include 'auth-user.php';
include 'vertical-navbar.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$message = mysqli_real_escape_string($connection, $_POST['message']);
	$cv_file_path = null;
	$job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;

	// Handle file upload
	if (isset($_FILES['cv_file_path']) && $_FILES['cv_file_path']['error'] == UPLOAD_ERR_OK) {
		$upload_dir = 'uploads/';
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
		$filename = basename($_FILES['cv_file_path']['name']);
		$unique_filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $filename;
		$cv_file_path = $upload_dir . $unique_filename;
		move_uploaded_file($_FILES['cv_file_path']['tmp_name'], $cv_file_path);
	}

	// Insert application with job_id
	$sql = "INSERT INTO apply_submissions 
		(user_id, job_id, first_name, last_name, email, phone_number, message, cv_file_path, applied_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
	$stmt = $connection->prepare($sql);
	$stmt->bind_param("iissssss", $user_id, $job_id, $first_name, $last_name, $email, $phone_number, $message, $cv_file_path);

	if ($stmt->execute()) {
		echo "<p>Application submitted successfully!</p>";
	} else {
		echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
	}
	$stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Submit Application</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="./css/master.css">
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">
		<?php include 'vertical-navbar.php'; ?>

		<main class="site-main">
			<section class="section-fullwidth">
				<div class="row">	
					<div class="flex-container centered-vertically centered-horizontally">
						<div class="form-box box-shadow">
							<div class="section-heading">
								<h2 class="heading-title">Submit application to Company Name</h2>
							</div>

							<form method="POST" enctype="multipart/form-data" action="">
								<input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">

								<div class="flex-container justified-horizontally flex-wrap">

									<!-- Uneditable Inputs + Hidden Values -->
									<div class="form-field-wrapper width-medium">
										<input type="text" value="<?php echo htmlspecialchars($first_name); ?>" disabled />
										<input type="hidden" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" />
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="text" value="<?php echo htmlspecialchars($last_name); ?>" disabled />
										<input type="hidden" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" />
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled />
										<input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" />
									</div>
									<div class="form-field-wrapper width-medium">
										<input type="text" value="<?php echo htmlspecialchars($phone_number); ?>" disabled />
										<input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" />
									</div>

									<!-- Editable Fields -->
									<div class="form-field-wrapper width-large">
										<textarea name="message" placeholder="Custom Message*" required></textarea>
									</div>
									<div class="form-field-wrapper width-large">
										<input type="file" name="cv_file_path" />
									</div>
								</div>

								<button class="button" type="submit">Submit</button>
							</form>

						</div>
					</div>
				</div>
			</section>	
		</main>
	</div>
	<script src="main.js"></script>
</body>
</html>

<?php endif; ?>
