<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$news_id = (int)$_GET['id'];
$employee_id = $session->getUserId();

$sql = "SELECT * FROM system_news WHERE news_id = ? AND created_by = ?";
$stmt = $db->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$news = $db->getSingle($stmt, [$news_id, $employee_id]);

if ($news) {
    // Convert boolean values to proper format for form
    $news['featured'] = (bool)$news['featured'];
    $news['allow_comments'] = (bool)$news['allow_comments'];
    echo json_encode(['success' => true, 'data' => $news]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'News not found']);
}
?>
