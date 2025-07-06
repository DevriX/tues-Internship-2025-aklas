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

$user_id = null;
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("SELECT user_id FROM login_tokens WHERE token_hash = ? AND expiry > NOW() LIMIT 1");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
    }
    $stmt->close();
}

if (!$user_id) {
    header('Location: login.php');
    exit;
}

$job_title = $_POST['job-title'] ?? null;
$location = $_POST['location'] ?? null;
$salary = $_POST['salary'] ?? null;
$description = $_POST['description'] ?? null;



$error_message = '';
$success_message = '';


// Insert job with user_id only (company info is in users table)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($job_title == null) {
        $error_message = 'Job title is required';
    } elseif ($location == null) {
        $error_message = 'Location is required';
    } elseif ($salary == null) {
        $error_message = 'Salary is required';
    } //Check if $job_title is made only out of letters
	elseif(filter_var(!ctype_alpha($job_title))){
		$error_message = "Job title should only be made by letters";
	 }  //Check if $salary is made only out of numbers
	elseif(filter_var($salary, FILTER_VALIDATE_INT) == false){
		$error_message = "Salary should only be made by numbers";
	} elseif ($salary && $location && $job_title) {
        $sql = "INSERT INTO jobs (title, location, salary, description, user_id) VALUES ('$job_title', '$location', '$salary', '$description', '$user_id')";
        if (mysqli_query($connection, $sql)) {
            $success_message = 'Job created SUCCESSFULLY, waiting for approval';
            // Clear form data only after successful submission
            $job_title = $location = $salary = $description = '';
        } else {
            $error_message = 'Error creating job. Please try again.';
        }
    }
}
?>

<?php
$update_success = false;
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
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">

		<main class="site-main">
			<section class="section-fullwidth">
				<div class="row">	
					<div class="flex-container centered-vertically centered-horizontally">
						<div class="form-box box-shadow">
							<div class="section-heading">
								<h2 class="heading-title">New job</h2>
							</div>
                            <?php if (!empty($error_message)): ?>
                                <div id="error-popup" class="popup-error">
                                    <?= htmlspecialchars($error_message) ?>
                                </div>
                                <script>
                                    setTimeout(function() {
                                        var popup = document.getElementById('error-popup');
                                        if (popup) popup.classList.add('hide');
                                    }, 3000);
                                </script>
                            <?php endif; ?>
                            <?php if (!empty($success_message)): ?>
                                <div id="success-popup" class="popup-success">
                                    <?= htmlspecialchars($success_message) ?>
                                </div>
                                <script>
                                    setTimeout(function() {
                                        var popup = document.getElementById('success-popup');
                                        if (popup) popup.classList.add('hide');
                                    }, 3000);
                                </script>
                            <?php endif; ?>
							<form action="create-job.php" method="POST">
								<div class="flex-container flex-wrap">
									<div class="form-field-wrapper width-large">
										<input type="text" placeholder="Job title*" name="job-title" value="<?= htmlspecialchars($job_title) ?>"/>
									</div>
									<div class="form-field-wrapper width-large">
										<input type="text" placeholder="Location*" name="location" value="<?= htmlspecialchars($location) ?>"/>
									</div>
									<div class="form-field-wrapper width-large">
										<input type="text" placeholder="Salary (in leva)*" name="salary" value="<?= htmlspecialchars($salary) ?>"/>
									</div>
									<div class="form-field-wrapper width-large">
										<textarea placeholder="Description" name="description"><?= htmlspecialchars($description) ?></textarea>
									</div>	
								</div>
								<button type="submit" class="button">
									Create
								</button>
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