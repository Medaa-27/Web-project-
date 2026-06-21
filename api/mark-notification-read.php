<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $session->getUserId();
$notification_id = $_POST['notification_id'] ?? null;

if (!is_numeric($notification_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification']);
    exit;
}

$sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
$stmt = $db->prepare($sql);
$ok = $db->execute($stmt, [(int)$notification_id, (int)$user_id]);

echo json_encode(['success' => (bool)$ok]);
