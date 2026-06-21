<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Support Inquiries Management";

// Handle support ticket actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_ticket'])) {
        $ticket_id = $_POST['ticket_id'];
        $status = $_POST['status'];
        $priority = $_POST['priority'];
        $response = $_POST['response'];
        
        try {
            // Update ticket status and priority
            $sql = "UPDATE support_tickets SET status = ?, priority = ?, updated_at = NOW() WHERE ticket_id = ?";
            $stmt = $db->prepare($sql);
            
            if ($db->execute($stmt, [$status, $priority, $ticket_id])) {
                // Add response as a message if provided
                if (!empty(trim($response))) {
                    $employee_id = $session->getUserId();
                    $msg_sql = "INSERT INTO support_messages (ticket_id, sender_role, sender_id, message, created_at) 
                                VALUES (?, 'employee', ?, ?, NOW())";
                    $msg_stmt = $db->prepare($msg_sql);
                    $db->execute($msg_stmt, [$ticket_id, $employee_id, $response]);
                }
                
                // Get user ID for notification
                $user_sql = "SELECT tenant_id FROM support_tickets WHERE ticket_id = ?";
                $user_stmt = $db->prepare($user_sql);
                $user = $db->getSingle($user_stmt, [$ticket_id]);
                
                if ($user) {
                    $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                                  VALUES (?, 'Support Response', ?, 'success', NOW())";
                    $notif_stmt = $db->prepare($notif_sql);
                    $db->execute($notif_stmt, [$user['tenant_id'], "Your support ticket #$ticket_id has been updated with a response."]);
                }
                
                $_SESSION['success'] = "Support ticket updated successfully";
                header("Location: support-inquiries.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Update ticket error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update support ticket";
        }
    }
    
    if (isset($_POST['bulk_action'])) {
        $ticket_ids = $_POST['ticket_ids'] ?? [];
        $action = $_POST['bulk_action_type'];
        
        if (!empty($ticket_ids) && $action) {
            $placeholders = str_repeat('?,', count($ticket_ids) - 1) . '?';
            
            switch ($action) {
                case 'close':
                    $sql = "UPDATE support_tickets SET status = 'CLOSED' WHERE ticket_id IN ($placeholders)";
                    $message = "Support tickets closed successfully";
                    break;
                case 'assign':
                    $sql = "UPDATE support_tickets SET status = 'IN_PROGRESS', assigned_employee_id = ? WHERE ticket_id IN ($placeholders)";
                    $message = "Support tickets assigned successfully";
                    break;
                case 'high_priority':
                    $sql = "UPDATE support_tickets SET priority = 'high' WHERE ticket_id IN ($placeholders)";
                    $message = "Support tickets marked as high priority";
                    break;
            }
            
            $stmt = $db->prepare($sql);
            $params = $action == 'assign' ? [$session->getUserId(), ...$ticket_ids] : $ticket_ids;
            
            if ($db->execute($stmt, $params)) {
                $_SESSION['success'] = $message;
            }
        }
        header("Location: support-inquiries.php");
        exit();
    }
}

// Get support tickets with pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';

// Build query
$sql = "SELECT st.*, u.full_name, u.email, u.phone,
               e.full_name as assigned_employee_name,
               (SELECT m.message FROM support_messages m WHERE m.ticket_id = st.ticket_id ORDER BY m.created_at ASC LIMIT 1) as first_message
        FROM support_tickets st 
        JOIN users u ON st.tenant_id = u.user_id 
        LEFT JOIN users e ON st.assigned_employee_id = e.user_id
        WHERE 1=1";
        
$params = [];
$count_params = [];

if ($search) {
    $sql .= " AND (st.subject LIKE ? OR (SELECT m.message FROM support_messages m WHERE m.ticket_id = st.ticket_id ORDER BY m.created_at ASC LIMIT 1) LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    
    $count_params = array_merge($count_params, $params);
}

if ($status_filter) {
    $sql .= " AND st.status = ?";
    $params[] = $status_filter;
    $count_params[] = $status_filter;
}

if ($priority_filter) {
    $sql .= " AND st.priority = ?";
    $params[] = $priority_filter;
    $count_params[] = $priority_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM support_tickets st JOIN users u ON st.tenant_id = u.user_id WHERE 1=1";
if ($search) {
    $count_sql .= " AND (st.subject LIKE ? OR (SELECT m.message FROM support_messages m WHERE m.ticket_id = st.ticket_id ORDER BY m.created_at ASC LIMIT 1) LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
}
if ($status_filter) {
    $count_sql .= " AND st.status = ?";
}
if ($priority_filter) {
    $count_sql .= " AND st.priority = ?";
}

$count_stmt = $db->prepare($count_sql);
$db->execute($count_stmt, $count_params);
$total_tickets = $count_stmt->fetch()['total'];

// Add pagination
$sql .= " ORDER BY st.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$tickets = $db->getMultiple($stmt, $params);

$total_pages = ceil($total_tickets / $limit);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 mb-0">Support Inquiries Management</h1>
                            <p class="text-muted mb-0">Manage user support tickets and inquiries</p>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="Search tickets..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-primary bg-opacity-10 text-primary mx-auto">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $total_tickets; ?></h3>
                            <p class="text-muted mb-0">Total Tickets</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold">
                                <?php 
                                $open_sql = "SELECT COUNT(*) as count FROM support_tickets WHERE status = 'OPEN'";
                                $open_stmt = $db->prepare($open_sql);
                                $db->execute($open_stmt);
                                echo $open_stmt->fetch()['count'];
                                ?>
                            </h3>
                            <p class="text-muted mb-0">Open Tickets</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">
                                <i class="fas fa-spinner fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold">
                                <?php 
                                $progress_sql = "SELECT COUNT(*) as count FROM support_tickets WHERE status = 'IN_PROGRESS'";
                                $progress_stmt = $db->prepare($progress_sql);
                                $db->execute($progress_stmt);
                                echo $progress_stmt->fetch()['count'];
                                ?>
                            </h3>
                            <p class="text-muted mb-0">In Progress</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-danger bg-opacity-10 text-danger mx-auto">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold">
                                <?php 
                                $urgent_sql = "SELECT COUNT(*) as count FROM support_tickets WHERE priority = 'urgent'";
                                $urgent_stmt = $db->prepare($urgent_sql);
                                $db->execute($urgent_stmt);
                                echo $urgent_stmt->fetch()['count'];
                                ?>
                            </h3>
                            <p class="text-muted mb-0">Urgent Tickets</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="OPEN" <?php echo $status_filter == 'OPEN' ? 'selected' : ''; ?>>Open</option>
                                    <option value="IN_PROGRESS" <?php echo $status_filter == 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="CLOSED" <?php echo $status_filter == 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="priority" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Priority</option>
                                    <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="normal" <?php echo $priority_filter == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo $priority_filter == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="support-inquiries.php" class="btn btn-outline-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Support Tickets Table -->
            <div class="card dashboard-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()"></th>
                                    <th>Ticket ID</th>
                                    <th>Subject</th>
                                    <th>User</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No support tickets found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['ticket_id']; ?>" class="ticket-checkbox"></td>
                                            <td>
                                                <strong>#<?php echo str_pad($ticket['ticket_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ticket['subject']); ?></strong>
                                                <br><small class="text-muted"><?php echo substr(htmlspecialchars($ticket['first_message'] ?? 'No message'), 0, 50); ?>...</small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($ticket['full_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($ticket['email']); ?></small>
                                                    <?php if ($ticket['phone']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($ticket['phone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $priority_colors = [
                                                    'low' => 'secondary',
                                                    'normal' => 'primary', 
                                                    'high' => 'warning',
                                                    'urgent' => 'danger'
                                                ];
                                                $color = $priority_colors[$ticket['priority']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($ticket['priority']); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'OPEN' => 'warning',
                                                    'IN_PROGRESS' => 'info',
                                                    'CLOSED' => 'success'
                                                ];
                                                $status_color = $status_colors[$ticket['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_color; ?>"><?php echo $ticket['status']; ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="respondToTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                        <i class="fas fa-reply"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <form method="POST" action="" id="bulkActionForm">
                                <div class="d-flex align-items-center">
                                    <select name="bulk_action_type" class="form-select me-2" style="width: auto;">
                                        <option value="">Bulk Actions</option>
                                        <option value="close">Close Tickets</option>
                                        <option value="assign">Assign to Me</option>
                                        <option value="high_priority">Mark as High Priority</option>
                                    </select>
                                    <button type="submit" name="bulk_action" class="btn btn-primary">Apply</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>">Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Ticket Modal -->
<div class="modal fade" id="viewTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Support Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ticketDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Respond to Ticket Modal -->
<div class="modal fade" id="respondTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Respond to Support Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="respondForm">
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="respondTicketId">
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="OPEN">Open</option>
                            <option value="IN_PROGRESS">In Progress</option>
                            <option value="CLOSED">Closed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Response</label>
                        <textarea name="response" class="form-control" rows="5" placeholder="Enter your response..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_ticket" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.ticket-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
}

function viewTicket(id) {
    fetch(`api/get-support-ticket.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            const details = document.getElementById('ticketDetails');
            details.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Ticket ID:</strong> #${String(data.ticket_id).padStart(6, '0')}
                        </div>
                        <div class="mb-3">
                            <strong>Subject:</strong> ${data.subject}
                        </div>
                        <div class="mb-3">
                            <strong>Category:</strong> ${data.category}
                        </div>
                        <div class="mb-3">
                            <strong>Priority:</strong> <span class="badge bg-${data.priority_color}">${data.priority}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong> <span class="badge bg-${data.status_color}">${data.status}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>User:</strong> ${data.full_name}
                        </div>
                        <div class="mb-3">
                            <strong>Email:</strong> ${data.email}
                        </div>
                        <div class="mb-3">
                            <strong>Phone:</strong> ${data.phone || 'Not provided'}
                        </div>
                        <div class="mb-3">
                            <strong>Created:</strong> ${data.created_at}
                        </div>
                        <div class="mb-3">
                            <strong>Assigned to:</strong> ${data.assigned_employee_name || 'Not assigned'}
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <strong>Message:</strong><br>
                    <div class="border rounded p-3 bg-light">
                        ${data.message.replace(/\n/g, '<br>')}
                    </div>
                </div>
                ${data.response ? `
                    <div class="mt-3">
                        <strong>Response:</strong><br>
                        <div class="border rounded p-3 bg-success bg-opacity-10">
                            ${data.response.replace(/\n/g, '<br>')}
                        </div>
                        <small class="text-muted">Responded at: ${data.responded_at}</small>
                    </div>
                ` : ''}
            `;
            
            // Try to open modal with Bootstrap, fallback to manual
            try {
                const modal = new bootstrap.Modal(document.getElementById('viewTicketModal'));
                modal.show();
            } catch (e) {
                console.error('Bootstrap modal error:', e);
                // Manual modal opening
                const modal = document.getElementById('viewTicketModal');
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
                
                // Add backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'modal-backdrop-view';
                document.body.appendChild(backdrop);
            }
        })
        .catch(error => {
            console.error('Error fetching ticket:', error);
            alert('Error loading ticket details');
        });
}

function respondToTicket(id) {
    document.getElementById('respondTicketId').value = id;
    
    // Try to open modal with Bootstrap, fallback to manual
    try {
        const modal = new bootstrap.Modal(document.getElementById('respondTicketModal'));
        modal.show();
    } catch (e) {
        console.error('Bootstrap modal error:', e);
        // Manual modal opening
        const modal = document.getElementById('respondTicketModal');
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'modal-backdrop-respond';
        document.body.appendChild(backdrop);
    }
}

// Manual close function for modals
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.remove('show');
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    const backdrop = document.getElementById('modal-backdrop-view') || document.getElementById('modal-backdrop-respond');
    if (backdrop) {
        backdrop.remove();
    }
}

// Add close handlers to modal close buttons
document.addEventListener('DOMContentLoaded', function() {
    // Handle close button clicks for all modals
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Handle backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.remove();
            document.body.classList.remove('modal-open');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
