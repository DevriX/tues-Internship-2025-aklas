<?php
require_once 'config.php';
// Shared header partial. Assumes $user_logged_in, $display_name, $current_page are set in the including file.
?>
<header class="site-header main-header-navbar">
    <div class="row site-header-inner">
        <div class="site-header-branding">
            <h2 class="section-heading" style="color: #7c3aed !important; font-size:2.2rem; font-weight:700; text-align:center; margin-bottom:2.2rem; margin-top:2.5rem;">
              <a href="/<?= $project_path ?>/index.php" style="color: #7c3aed; text-decoration: none;">Job Offers</a>
            </h2>
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
                        <a href="/<?= $project_path ?>/register.php">Register</a>
                    </li>
                    <li class="menu-item">
                        <a href="/<?= $project_path ?>/login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>