<?php
require_once '../includes/config.php';

ensureSupportChatSchema($db);

header('Content-Type: application/json; charset=utf-8');
$session->requireLogin();

$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
$after_id = isset($_GET['after_id']) ? intval($_GET['after_id']) : 0;

if ($ticket_id <= 0) {
    echo json_encode(['error' => 'Invalid ticket ID']);
    exit;
}

$user_id = $session->getUserId();
$user_role = $session->getUserRole();

$ticketSql = "SELECT ticket_id, tenant_id, owner_id FROM support_tickets WHERE ticket_id = ?";
$ticketStmt = $db->prepare($ticketSql);
$ticket = $db->getSingle($ticketStmt, [$ticket_id]);

if (!$ticket) {
    echo json_encode(['error' => 'Support ticket not found']);
    exit;
}

if ($user_role === 'tenant' && (int)$ticket['tenant_id'] !== (int)$user_id) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if ($user_role === 'owner' && (int)$ticket['owner_id'] !== (int)$user_id) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if (!in_array($user_role, ['tenant', 'owner', 'employee', 'admin'], true)) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$query = "SELECT m.message_id, m.sender_role, m.sender_id, m.message, m.file_path, m.file_type, m.reply_to, m.is_deleted, m.updated_at, m.created_at, 
                 COALESCE(u.full_name, CASE WHEN m.sender_role = 'employee' THEN 'Employee' WHEN m.sender_role = 'tenant' THEN 'Tenant' ELSE 'System' END) AS full_name 
          FROM support_messages m 
          LEFT JOIN users u ON m.sender_id = u.user_id 
          WHERE m.ticket_id = ?";
$params = [$ticket_id];

if ($after_id > 0) {
    $query .= " AND m.message_id > ?";
    $params[] = $after_id;
}

$query .= " ORDER BY m.created_at ASC, m.message_id ASC";
$stmt = $db->prepare($query);
$messages = $db->getMultiple($stmt, $params);

echo json_encode(['ticket_id' => $ticket_id, 'messages' => $messages]);
exit;
