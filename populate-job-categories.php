<?php
require 'dbconn.php';

// Build a map of category name => id
$cat_result = mysqli_query($connection, "SELECT id, name FROM categories");
$cat_map = [];
while ($row = mysqli_fetch_assoc($cat_result)) {
    $cat_map[trim($row['name'])] = $row['id'];
}

// For each job, insert into job_categories for each category
$job_result = mysqli_query($connection, "SELECT id, category FROM jobs WHERE category IS NOT NULL AND category != ''");
while ($job = mysqli_fetch_assoc($job_result)) {
    $job_id = $job['id'];
    $categories = explode(',', $job['category']);
    foreach ($categories as $cat_name) {
        $cat_name = trim($cat_name);
        if ($cat_name && isset($cat_map[$cat_name])) {
            $cat_id = $cat_map[$cat_name];
            // Insert, ignore duplicates
            mysqli_query($connection, "INSERT IGNORE INTO job_categories (job_id, category_id) VALUES ($job_id, $cat_id)");
        }
    }
}
echo "Done populating job_categories!";
?>