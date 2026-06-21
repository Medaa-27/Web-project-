<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $session->getUserId();
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;

$sql = "SELECT notification_id, title, message, type, is_read, link, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?";
$stmt = $db->prepare($sql);
$items = $db->getMultiple($stmt, [(int)$user_id, $limit]);

echo json_encode(['success' => true, 'notifications' => $items]);
