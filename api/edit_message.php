<?php
require_once '../includes/config.php';

ensureSupportChatSchema($db);

header('Content-Type: application/json; charset=utf-8');
$session->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
$message = trim($_POST['message'] ?? '');

if ($message_id <= 0) {
    echo json_encode(['error' => 'Message ID is required']);
    exit;
}

$user_id = $session->getUserId();
$user_role = $session->getUserRole();

// Check if user owns the message and ensure message is not deleted
$checkSql = "SELECT sender_id, sender_role, message, file_path, is_deleted FROM support_messages WHERE message_id = ?";
$checkStmt = $db->prepare($checkSql);
$msg = $db->getSingle($checkStmt, [$message_id]);

if (!$msg || (int)$msg['sender_id'] !== (int)$user_id || $msg['sender_role'] !== $user_role) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if (!empty($msg['is_deleted'])) {
    echo json_encode(['error' => 'Cannot edit a deleted message']);
    exit;
}

if ($message === '' && empty($msg['file_path'])) {
    echo json_encode(['error' => 'Message text cannot be empty when no attachment exists']);
    exit;
}

$updateSql = "UPDATE support_messages SET message = ?, updated_at = NOW() WHERE message_id = ?";
$updateStmt = $db->prepare($updateSql);
$db->execute($updateStmt, [$message, $message_id]);

echo json_encode(['success' => true]);
exit;
?>