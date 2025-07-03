<?php
session_start();
require 'dbconn.php'; 

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    }

    if (!$password) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        
        $stmt = $connection->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;

                
                header('Location: index.html');
                exit;
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "Email not found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Jobs</title>
	<link rel="preconnect" href="https://fonts.gstatic.com" />
	<link rel="stylesheet" href="./css/master.css" />
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet" />
</head>
<body>
	<div class="site-wrapper">
		<header class="site-header">
			<div class="row site-header-inner">
				<div class="site-header-branding">
					<h1 class="site-title"><a href="/tues-Internship-2025-aklas/index.html">Job Offers</a></h1>
				</div>
				<nav class="site-header-navigation">
					<ul class="menu">
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/index.html">Home</a>					
						</li>
						<li class="menu-item">
							<a href="/tues-Internship-2025-aklas/register.php">Register</a>
						</li>
						<li class="menu-item current-menu-item">
							<a href="/tues-Internship-2025-aklas/login.php">Login</a>					
						</li>
					</ul>
				</nav>
				<button class="menu-toggle">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path fill="currentColor" class='menu-toggle-bars' d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
				</button>
			</div>
		</header>

		<main class="site-main">
			<section class="section-fullwidth section-login">
				<div class="row">	
					<div class="flex-container centered-vertically centered-horizontally">
						<div class="form-box box-shadow">
							<div class="section-heading">
								<h2 class="heading-title">Login</h2>
							</div>

                            <?php if (!empty($errors)): ?>
                                <div style="color:red; margin-bottom:1em;">
                                    <ul>
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

							<form action="login.php" method="POST" novalidate>
								<div class="form-field-wrapper">
									<input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
								</div>
								<div class="form-field-wrapper">
									<input type="password" name="password" placeholder="Password" required />
								</div>
								<button type="submit" class="button">
									Login
								</button>
							</form>
							<a href="#" class="button button-inline">Forgot Password</a>
						</div>
					</div>
				</div>
			</section>	
		</main>
		<footer class="site-footer">
			<div class="row">
				<p>Copyright 2020 | Developer links: 
					<a href="/tues-Internship-2025-aklas/index.html">Home</a>,
					<a href="/tues-Internship-2025-aklas/dashboard.html">Jobs Dashboard</a>,
					<a href="/tues-Internship-2025-aklas/single.html">Single</a>,
					<a href="/tues-Internship-2025-aklas/login.php">Login</a>,
					<a href="/tues-Internship-2025-aklas/register.php">Register</a>,
					<a href="/tues-Internship-2025-aklas/submissions.html">Submissions</a>,
					<a href="/tues-Internship-2025-aklas/apply-submission.html">Apply Submission</a>,
					<a href="/tues-Internship-2025-aklas/view-submission.html">View Submission</a>,
					<a href="/tues-Internship-2025-aklas/create-job.php">Create-Edit Job</a>,
					<a href="/tues-Internship-2025-aklas/category-dashboard.html">Category Dashboard</a>,
					<a href="/tues-Internship-2025-aklas/profile.html">My Profile</a>
				</p>
			</div>
		</footer>
	</div>
</body>
</html>
