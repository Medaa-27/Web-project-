<?php
require_once '../includes/config.php';

ensureSupportChatSchema($db);

header('Content-Type: application/json; charset=utf-8');
$session->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$message = trim($_POST['message'] ?? '');
$reply_to = isset($_POST['reply_to']) ? intval($_POST['reply_to']) : null;

// Handle file upload
$file_path = null;
$file_type = null;
if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/zip',
            'text/plain'
        ];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types, true)) {
            echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP, PDF, DOCX, TXT, and ZIP are allowed.']);
            exit;
        }

        if ($file['size'] > $max_size) {
            echo json_encode(['error' => 'File size exceeds 5MB limit.']);
            exit;
        }

        $upload_dir = '../assets/uploads/chat/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = time() . '_' . $session->getUserId() . '.' . $ext;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $file_path = 'assets/uploads/chat/' . $filename;
            $file_type = $file['type'];
        } else {
            echo json_encode(['error' => 'Failed to upload file.']);
            exit;
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server limit.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form limit.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
        ];
        $errorMessage = $uploadErrors[$file['error']] ?? 'File upload failed.';
        echo json_encode(['error' => $errorMessage]);
        exit;
    }
}

if ($ticket_id <= 0 || ($message === '' && $file_path === null)) {
    echo json_encode(['error' => 'Ticket ID and either message or file are required']);
    exit;
}

$user_id = $session->getUserId();
$user_role = $session->getUserRole();

$ticketSql = "SELECT ticket_id, tenant_id, owner_id, assigned_employee_id, status FROM support_tickets WHERE ticket_id = ?";
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

if ($ticket['status'] === 'closed') {
    echo json_encode(['error' => 'This support ticket is closed']);
    exit;
}

if (!empty($reply_to)) {
    $replyCheckSql = "SELECT message_id FROM support_messages WHERE message_id = ? AND ticket_id = ?";
    $replyCheckStmt = $db->prepare($replyCheckSql);
    $replyRow = $db->getSingle($replyCheckStmt, [$reply_to, $ticket_id]);

    if (!$replyRow) {
        echo json_encode(['error' => 'Invalid reply target']);
        exit;
    }
}

$db->beginTransaction();

try {
    $insertSql = "INSERT INTO support_messages (ticket_id, sender_id, sender_role, message, file_path, file_type, reply_to, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $insertStmt = $db->prepare($insertSql);
    $db->execute($insertStmt, [$ticket_id, $user_id, $user_role, $message, $file_path, $file_type, $reply_to]);
    $message_id = $db->lastInsertId();

    $updateSql = "UPDATE support_tickets SET updated_at = NOW() WHERE ticket_id = ?";
    $updateStmt = $db->prepare($updateSql);
    $db->execute($updateStmt, [$ticket_id]);

    if ($user_role === 'employee' && empty($ticket['assigned_employee_id'])) {
        $assignSql = "UPDATE support_tickets SET assigned_employee_id = ? WHERE ticket_id = ?";
        $assignStmt = $db->prepare($assignSql);
        $db->execute($assignStmt, [$user_id, $ticket_id]);
    }

    $db->commit();

    $selectSql = "SELECT m.message_id, m.sender_role, m.sender_id, m.message, m.file_path, m.file_type, m.reply_to, m.is_deleted, m.updated_at, m.created_at, 
                         COALESCE(u.full_name, CASE WHEN m.sender_role = 'employee' THEN 'Employee' WHEN m.sender_role = 'tenant' THEN 'Tenant' ELSE 'System' END) AS full_name 
                  FROM support_messages m 
                  LEFT JOIN users u ON m.sender_id = u.user_id 
                  WHERE m.message_id = ?";
    $selectStmt = $db->prepare($selectSql);
    $newMessage = $db->getSingle($selectStmt, [$message_id]);

    echo json_encode(['success' => true, 'message' => $newMessage]);
    exit;
} catch (Exception $e) {
    $db->rollback();
    error_log('Support send_message error: ' . $e->getMessage());
    echo json_encode(['error' => 'Unable to save message. Please try again.']);
    exit;
}
