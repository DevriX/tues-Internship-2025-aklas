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

$user_company = $user['company_name'] ?? '';
$submissions = [];

if ($user_company) {
    $stmt = $connection->prepare("
        SELECT a.id, u.first_name, u.last_name, u.email, u.phone_number, a.message, a.cv_file_path, a.applied_at, a.company_name, a.job_title, a.status, c.company_image, u.profile_image
        FROM apply_submissions a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN users c ON a.company_name = c.company_name
        WHERE a.company_name = ?
        ORDER BY a.applied_at DESC
    ");
    $stmt->bind_param("s", $user_company);
    $stmt->execute();
    $stmt->bind_result($id, $fname, $lname, $email, $phone, $message, $cv, $applied_at, $company_name, $job_title, $status, $company_image, $profile_image);
    while ($stmt->fetch()) {
        $files = json_decode($cv, true) ?: [];
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
    }
    $stmt->close();
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
    <style>
        .company-submissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        .company-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 24px rgba(80,0,120,0.10);
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s;
            border: none;
            min-height: 180px;
        }
        .company-card:hover {
            transform: translateY(-6px) scale(1.025);
            box-shadow: 0 8px 32px rgba(80,0,120,0.16);
        }
        .company-card-accent {
            height: 6px;
            width: 100%;
            background: linear-gradient(90deg, #7c3aed 0%, #4b0082 100%);
            margin-bottom: 1.2rem;
        }
        .company-card-content {
            flex: 1 1 auto;
            padding: 1.2rem 1.5rem 1.5rem 1.5rem;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 1.2rem;
            position: relative;
        }
        .company-card-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.7rem;
            margin-left: auto;
            min-width: 160px;
            position: relative;
            z-index: 2;
        }
        .company-card-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e0d7f7 0%, #f0e6ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #4b0082;
            box-shadow: 0 2px 8px rgba(80,0,120,0.07);
            flex-shrink: 0;
            text-transform: uppercase;
        }
        .company-card-info {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .company-card-name {
            font-size: 1.15rem;
            font-weight: 600;
            color: #2d1457;
            margin-bottom: 0.1rem;
        }
        .company-card-job {
            font-size: 1rem;
            color: #7c3aed;
            font-weight: 500;
        }
        .company-card-date {
            font-size: 0.95rem;
            color: #888;
            margin-top: 0.2rem;
        }
        .company-card .view-btn,
        .company-card .action-btn {
            border: none;
            border-radius: 999px;
            font-weight: 500;
            font-size: 0.95rem;
            box-shadow: 0 2px 10px rgba(80,0,120,0.09);
            cursor: pointer;
            transition: transform 0.18s, box-shadow 0.18s, background 0.2s;
            min-width: 100px;
            white-space: nowrap;
            padding: 0.45rem 1.1rem;
            margin: 0;
            outline: none;
        }
        .company-card .view-btn {
            background: linear-gradient(90deg, #7c3aed 0%, #4b0082 100%);
            color: #fff;
            margin-bottom: 0.1rem;
        }
        .company-card .view-btn:hover {
            background: linear-gradient(90deg, #4b0082 0%, #7c3aed 100%);
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 18px rgba(80,0,120,0.16);
        }
        .company-card .accept-btn {
            background: linear-gradient(90deg, #34d399 0%, #059669 100%);
            color: #fff;
        }
        .company-card .accept-btn:hover {
            background: linear-gradient(90deg, #059669 0%, #34d399 100%);
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 18px rgba(52,211,153,0.18);
        }
        .company-card .reject-btn {
            background: linear-gradient(90deg, #f87171 0%, #b91c1c 100%);
            color: #fff;
        }
        .company-card .reject-btn:hover {
            background: linear-gradient(90deg, #b91c1c 0%, #f87171 100%);
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 18px rgba(248,113,113,0.18);
        }
        .company-card .progress-btn {
            background: linear-gradient(90deg, #fbbf24 0%, #f59e42 100%);
            color: #fff;
        }
        .company-card .progress-btn:hover {
            background: linear-gradient(90deg, #f59e42 0%, #fbbf24 100%);
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 18px rgba(251,191,36,0.18);
        }
        .company-empty-state {
            text-align: center;
            color: #4b0082;
            margin: 3rem 0 2rem 0;
            font-size: 1.2rem;
            opacity: 0.85;
        }
        .company-empty-illustration {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.2rem auto;
            display: block;
            opacity: 0.7;
        }
        @media (max-width: 600px) {
            .company-card-content {
                flex-direction: column;
                align-items: stretch;
            }
            .company-card-actions {
                align-items: stretch;
                min-width: 0;
                margin-left: 0;
                margin-top: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="site-wrapper">
    <main class="site-main">
        <section class="section-fullwidth">
            <div class="row">
                <h3 style="text-align: center; font-weight: 700; font-size: 2.1rem; color: #4b0082; margin-bottom: 2.2rem;">My Company Submissions</h3>
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
