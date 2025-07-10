<?php
require_once 'config.php';
// Make sure $user and $current_page are set before including this file
$is_logged_in = isset($user) && $user;
$is_admin = $is_logged_in && isset($user['is_admin']) && $user['is_admin'];
?>
<div id="vertical-navbar" class="vertical-navbar side-vertical-navbar">
    <!-- <button id="close-vertical-navbar" class="close-navbar-btn" aria-label="Close menu" style="position:absolute;top:10px;right:10px;font-size:2rem;background:none;border:none;cursor:pointer;z-index:2100;">&times;</button> -->
    <nav class="footer-vertical-menu">
    <a href="/<?= $project_path ?>/index.php" class="footer-vlink<?php if($current_page == 'index.php') echo ' active'; ?>">Home</a>
        <?php if ($is_logged_in && $is_admin): ?>
            <a href="/<?= $project_path ?>/dashboard.php" class="footer-vlink<?php if($current_page == 'dashboard.php') echo ' active'; ?>">Jobs Dashboard</a>
            <a href="/<?= $project_path ?>/submissions.php" class="footer-vlink<?php if($current_page == 'submissions.php') echo ' active'; ?>">Submissions</a>
        <?php endif; ?>
        <a href="/<?= $project_path ?>/my-submission.php" class="footer-vlink<?php if($current_page == 'my-submission.php') echo ' active'; ?>">My Submission</a>
        <a href="/<?= $project_path ?>/my-company-submissions.php" class="footer-vlink<?php if($current_page == 'my-company-submissions.php') echo ' active'; ?>">My Company Submissions</a>
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