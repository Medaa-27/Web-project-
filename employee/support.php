<?php
require_once '../includes/config.php';

$session->requireRole('employee');
$title = "Support Tickets";

// Suppress warnings for cleaner display
error_reporting(E_ERROR | E_PARSE);

$employee_id = $session->getUserId();

function ensureSupportTables($db) {
    try {
        $check = $db->prepare("SHOW TABLES LIKE 'support_tickets'");
        $db->execute($check);
        if ($check->rowCount() === 0) {
            $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
                ticket_id INT AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT NOT NULL,
                owner_id INT NULL,
                assigned_employee_id INT NULL,
                target_role ENUM('owner', 'employee', 'admin', 'all') NOT NULL DEFAULT 'employee',
                subject VARCHAR(255) NOT NULL,
                category VARCHAR(50) NOT NULL DEFAULT 'general',
                priority VARCHAR(20) NOT NULL DEFAULT 'normal',
                status VARCHAR(20) NOT NULL DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at NULL DEFAULT NULL,
                INDEX idx_tenant_status (tenant_id, status),
                INDEX idx_owner_status (owner_id, status),
                INDEX idx_assigned_status (assigned_employee_id, status),
                INDEX idx_target_role (target_role),
                CONSTRAINT fk_support_tickets_tenant FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_support_tickets_owner FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE SET NULL,
                CONSTRAINT fk_support_tickets_employee FOREIGN KEY (assigned_employee_id) REFERENCES users(user_id) ON DELETE SET NULL
            )";
            $db->execute($db->prepare($sql));
        } else {
            $hasOwnerId = $db->prepare("SHOW COLUMNS FROM support_tickets LIKE 'owner_id'");
            $db->execute($hasOwnerId);
            if ($hasOwnerId->rowCount() === 0) {
                $alter = "ALTER TABLE support_tickets ADD COLUMN owner_id INT NULL AFTER tenant_id,
                          ADD INDEX idx_owner_status (owner_id, status),
                          ADD CONSTRAINT fk_support_tickets_owner FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE SET NULL";
                $db->execute($db->prepare($alter));
            }
            $hasTargetRole = $db->prepare("SHOW COLUMNS FROM support_tickets LIKE 'target_role'");
            $db->execute($hasTargetRole);
            if ($hasTargetRole->rowCount() === 0) {
                $alter = "ALTER TABLE support_tickets ADD COLUMN target_role ENUM('owner', 'employee', 'admin', 'all') NOT NULL DEFAULT 'employee' AFTER assigned_employee_id,
                          ADD INDEX idx_target_role (target_role)";
                $db->execute($db->prepare($alter));
            } else {
                $modify = "ALTER TABLE support_tickets MODIFY COLUMN target_role ENUM('owner', 'employee', 'admin', 'all') NOT NULL DEFAULT 'employee'";
                $db->execute($db->prepare($modify));
            }
        }

        $check = $db->prepare("SHOW TABLES LIKE 'support_messages'");
        $db->execute($check);
        if ($check->rowCount() === 0) {
            $sql = "CREATE TABLE IF NOT EXISTS support_messages (
                message_id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                sender_role VARCHAR(20) NOT NULL,
                sender_id INT NOT NULL,
                message TEXT,
                file_path VARCHAR(255) NULL,
                file_type VARCHAR(50) NULL,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                reply_to INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL,
                INDEX idx_ticket_created (ticket_id, created_at),
                CONSTRAINT fk_support_messages_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(ticket_id) ON DELETE CASCADE,
                CONSTRAINT fk_support_messages_sender FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
            )";
            $db->execute($db->prepare($sql));
        }
    } catch (Exception $e) {
        error_log("Support table creation error: " . $e->getMessage());
        // Continue execution - tables might already exist
    }
}

ensureSupportTables($db);

$active_ticket_id = isset($_GET['ticket_id']) && is_numeric($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null;
$status_filter = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'open';
if (!in_array($status_filter, ['open', 'closed', 'all'], true)) {
    $status_filter = 'open';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign_ticket') {
        $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        if ($ticket_id > 0) {
            $sql = "UPDATE support_tickets SET assigned_employee_id = ?, updated_at = NOW() WHERE ticket_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$employee_id, $ticket_id]);
            header('Location: support.php?ticket_id=' . $ticket_id);
            exit;
        }
    }

    if ($action === 'close_ticket') {
        $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        if ($ticket_id > 0) {
            $sql = "UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE ticket_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$ticket_id]);
            header('Location: support.php?ticket_id=' . $ticket_id);
            exit;
        }
    }

    if ($action === 'send_message') {
        $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        $message = trim($_POST['message'] ?? '');

        if ($ticket_id <= 0 || $message === '') {
            $error = 'Message cannot be empty.';
        } else {
            $sql = "SELECT * FROM support_tickets WHERE ticket_id = ?";
            $stmt = $db->prepare($sql);
            $ticket = $db->getSingle($stmt, [$ticket_id]);

            if (!$ticket) {
                $error = 'Ticket not found.';
            } elseif (($ticket['status'] ?? '') === 'closed') {
                $error = 'This ticket is closed.';
            } else {
                if (empty($ticket['assigned_employee_id'])) {
                    $sql = "UPDATE support_tickets SET assigned_employee_id = ?, updated_at = NOW() WHERE ticket_id = ?";
                    $db->execute($db->prepare($sql), [$employee_id, $ticket_id]);
                } else {
                    $sql = "UPDATE support_tickets SET updated_at = NOW() WHERE ticket_id = ?";
                    $db->execute($db->prepare($sql), [$ticket_id]);
                }

                $sql = "INSERT INTO support_messages (ticket_id, sender_role, sender_id, message, created_at)
                        VALUES (?, 'employee', ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                if ($db->execute($stmt, [$ticket_id, $employee_id, $message])) {
                    $notif = "INSERT INTO notifications (user_id, title, message, type, created_at)
                              VALUES (?, 'Support Reply', 'An employee replied to your support ticket.', 'info', NOW())";
                    $db->execute($db->prepare($notif), [(int)$ticket['tenant_id']]);

                    header('Location: support.php?ticket_id=' . $ticket_id);
                    exit;
                }

                $error = 'Failed to send message.';
            }
        }
    }
}

$sql = "SELECT t.*,
               u.full_name AS tenant_name,
               u.email AS tenant_email,
               e.full_name AS assigned_employee_name,
               (SELECT m.created_at FROM support_messages m WHERE m.ticket_id = t.ticket_id ORDER BY m.created_at DESC LIMIT 1) AS last_message_at,
               (SELECT m.message FROM support_messages m WHERE m.ticket_id = t.ticket_id ORDER BY m.created_at DESC LIMIT 1) AS last_message
        FROM support_tickets t
        JOIN users u ON t.tenant_id = u.user_id
        LEFT JOIN users e ON t.assigned_employee_id = e.user_id";

$params = [];
$wheres = [];

if ($status_filter !== 'all') {
    $wheres[] = "t.status = ?";
    $params[] = $status_filter;
}

$wheres[] = "t.target_role IN ('employee', 'admin', 'all')";

if (!empty($_GET['mine']) && $_GET['mine'] === '1') {
    $wheres[] = "t.assigned_employee_id = ?";
    $params[] = $employee_id;
}

if (!empty($wheres)) {
    $sql .= " WHERE " . implode(' AND ', $wheres);
}

$sql .= " ORDER BY COALESCE(t.updated_at, t.created_at) DESC";

$tickets = [];
try {
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $tickets = $db->getMultiple($stmt, $params);
    }
} catch (Exception $e) {
    error_log("Support tickets query error: " . $e->getMessage());
    $tickets = [];
}

$active_ticket = null;
$messages = [];

if ($active_ticket_id) {
    try {
        $sql = "SELECT t.*, u.full_name AS tenant_name, u.email AS tenant_email, e.full_name AS assigned_employee_name
                FROM support_tickets t
                JOIN users u ON t.tenant_id = u.user_id
                LEFT JOIN users e ON t.assigned_employee_id = e.user_id
                WHERE t.ticket_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $active_ticket = $db->getSingle($stmt, [$active_ticket_id]);
        }

        if ($active_ticket) {
            $sql = "SELECT m.*, u.full_name
                    FROM support_messages m
                    LEFT JOIN users u ON m.sender_id = u.user_id
                    WHERE m.ticket_id = ?
                    ORDER BY m.created_at ASC";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $messages = $db->getMultiple($stmt, [$active_ticket_id]);
            }
        }
    } catch (Exception $e) {
        error_log("Active ticket query error: " . $e->getMessage());
        $active_ticket = null;
        $messages = [];
    }
}

include '../includes/header.php';
?>

<style>
.chat-image {
    max-width: 200px;
    border-radius: 10px;
    cursor: pointer;
    transition: transform 0.2s;
    display: block;
    margin-top: 0.5rem;
}
.chat-image:hover {
    transform: scale(1.02);
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Support Tickets</h1>
                    <p class="text-muted mb-0">Respond to tenants and provide assistance.</p>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="support.php?status=<?php echo htmlspecialchars($status_filter); ?>&mine=1">
                        Assigned to Me
                    </a>
                    <a class="btn btn-outline-primary" href="support.php?status=open">Open</a>
                    <a class="btn btn-outline-secondary" href="support.php?status=closed">Closed</a>
                    <a class="btn btn-outline-dark" href="support.php?status=all">All</a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Inbox</h6>
                            <span class="badge bg-secondary"><?php echo count($tickets); ?></span>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 70vh; overflow:auto;">
                            <?php if (empty($tickets)): ?>
                                <div class="p-4 text-center text-muted">No tickets found.</div>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): ?>
                                    <?php
                                        $is_active = $active_ticket_id && (int)$t['ticket_id'] === (int)$active_ticket_id;
                                        $status = $t['status'] ?? 'open';
                                        $badge = $status === 'closed' ? 'secondary' : 'success';
                                        $priority = $t['priority'] ?? 'normal';
                                        $priority_badge = $priority === 'urgent' ? 'danger' : ($priority === 'high' ? 'warning' : 'info');
                                    ?>
                                    <a class="list-group-item list-group-item-action <?php echo $is_active ? 'active' : ''; ?>" href="support.php?ticket_id=<?php echo (int)$t['ticket_id']; ?>">
                                        <div class="d-flex justify-content-between">
                                            <div class="fw-semibold text-truncate" style="max-width: 220px;">
                                                <?php echo htmlspecialchars($t['subject']); ?>
                                            </div>
                                            <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                                        </div>
                                        <div class="small text-muted text-truncate"><?php echo htmlspecialchars($t['tenant_name']); ?></div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="badge bg-<?php echo $priority_badge; ?>"><?php echo htmlspecialchars($priority); ?></span>
                                            <span class="small text-muted">
                                                <?php echo !empty($t['last_message_at']) ? date('M d, H:i', strtotime($t['last_message_at'])) : date('M d, H:i', strtotime($t['created_at'])); ?>
                                            </span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <?php if ($active_ticket): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($active_ticket['subject']); ?></h6>
                                        <div class="small text-muted">
                                            Ticket #<?php echo (int)$active_ticket['ticket_id']; ?>
                                            - Tenant: <?php echo htmlspecialchars($active_ticket['tenant_name']); ?> (<?php echo htmlspecialchars($active_ticket['tenant_email']); ?>)
                                        </div>
                                        <div class="small text-muted">
                                            <?php if (!empty($active_ticket['assigned_employee_name'])): ?>
                                                Assigned to <?php echo htmlspecialchars($active_ticket['assigned_employee_name']); ?>
                                            <?php else: ?>
                                                Not assigned
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if (empty($active_ticket['assigned_employee_id'])): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="assign_ticket">
                                                <input type="hidden" name="ticket_id" value="<?php echo (int)$active_ticket['ticket_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-user-check me-1"></i>Assign to Me
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (($active_ticket['status'] ?? '') !== 'closed'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="close_ticket">
                                                <input type="hidden" name="ticket_id" value="<?php echo (int)$active_ticket['ticket_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Close this ticket?');">
                                                    <i class="fas fa-times-circle me-1"></i>Close
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Closed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <h6 class="mb-0">Conversation</h6>
                            <?php endif; ?>
                        </div>

                        <div class="card-body" style="height: 55vh; overflow:auto;" id="chatBox">
                            <?php if (!$active_ticket): ?>
                                <div class="text-center text-muted py-5">
                                    Select a ticket from the inbox.
                                </div>
                            <?php else: ?>
                                <?php if (empty($messages)): ?>
                                    <div class="text-center text-muted py-5">No messages yet.</div>
                                <?php else: ?>
                                    <?php foreach ($messages as $m): ?>
                                        <?php
                                            $mine = ($m['sender_role'] === 'employee' && (int)$m['sender_id'] === (int)$employee_id);
                                            $replyPreview = null;
                                            if (!empty($m['reply_to'])) {
                                                foreach ($messages as $pm) {
                                                    if ((int)$pm['message_id'] === (int)$m['reply_to']) {
                                                        $replyPreview = $pm;
                                                        break;
                                                    }
                                                }
                                            }
                                        ?>
                                        <div data-message-id="<?php echo (int)$m['message_id']; ?>" class="mb-3 d-flex <?php echo $mine ? 'justify-content-end' : 'justify-content-start'; ?>">
                                            <div class="p-3 rounded" style="max-width: 80%; background: <?php echo $mine ? '#e7f1ff' : '#f1f3f5'; ?>;">
                                                <div class="small fw-semibold mb-1">
                                                    <?php echo htmlspecialchars($m['full_name'] ?? ($m['sender_role'] === 'tenant' ? 'Tenant' : 'You')); ?>
                                                    <?php if (!empty($m['updated_at']) && strtotime($m['updated_at']) > strtotime($m['created_at'])): ?>
                                                        <span class="text-muted small">(edited)</span>
                                                    <?php endif; ?>
                                                    <span class="text-muted fw-normal">· <?php echo date('M d, H:i', strtotime($m['created_at'])); ?></span>
                                                </div>
                                                <?php if (!empty($replyPreview)): ?>
                                                    <div class="border-start ps-2 small text-muted mb-2">
                                                        Replying to: <?php echo htmlspecialchars(mb_substr($replyPreview['message'] ?: ($replyPreview['file_path'] ? 'Attachment' : ''), 0, 80)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($m['is_deleted'])): ?>
                                                    <div class="fst-italic text-muted">This message was deleted.</div>
                                                <?php else: ?>
                                                    <div><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                                                    <?php if (!empty($m['file_path'])): ?>
                                                        <div class="mt-2">
                                                            <?php $ext = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION)); ?>
                                                            <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank">
                                                                    <img src="<?php echo rtrim(SITE_URL, '/'); ?>/<?php echo htmlspecialchars($m['file_path']); ?>" class="chat-image">
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-file"></i> Download File
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <div class="dropdown mt-1">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="replyToMessage(<?php echo $m['message_id']; ?>)"><i class="fas fa-reply"></i> Reply</a></li>
                                                        <?php if ($mine && empty($m['is_deleted'])): ?>
                                                            <li><a class="dropdown-item" href="#" onclick="editMessage(<?php echo $m['message_id']; ?>)"><i class="fas fa-edit"></i> Edit</a></li>
                                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteMessage(<?php echo $m['message_id']; ?>)"><i class="fas fa-trash"></i> Delete</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?> 
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer">
                            <?php if ($active_ticket && ($active_ticket['status'] ?? '') !== 'closed'): ?>
                                <form id="supportMessageForm" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                    <input type="hidden" name="action" value="send_message">
                                    <input type="hidden" name="ticket_id" value="<?php echo (int)$active_ticket['ticket_id']; ?>">
                                    <input type="hidden" name="reply_to" id="replyToField" value="">
                                    <div id="replyPreview" class="border rounded px-3 py-2 mb-2 d-none bg-light text-muted"></div>
                                    <div class="flex-grow-1">
                                        <textarea name="message" class="form-control" rows="2" placeholder="Type your reply..."></textarea>
                                        <input type="file" name="file" class="form-control mt-1" accept=".jpg,.jpeg,.png,.pdf,.docx">
                                    </div>
                                    <button class="btn btn-primary align-self-end" type="submit">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                                <div id="supportMessageStatus" class="small text-muted mt-2 d-none"></div>
                            <?php elseif ($active_ticket): ?>
                                <div class="text-muted">This ticket is closed.</div>
                            <?php else: ?>
                                <div class="text-muted">Choose a ticket to start messaging.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    window.supportChatConfig = {
        ticketId: <?php echo (int)$active_ticket_id; ?>,
        userId: <?php echo (int)$employee_id; ?>,
        userRole: <?php echo json_encode($session->getUserRole()); ?>,
        getMessagesUrl: '../api/get_messages.php',
        sendMessageUrl: '../api/send_message.php',
        editMessageUrl: '../api/edit_message.php',
        deleteMessageUrl: '../api/delete_message.php',
        baseUrl: <?php echo json_encode(SITE_URL); ?>,
        initialMessages: <?php echo json_encode($messages ?? []); ?>
    };
</script>
<script src="../assets/js/support-chat.js"></script>
<?php include '../includes/footer.php'; ?>
