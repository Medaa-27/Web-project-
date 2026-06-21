<?php
require_once '../includes/config.php';

$session->requireRole('tenant');
$title = "Support Center";

$user_id = $session->getUserId();

function ensureSupportTables($db) {
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
            updated_at TIMESTAMP NULL DEFAULT NULL,
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
}

ensureSupportTables($db);

$active_ticket_id = isset($_GET['ticket_id']) && is_numeric($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_ticket') {
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $priority = trim($_POST['priority'] ?? 'normal');
        $target_role = trim($_POST['target_role'] ?? 'employee');
        $message = trim($_POST['message'] ?? '');

        if ($subject === '' || $message === '') {
            $error = 'Please enter a subject and a message.';
        } elseif (!in_array($target_role, ['owner', 'employee', 'all'], true)) {
            $error = 'Invalid target recipient.';
        } else {
            $owner_id = null;
            if ($target_role === 'owner' || $target_role === 'all') {
                $ownerQuery = "SELECT p.owner_id FROM rental_agreements ra JOIN properties p ON ra.property_id = p.property_id WHERE ra.tenant_id = ? AND ra.status = 'active' ORDER BY ra.updated_at DESC LIMIT 1";
                $ownerStmt = $db->prepare($ownerQuery);
                $ownerResult = $db->getSingle($ownerStmt, [$user_id]);
                if ($ownerResult && !empty($ownerResult['owner_id'])) {
                    $owner_id = $ownerResult['owner_id'];
                } else {
                    $error = 'No active rental agreement found with an owner.';
                }
            }

            if (!$error) {
                $sql = "INSERT INTO support_tickets (tenant_id, owner_id, target_role, subject, category, priority, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())";
                $stmt = $db->prepare($sql);

                if ($db->execute($stmt, [$user_id, $owner_id, $target_role, $subject, $category, $priority])) {
                    $ticket_id = (int)$db->lastInsertId();

                    $sql = "INSERT INTO support_messages (ticket_id, sender_role, sender_id, message, created_at)
                            VALUES (?, 'tenant', ?, ?, NOW())";
                    $stmt = $db->prepare($sql);
                    $db->execute($stmt, [$ticket_id, $user_id, $message]);

                    header('Location: support.php?ticket_id=' . $ticket_id . '&created=1');
                    exit;
                }

                $error = 'Failed to create ticket. Please try again.';
            }
        }
    }

    if ($action === 'send_message') {
        $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        $message = trim($_POST['message'] ?? '');

        if ($ticket_id <= 0 || $message === '') {
            $error = 'Message cannot be empty.';
        } else {
            $sql = "SELECT * FROM support_tickets WHERE ticket_id = ? AND tenant_id = ?";
            $stmt = $db->prepare($sql);
            $ticket = $db->getSingle($stmt, [$ticket_id, $user_id]);

            if (!$ticket) {
                $error = 'Ticket not found.';
            } elseif (($ticket['status'] ?? '') === 'closed') {
                $error = 'This ticket is closed.';
            } else {
                $sql = "INSERT INTO support_messages (ticket_id, sender_role, sender_id, message, created_at)
                        VALUES (?, 'tenant', ?, ?, NOW())";
                $stmt = $db->prepare($sql);

                if ($db->execute($stmt, [$ticket_id, $user_id, $message])) {
                    $sql = "UPDATE support_tickets SET updated_at = NOW() WHERE ticket_id = ?";
                    $db->execute($db->prepare($sql), [$ticket_id]);

                    if (!empty($ticket['assigned_employee_id'])) {
                        $notif = "INSERT INTO notifications (user_id, title, message, type, created_at)
                                  VALUES (?, 'Support Ticket Update', 'A tenant replied to a support ticket.', 'info', NOW())";
                        $db->execute($db->prepare($notif), [(int)$ticket['assigned_employee_id']]);
                    }

                    header('Location: support.php?ticket_id=' . $ticket_id . '&message_sent=1');
                    exit;
                }

                $error = 'Failed to send message.';
            }
        }
    }

    if ($action === 'close_ticket') {
        $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;

        $sql = "UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE ticket_id = ? AND tenant_id = ?";
        $stmt = $db->prepare($sql);
        if ($db->execute($stmt, [$ticket_id, $user_id])) {
            header('Location: support.php?ticket_id=' . $ticket_id . '&closed=1');
            exit;
        }
        $error = 'Failed to close ticket.';
    }
}

$sql = "SELECT t.*,
               (SELECT m.message FROM support_messages m WHERE m.ticket_id = t.ticket_id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
               (SELECT m.created_at FROM support_messages m WHERE m.ticket_id = t.ticket_id ORDER BY m.created_at DESC LIMIT 1) AS last_message_at,
               e.full_name AS assigned_employee_name
        FROM support_tickets t
        LEFT JOIN users e ON t.assigned_employee_id = e.user_id
        WHERE t.tenant_id = ?
        ORDER BY COALESCE(t.updated_at, t.created_at) DESC";
$stmt = $db->prepare($sql);
$tickets = $db->getMultiple($stmt, [$user_id]);

$active_ticket = null;
$messages = [];

if ($active_ticket_id) {
    $sql = "SELECT t.*, e.full_name AS assigned_employee_name
            FROM support_tickets t
            LEFT JOIN users e ON t.assigned_employee_id = e.user_id
            WHERE t.ticket_id = ? AND t.tenant_id = ?";
    $stmt = $db->prepare($sql);
    $active_ticket = $db->getSingle($stmt, [$active_ticket_id, $user_id]);

    if ($active_ticket) {
        $sql = "SELECT m.*, u.full_name
                FROM support_messages m
                LEFT JOIN users u ON m.sender_id = u.user_id
                WHERE m.ticket_id = ?
                ORDER BY m.created_at ASC";
        $stmt = $db->prepare($sql);
        $messages = $db->getMultiple($stmt, [$active_ticket_id]);
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
                    <h1 class="h3 mb-1">Support Center</h1>
                    <p class="text-muted mb-0">Need help? Chat with our support team, employees, or property owners anytime.</p>
                </div>
                <button class="btn btn-primary" onclick="openNewTicketModal()" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    <i class="fas fa-plus me-2"></i>New Ticket
                </button>
            </div>

            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Support ticket created successfully. Our team will respond shortly.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['message_sent'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    Your message has been sent.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['closed'])): ?>
                <div class="alert alert-secondary alert-dismissible fade show" role="alert">
                    Ticket closed. If you need more help, open a new ticket.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

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
                            <h6 class="mb-0">My Tickets</h6>
                            <span class="badge bg-secondary"><?php echo count($tickets); ?></span>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 70vh; overflow:auto;">
                            <?php if (empty($tickets)): ?>
                                <div class="p-4 text-center text-muted">No tickets yet.</div>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): ?>
                                    <?php
                                        $is_active = $active_ticket_id && (int)$t['ticket_id'] === (int)$active_ticket_id;
                                        $status = $t['status'] ?? 'open';
                                        $badge = $status === 'closed' ? 'secondary' : 'success';
                                    ?>
                                    <a class="list-group-item list-group-item-action <?php echo $is_active ? 'active' : ''; ?>" href="support.php?ticket_id=<?php echo (int)$t['ticket_id']; ?>">
                                        <div class="d-flex justify-content-between">
                                            <div class="fw-semibold text-truncate" style="max-width: 220px;">
                                                <?php echo htmlspecialchars($t['subject']); ?>
                                            </div>
                                            <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                                        </div>
                                        <div class="small text-muted text-truncate">
                                            <?php echo htmlspecialchars($t['last_message'] ?? ''); ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?php echo !empty($t['last_message_at']) ? date('M d, H:i', strtotime($t['last_message_at'])) : date('M d, H:i', strtotime($t['created_at'])); ?>
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
                                            <?php if (!empty($active_ticket['assigned_employee_name'])): ?>
                                                - Assigned to <?php echo htmlspecialchars($active_ticket['assigned_employee_name']); ?>
                                            <?php else: ?>
                                                - Not assigned yet
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
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
                                    Select a ticket to view messages, or create a new one.
                                </div>
                            <?php else: ?>
                                <?php if (empty($messages)): ?>
                                    <div class="text-center text-muted py-5">No messages yet.</div>
                                <?php else: ?>
                                    <?php foreach ($messages as $m): ?>
                                        <?php
                                            $mine = ($m['sender_role'] === 'tenant' && (int)$m['sender_id'] === (int)$user_id);
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
                                                    <?php echo htmlspecialchars($m['full_name'] ?? ($m['sender_role'] === 'employee' ? 'Employee' : 'You')); ?>
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
                                        <textarea name="message" class="form-control" rows="2" placeholder="Type your message..."></textarea>
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

<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">New Support Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_ticket">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Subject *</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="general">General</option>
                                <option value="payment">Payment</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="account">Account</option>
                                <option value="agreement">Agreement</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Send to *</label>
                            <select class="form-select" name="target_role" required>
                                <option value="owner">Owner</option>
                                <option value="all">All (Owner + Employee)</option>
                                <option value="employee">Employee</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNewTicketModal() {
    console.log('Opening new ticket modal');
    
    // Try to open modal with Bootstrap
    try {
        const modal = new bootstrap.Modal(document.getElementById('newTicketModal'));
        modal.show();
    } catch (e) {
        console.error('Bootstrap modal error:', e);
        // Fallback: show modal manually
        const modal = document.getElementById('newTicketModal');
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'modal-backdrop';
        document.body.appendChild(backdrop);
    }
}

// Manual close function for modal
function closeNewTicketModal() {
    const modal = document.getElementById('newTicketModal');
    modal.style.display = 'none';
    modal.classList.remove('show');
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    const backdrop = document.getElementById('modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

// Add close handlers to modal close buttons
document.addEventListener('DOMContentLoaded', function() {
    // Handle close button clicks
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            closeNewTicketModal();
        });
    });
    
    // Handle backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            closeNewTicketModal();
        }
    });
});
</script>

<script>
    window.supportChatConfig = {
        ticketId: <?php echo (int)$active_ticket_id; ?>,
        userId: <?php echo (int)$user_id; ?>,
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
