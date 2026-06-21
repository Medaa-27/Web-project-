<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = 'Rental Requests - Aksum Rental System';

$owner_id = $session->getUserId();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_request'])) {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (is_numeric($request_id) && in_array($action, ['approve', 'reject'], true)) {
        // Ensure request belongs to this owner
        $sql = "SELECT rr.*, p.property_id, p.owner_id, p.title, p.monthly_rent, p.security_deposit
                FROM rental_requests rr
                JOIN properties p ON rr.property_id = p.property_id
                WHERE rr.request_id = ? AND p.owner_id = ?";
        $stmt = $db->prepare($sql);
        $req = $db->getSingle($stmt, [$request_id, $owner_id]);

        if ($req && $req['status'] === 'pending') {
            if ($action === 'approve') {
                // Approve request
                $sql = "UPDATE rental_requests
                        SET status = 'approved', approved_by = ?, approved_at = NOW()
                        WHERE request_id = ?";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$owner_id, $request_id]);

                // Create rental agreement
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime('+' . (int)AGREEMENT_PERIOD . ' months'));
                $monthly_rent = (float)$req['monthly_rent'];
                $security_deposit = $req['security_deposit'] !== null ? (float)$req['security_deposit'] : 0;
                $advance_payment = round($monthly_rent * (ADVANCE_PERCENTAGE / 100), 2);

                $sql = "INSERT INTO rental_agreements
                            (request_id, tenant_id, property_id, start_date, end_date, monthly_rent, security_deposit, advance_payment,
                             status, signed_by_tenant, signed_by_owner, created_at, updated_at)
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?, ?, 'active', 0, 1, NOW(), NOW())";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [
                    $request_id,
                    $req['tenant_id'],
                    $req['property_id'],
                    $start_date,
                    $end_date,
                    $monthly_rent,
                    $security_deposit,
                    $advance_payment
                ]);

                // Update property status
                $sql = "UPDATE properties SET status = 'rented', updated_at = NOW() WHERE property_id = ?";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$req['property_id']]);

                // Reject all other pending requests for this property
                $sql = "UPDATE rental_requests
                        SET status = 'rejected', approved_by = ?, approved_at = NOW()
                        WHERE property_id = ? AND status = 'pending' AND request_id != ?";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$owner_id, $req['property_id'], $request_id]);

                // Notify tenant
                $msg = "Your rental request for '" . $req['title'] . "' has been approved.";
                createNotification($req['tenant_id'], 'Rental Request Approved', $msg, 'success', '../tenant/agreements.php', 15);

                // Send email to tenant
                try {
                    require_once '../includes/functions.php';
                    $tenantStmt = $db->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
                    $tenant = $db->getSingle($tenantStmt, [$req['tenant_id']]);
                    if ($tenant && !empty($tenant['email'])) {
                        $subject = "Rental Request Approved - " . SITE_NAME;
                        sendEmailTemplate($tenant['email'], $subject, 'request_decision', [
                            'user_name' => $tenant['full_name'],
                            'property_title' => $req['title'],
                            'status' => 'Approved',
                            'status_class' => 'status-approved',
                            'decision_date' => date('Y-m-d H:i:s'),
                            'message' => "Congratulations! Your rental request for '{$req['title']}' has been approved. You can now view your agreement and proceed with the payment.",
                            'action_link' => SITE_URL . 'tenant/agreements.php',
                            'site_name' => SITE_NAME
                        ]);
                    }
                } catch (Exception $e) {
                    error_log("Failed to send approval email: " . $e->getMessage());
                }

                $_SESSION['success'] = 'Request approved and agreement created.';
            } else {
                // Reject request
                $sql = "UPDATE rental_requests
                        SET status = 'rejected', approved_by = ?, approved_at = NOW()
                        WHERE request_id = ?";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$owner_id, $request_id]);

                // Set property back to available (simple rule)
                $sql = "UPDATE properties SET status = 'available', updated_at = NOW() WHERE property_id = ?";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$req['property_id']]);

                // Notify tenant
                $msg = "Your rental request for '" . $req['title'] . "' was rejected.";
                if ($notes !== '') {
                    $msg .= " Reason: " . $notes;
                }
                createNotification($req['tenant_id'], 'Rental Request Rejected', $msg, 'warning', null, 15);

                // Send email to tenant
                try {
                    require_once '../includes/functions.php';
                    $tenantStmt = $db->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
                    $tenant = $db->getSingle($tenantStmt, [$req['tenant_id']]);
                    if ($tenant && !empty($tenant['email'])) {
                        $subject = "Rental Request Update - " . SITE_NAME;
                        $reject_msg = "We regret to inform you that your rental request for '{$req['title']}' was rejected.";
                        if ($notes !== '') {
                            $reject_msg .= " Reason: " . $notes;
                        }
                        sendEmailTemplate($tenant['email'], $subject, 'request_decision', [
                            'user_name' => $tenant['full_name'],
                            'property_title' => $req['title'],
                            'status' => 'Rejected',
                            'status_class' => 'status-rejected',
                            'decision_date' => date('Y-m-d H:i:s'),
                            'message' => $reject_msg,
                            'action_link' => SITE_URL . 'tenant/requests.php',
                            'site_name' => SITE_NAME
                        ]);
                    }
                } catch (Exception $e) {
                    error_log("Failed to send rejection email: " . $e->getMessage());
                }

                $_SESSION['success'] = 'Request rejected.';
            }
        }
    }

    header('Location: requests.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'pending';
if (!in_array($status_filter, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
    $status_filter = 'pending';
}

$sql = "SELECT rr.*, p.title as property_title, p.address, p.monthly_rent,
               u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone
        FROM rental_requests rr
        JOIN properties p ON rr.property_id = p.property_id
        JOIN users u ON rr.tenant_id = u.user_id
        WHERE p.owner_id = ? AND rr.status = ?
        ORDER BY rr.created_at DESC";
$stmt = $db->prepare($sql);
$requests = $db->getMultiple($stmt, [$owner_id, $status_filter]);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">Rental Requests</h1>
                            <p class="text-muted mb-0">Review rental requests for your properties</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group">
                                <a href="?status=pending" class="btn btn-<?php echo $status_filter === 'pending' ? 'warning' : 'outline-warning'; ?>">Pending</a>
                                <a href="?status=approved" class="btn btn-<?php echo $status_filter === 'approved' ? 'success' : 'outline-success'; ?>">Approved</a>
                                <a href="?status=rejected" class="btn btn-<?php echo $status_filter === 'rejected' ? 'danger' : 'outline-danger'; ?>">Rejected</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Tenant</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No requests found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $r): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($r['property_title']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($r['address']); ?></div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($r['tenant_name']); ?>
                                                <div class="small text-muted"><?php echo htmlspecialchars($r['tenant_email']); ?></div>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($r['request_date'])); ?>
                                                <div class="small text-muted"><?php echo date('H:i', strtotime($r['created_at'])); ?></div>
                                            </td>
                                            <td>
                                                <?php
                                                $badge = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'cancelled' => 'secondary'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $badge[$r['status']] ?? 'secondary'; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($r['status'] === 'pending'): ?>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-success" onclick="openApproveModal(<?php echo (int)$r['request_id']; ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="openRejectModal(<?php echo (int)$r['request_id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Processed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Approve this rental request and create an agreement?</p>
                    <input type="hidden" name="request_id" id="approveRequestId">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="process_request" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Reject this rental request?</p>
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="process_request" value="1">
                    <div class="mb-3">
                        <label class="form-label">Reason (optional)</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('Owner requests page loaded');
});

// Simple functions to open modals
function openApproveModal(requestId) {
    console.log('Opening approve modal for request:', requestId);
    document.getElementById('approveRequestId').value = requestId;
    
    // Show modal manually
    const modal = document.getElementById('approveModal');
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function openRejectModal(requestId) {
    console.log('Opening reject modal for request:', requestId);
    document.getElementById('rejectRequestId').value = requestId;
    
    // Show modal manually
    const modal = document.getElementById('rejectModal');
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// Close modal functionality
document.addEventListener('click', function(event) {
    // Check if click is on modal close button or outside modal
    if (event.target.classList.contains('btn-close') || 
        (event.target.classList.contains('modal') && event.target.classList.contains('fade'))) {
        
        // Find the modal
        let modal = event.target;
        if (!modal.classList.contains('modal')) {
            modal = event.target.closest('.modal');
        }
        
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
