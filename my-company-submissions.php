<?php
require_once 'require_login.php';
require_once 'dbconn.php';
include 'auth-user.php';

// Accept/Reject/In Progress logic must be before any output or includes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'dbconn.php';
    if (isset($_POST['accept_submission_id'])) {
        $submission_id = intval($_POST['accept_submission_id']);
        $stmt = $connection->prepare("UPDATE apply_submissions SET status = 'accepted' WHERE id = ?");
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['reject_submission_id'])) {
        $submission_id = intval($_POST['reject_submission_id']);
        $stmt = $connection->prepare("UPDATE apply_submissions SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['progress_submission_id'])) {
        $submission_id = intval($_POST['progress_submission_id']);
        $stmt = $connection->prepare("UPDATE apply_submissions SET status = 'in_progress' WHERE id = ?");
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Set variables for header.php compatibility
$user_logged_in = isset(
    $is_logged_in
) ? $is_logged_in : false;
$display_name = isset($user['first_name']) ? $user['first_name'] : '';
$current_page = basename($_SERVER['PHP_SELF']);

include 'header.php';
include_once 'vertical-navbar.php';
include 'submission-details-popup.php';

$user_company = isset($user['company_name']) ? trim(strtolower($user['company_name'])) : '';
$submissions = [];
$unique_check = [];

$unseen_count = 0;
$last_seen_id = 0;
if ($user_company && $user_logged_in) {
    // Fetch last seen submission id for this user/company
    $seen_stmt = $connection->prepare("SELECT last_seen_submission_id FROM company_submission_seen WHERE user_id = ? AND company_name = ?");
    $seen_stmt->bind_param("is", $user['id'], $user_company);
    $seen_stmt->execute();
    $seen_stmt->bind_result($last_seen_id);
    $seen_stmt->fetch();
    $seen_stmt->close();
}

$latest_submission_id = 0;
if ($user_company) {
    $stmt = $connection->prepare("
        SELECT a.id, a.user_id, a.job_id, u.first_name, u.last_name, u.email, u.phone_number, a.message, a.cv_file_path, a.applied_at, a.company_name, a.job_title, a.status, c.company_image, u.profile_image
        FROM apply_submissions a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN users c ON a.company_name = c.company_name
        WHERE LOWER(TRIM(a.company_name)) = ?
        ORDER BY a.applied_at DESC
    ");
    $stmt->bind_param("s", $user_company);
    $stmt->execute();
    $stmt->bind_result($id, $user_id, $job_id, $fname, $lname, $email, $phone, $message, $cv, $applied_at, $company_name, $job_title, $status, $company_image, $profile_image);
    while ($stmt->fetch()) {
        $files = json_decode($cv, true) ?: [];
        $unique_key = $user_id . '_' . $job_id;
        if (!isset($unique_check[$unique_key])) {
            $submissions[] = [
                'id' => $id,
                'first_name' => $fname,
                'last_name' => $lname,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'files' => $files,
                'applied_at' => $applied_at,
                'company_name' => $company_name,
                'job_title' => $job_title,
                'status' => $status,
                'company_image' => $company_image,
                'profile_image' => $profile_image
            ];
            $unique_check[$unique_key] = true;
            // Count unseen submissions
            if ($id > $last_seen_id) {
                $unseen_count++;
            }
            if ($id > $latest_submission_id) {
                $latest_submission_id = $id;
            }
        }
    }
    $stmt->close();
    // After rendering, update last seen to latest
    if ($latest_submission_id > 0 && $user_logged_in) {
        $up_stmt = $connection->prepare("REPLACE INTO company_submission_seen (user_id, company_name, last_seen_submission_id, last_seen_at) VALUES (?, ?, ?, NOW())");
        $up_stmt->bind_param("isi", $user['id'], $user_company, $latest_submission_id);
        $up_stmt->execute();
        $up_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Company Submissions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/master.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  
</head>
<body>
<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="row">
                <h3 style="text-align: center; font-weight: 700; font-size: 2.1rem; color: #4b0082; margin-bottom: 2.2rem;">
                    My Company Submissions
                    <?php if ($unseen_count > 0): ?>
                        <span style="color: #fff; background: #7c3aed; border-radius: 1em; padding: 0.2em 0.8em; font-size: 1.1rem; margin-left: 0.5em; vertical-align: middle;">
                            <?= $unseen_count ?> new
                        </span>
                    <?php endif; ?>
                </h3>
                <?php if (!$user_company): ?>
                    <div class="company-empty-state">
                        <svg class="company-empty-illustration" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="32" fill="#e6e6ff"/><rect x="18" y="28" width="28" height="16" rx="4" fill="#7c3aed"/><rect x="24" y="34" width="16" height="4" rx="2" fill="#fff"/></svg>
                        You do not have a company set in your profile.<br><a href="profile.php">Set your company</a> to view submissions.
                    </div>
                <?php elseif (count($submissions) > 0): ?>
                    <div class="company-submissions-grid">
                        <?php foreach ($submissions as $submission):
                            if (isset($submission['status']) && $submission['status'] === 'rejected') continue;
                            $initials = strtoupper(mb_substr($submission['first_name'],0,1).mb_substr($submission['last_name'],0,1));
                            $is_accepted = (isset($submission['status']) && $submission['status'] === 'accepted');
                            $is_in_progress = (isset($submission['status']) && $submission['status'] === 'in_progress');
                        ?>
                        <div class="company-card" style="<?php if ($is_accepted) echo 'background: #f3f3f3; opacity: 0.7; pointer-events: none;'; ?>">
                            <div class="company-card-accent"></div>
                            <div class="company-card-content">
                                <div class="company-card-avatar">
                                    <?php if (!empty($submission['profile_image'])): ?>
                                        <img src="<?= htmlspecialchars($submission['profile_image']) ?>" alt="Profile Image" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                    <?php else: ?>
                                        <?= $initials ?>
                                    <?php endif; ?>
                                </div>
                                <div class="company-card-info">
                                    <div class="company-card-name">
                                        <?= htmlspecialchars($submission['first_name']) ?> <?= htmlspecialchars($submission['last_name']) ?>
                                    </div>
                                    <div class="company-card-job">
                                        <?= htmlspecialchars($submission['job_title'] ?: '—') ?>
                                    </div>
                                    <div class="company-card-date">
                                        <?= date('M d, Y', strtotime($submission['applied_at'])) ?>
                                    </div>
                                </div>
                                <div class="company-card-actions">
                                    <button class="view-btn"
                                        data-name="<?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name'], ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($submission['email'], ENT_QUOTES) ?>"
                                        data-phone="<?= htmlspecialchars($submission['phone'], ENT_QUOTES) ?>"
                                        data-date="<?= htmlspecialchars($submission['applied_at'], ENT_QUOTES) ?>"
                                        data-files='<?= json_encode($submission['files'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                                        data-company-name="<?= htmlspecialchars($submission['company_name'], ENT_QUOTES) ?>"
                                        data-job-title="<?= htmlspecialchars($submission['job_title'], ENT_QUOTES) ?>"
                                        data-cover="<?= htmlspecialchars($submission['message'], ENT_QUOTES) ?>"
                                        <?php if ($is_accepted) echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>
                                    >View Details</button>
                                    <form method="POST" style="display:inline; margin:0; padding:0;">
                                        <input type="hidden" name="accept_submission_id" value="<?= $submission['id'] ?>">
                                        <button type="submit" class="action-btn accept-btn" <?php if ($is_accepted) echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>Accept</button>
                                    </form>
                                    <form method="POST" style="display:inline; margin:0; padding:0;">
                                        <input type="hidden" name="reject_submission_id" value="<?= $submission['id'] ?>">
                                        <button type="submit" class="action-btn reject-btn" <?php if ($is_accepted) echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>Reject</button>
                                    </form>
                                    <form method="POST" style="display:inline; margin:0; padding:0;">
                                        <input type="hidden" name="progress_submission_id" value="<?= $submission['id'] ?>">
                                        <button type="submit" class="action-btn progress-btn" <?php if ($is_accepted || $is_in_progress) echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>In Progress</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="company-empty-state">
                        <svg class="company-empty-illustration" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="32" fill="#e6e6ff"/><rect x="18" y="28" width="28" height="16" rx="4" fill="#7c3aed"/><rect x="24" y="34" width="16" height="4" rx="2" fill="#fff"/></svg>
                        No submissions found for your company.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<script src="main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.view-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      let files = [];
      try {
        files = JSON.parse(btn.getAttribute('data-files'));
      } catch (err) {}
      const sub = {
        name: btn.getAttribute('data-name'),
        email: btn.getAttribute('data-email'),
        phone: btn.getAttribute('data-phone'),
        date: btn.getAttribute('data-date'),
        files: files,
        job_title: btn.getAttribute('data-job-title'),
        company_name: btn.getAttribute('data-company-name'),
        cover: btn.getAttribute('data-cover'),
      };
      openSubmissionDetailsModal(sub);
    });
  });
});
</script>
</body>
</html>
