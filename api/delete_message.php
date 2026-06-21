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

if ($message_id <= 0) {
    echo json_encode(['error' => 'Message ID is required']);
    exit;
}

$user_id = $session->getUserId();
$user_role = $session->getUserRole();

// Check if user owns the message
$checkSql = "SELECT sender_id, sender_role, is_deleted FROM support_messages WHERE message_id = ?";
$checkStmt = $db->prepare($checkSql);
$msg = $db->getSingle($checkStmt, [$message_id]);

if (!$msg || (int)$msg['sender_id'] !== (int)$user_id || $msg['sender_role'] !== $user_role) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if (!empty($msg['is_deleted'])) {
    echo json_encode(['success' => true]);
    exit;
}

$softDeleteSql = "UPDATE support_messages SET is_deleted = 1, updated_at = NOW() WHERE message_id = ?";
$softDeleteStmt = $db->prepare($softDeleteSql);
$db->execute($softDeleteStmt, [$message_id]);

echo json_encode(['success' => true]);
exit;
?>