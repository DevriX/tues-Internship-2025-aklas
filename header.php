<?php
// Shared header partial. Assumes $user_logged_in, $display_name, $current_page are set in the including file.
?>
<header class="site-header main-header-navbar">
    <div class="row site-header-inner">
        <div class="site-header-branding">
            <h1 class="site-title"><a href="/tues-Internship-2025-aklas/index.php">Job Offers</a></h1>
        </div>
        <nav class="site-header-navigation">
            <ul class="menu">
                <li class="menu-item<?php if($current_page == 'index.php') echo ' current-menu-item'; ?>">
                    <a href="/tues-Internship-2025-aklas/index.php">Home</a>
                </li>
                <?php if (isset($user_logged_in) && $user_logged_in): ?>
                    <li class="menu-item">
                        <span style="color: black; margin-right: 10px;">Hi, <?= htmlspecialchars($display_name) ?></span>
                    </li>
                    <li class="menu-item">
                        <a href="/tues-Internship-2025-aklas/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="menu-item">
                        <a href="/tues-Internship-2025-aklas/register.php">Register</a>
                    </li>
                    <li class="menu-item">
                        <a href="/tues-Internship-2025-aklas/login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header> 