<?php
require_once 'require_login.php';
require_once 'dbconn.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['job_ids'], $data['category_id']) || !is_array($data['job_ids']) || !$data['category_id']) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}
$job_ids = array_map('intval', $data['job_ids']);
$category_id = intval($data['category_id']);

// Check if category exists
$cat_check = $connection->prepare('SELECT id FROM categories WHERE id = ?');
$cat_check->bind_param('i', $category_id);
$cat_check->execute();
$cat_check->store_result();
if ($cat_check->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Category not found.']);
    exit;
}
$cat_check->close();

$assigned = [];
$already_assigned = [];

// Check and insert for each job
$check_stmt = $connection->prepare('SELECT 1 FROM job_categories WHERE job_id = ? AND category_id = ?');
$insert_stmt = $connection->prepare('INSERT INTO job_categories (job_id, category_id) VALUES (?, ?)');
foreach ($job_ids as $job_id) {
    $check_stmt->bind_param('ii', $job_id, $category_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $already_assigned[] = $job_id;
    } else {
        $insert_stmt->bind_param('ii', $job_id, $category_id);
        $insert_stmt->execute();
        $assigned[] = $job_id;
    }
}
$check_stmt->close();
$insert_stmt->close();

$response = ['success' => true, 'assigned' => $assigned, 'already_assigned' => $already_assigned];
echo json_encode($response); 