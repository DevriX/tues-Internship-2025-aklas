<?php
function render_jobs_listing($connection, $items_per_page, $offset, $current_page) {
    $jobs_result = mysqli_query(
        $connection,
        "SELECT * FROM jobs ORDER BY approved ASC, created_at DESC LIMIT $items_per_page OFFSET $offset"
    );
    while ($job = mysqli_fetch_assoc($jobs_result)):
        if (empty($job['title']) || empty($job['location']) || empty($job['salary'])) {
            continue;
        }
        // Fetch company name for this job (if needed)
        $company = '';
        $user_id = intval($job['user_id']);
        $company_query = mysqli_query($connection, "SELECT company_name FROM users WHERE id = $user_id LIMIT 1");
        if ($company_row = mysqli_fetch_assoc($company_query)) {
            $company = $company_row['company_name'];
        }
?>
    <li class="job-card" 
        data-title="<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>"
        data-company="<?= htmlspecialchars($company, ENT_QUOTES) ?>"
        data-location="<?= htmlspecialchars($job['location'], ENT_QUOTES) ?>"
        data-salary="<?= htmlspecialchars($job['salary'], ENT_QUOTES) ?>"
        data-description="<?= htmlspecialchars($job['description'], ENT_QUOTES) ?>"
        data-created_at="<?= htmlspecialchars($job['created_at'], ENT_QUOTES) ?>"
        data-approved="<?= $job['approved'] ? '1' : '0' ?>"
        style="cursor:pointer;"
    >
        <input type="checkbox" class="bulk-job-checkbox" value="<?= $job['id'] ?>" style="display:none; margin-right:10px;" />
        <div class="job-primary">
            <h2 class="job-title"><?= htmlspecialchars($job['title']) ?></h2>
            <div class="job-details">
                <a href="#" class="job-location-link" data-location="<?= htmlspecialchars($job['location']) ?>" style="margin-right: 30px; text-decoration: underline; color: #007bff; cursor: pointer;">
                    <?= htmlspecialchars($job['location']) ?>
                </a>
                <span class="job-salary">Salary: <?= htmlspecialchars($job['salary']) ?></span>
            </div>
        </div>
        <div class="job-secondary">
            <?php if (!$job['approved']): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="approve_job_id" value="<?= $job['id'] ?>">
                    <button type="submit" class="btn-approve">Approve</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="reject_job_id" value="<?= $job['id'] ?>">
                    <button type="submit" class="btn-reject">Reject</button>
                </form>
            <?php else: ?>
                <span class="approved-label">Approved</span>
            <?php endif; ?>
        </div>
    </li>
<?php endwhile;
} 