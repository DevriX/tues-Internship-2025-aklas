<?php
require_once 'config.php';
include_once 'auth-user.php';
$is_company_role = false;
// Make sure $user and $current_page are set before including this file
$is_logged_in = isset($user) && $user;
$is_admin = $is_logged_in && isset($user['is_admin']) && $user['is_admin'];

if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $token_hash = hash('sha256', $token);
    $stmt = $connection->prepare("
        SELECT u.id, u.company_role
        FROM login_tokens lt
        JOIN users u ON lt.user_id = u.id
        WHERE lt.token_hash = ? AND lt.expiry > NOW()
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $company_role);
        $stmt->fetch();
        $allowed_roles = ['HR', 'CEO', 'Manager', 'Owner'];
        if ($company_role && in_array(trim($company_role), $allowed_roles, true)) {
            $is_company_role = true;
        }
    }
    $stmt->close();
}

// Unseen company submissions counter logic should always run if user is company role
$unseen_count = 0;
if ($is_company_role && isset($user['id']) && !empty($user['company_name'])) {
    $user_company = trim(strtolower($user['company_name']));
    $seen_stmt = $connection->prepare("SELECT last_seen_submission_id FROM company_submission_seen WHERE user_id = ? AND company_name = ?");
    $seen_stmt->bind_param("is", $user['id'], $user_company);
    $seen_stmt->execute();
    $seen_stmt->bind_result($last_seen_id);
    $seen_stmt->fetch();
    $seen_stmt->close();
    // Count unseen submissions for this company
    $count_stmt = $connection->prepare("SELECT COUNT(*) FROM apply_submissions WHERE LOWER(TRIM(company_name)) = ? AND id > ?");
    $count_stmt->bind_param("si", $user_company, $last_seen_id);
    $count_stmt->execute();
    $count_stmt->bind_result($unseen_count);
    $count_stmt->fetch();
    $count_stmt->close();
}


$user_seen_value = null;

if ($is_logged_in && isset($user['id'])) {
    $user_id = $user['id'];

    $seen_stmt = $connection->prepare("
        SELECT seen
        FROM apply_submissions
        WHERE user_id = ?
        ORDER BY seen DESC
        LIMIT 1
    ");
    $seen_stmt->bind_param("i", $user_id);
    $seen_stmt->execute();
    $seen_stmt->bind_result($user_seen_value);
    $seen_stmt->fetch();
    $seen_stmt->close();
}

?>
<div id="vertical-navbar" class="vertical-navbar side-vertical-navbar">
    <!-- <button id="close-vertical-navbar" class="close-navbar-btn" aria-label="Close menu" style="position:absolute;top:10px;right:10px;font-size:2rem;background:none;border:none;cursor:pointer;z-index:2100;">&times;</button> -->
    <nav class="footer-vertical-menu">
    <a href="/<?= $project_path ?>/index.php" class="footer-vlink<?php if($current_page == 'index.php') echo ' active'; ?>">Home</a>
        <?php if ($is_logged_in && $is_admin): ?>
            <a href="/<?= $project_path ?>/dashboard.php" class="footer-vlink<?php if($current_page == 'dashboard.php') echo ' active'; ?>">Jobs Dashboard</a>
            <a href="/<?= $project_path ?>/submissions.php" class="footer-vlink<?php if($current_page == 'submissions.php') echo ' active'; ?>">Submissions</a>
        <?php endif; ?>


       <a href="/<?= $project_path ?>/my-submission.php?reset_seen=1" class="footer-vlink<?php if($current_page == 'my-submission.php') echo ' active'; ?>">
            My Submission
            <?php if (!is_null($user_seen_value) && $user_seen_value > 0): ?>
                <span style="background:#7c3aed;color:#fff;border-radius:1em;padding:0.2em 0.6em;font-size:0.9em;margin-left:0.5em;">
                    <?= htmlspecialchars($user_seen_value) ?>
                </span>
            <?php endif; ?>

        </a>

        <?php if ( $is_company_role ): ?>
        <a href="/<?= $project_path ?>/my-company-submissions.php" class="footer-vlink<?php if($current_page == 'my-company-submissions.php') echo ' active'; ?>">
            My Company Submissions
        <?php if ($unseen_count > 0): ?>
            <span style="background:#7c3aed;color:#fff;border-radius:1em;padding:0.1em 0.7em;font-size:1em;margin-left:0.5em;vertical-align:middle;display:inline-block;min-width:1.7em;text-align:center;">
                <?= $unseen_count ?>
            </span>
        <?php endif; ?>
        </a>
        <?php endif; ?>

        <a href="/<?= $project_path ?>/create-job.php" class="footer-vlink<?php if($current_page == 'create-job.php') echo ' active'; ?>">Create-Edit Job</a>
        <?php if($is_logged_in && $is_admin): ?>
            <a href="/<?= $project_path ?>/category-dashboard.php" class="footer-vlink<?php if($current_page == 'category-dashboard.php') echo ' active'; ?>">Category Dashboard</a>
        <?php endif; ?>
        <a href="/<?= $project_path ?>/profile.php" class="footer-vlink<?php if($current_page == 'profile.php') echo ' active'; ?>">My Profile</a>
        <?php if ($is_logged_in): ?>
            <a href="/<?= $project_path ?>/logout.php" class="footer-vlink<?php if($current_page == 'logout.php') echo ' active'; ?>">Logout</a>
        <?php else: ?>
            <a href="/<?= $project_path ?>/register.php" class="footer-vlink<?php if($current_page == 'register.php') echo ' active'; ?>">Register</a>
            <a href="/<?= $project_path ?>/login.php" class="footer-vlink blue-link<?php if($current_page == 'login.php') echo ' active'; ?>">Login</a>
        <?php endif; ?>
    </nav>
</div>