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
                <?php if (isset($user_logged_in) && $user_logged_in): ?>
                    <li class="menu-item">
                        <a style="color: black; margin-right: 10px;">Hi, <?= htmlspecialchars($display_name) ?></a>
                    </li>
                    <li style="color: black; margin-right: -100px;" class="menu-item">
                            <!-- Three dots (vertical ellipsis) button -->
                            <button id="menu-toggle-btn" class="menu-toggle-btn" aria-label="Open menu">
                                <svg width="28" height="28" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                            </button>
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