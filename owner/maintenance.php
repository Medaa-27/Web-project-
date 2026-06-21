<?php
require_once '../includes/config.php';
$session->requireRole('owner');
$title = "Maintenance Management - Aksum Rental System";

$owner_id = $session->getUserId();

// Get maintenance requests - simplified approach
$maintenance_requests = [];
try {
    $sql = "SELECT mr.*, p.title as property_title 
            FROM maintenance_requests mr
            JOIN properties p ON mr.property_id = p.property_id
            WHERE p.owner_id = ? 
            ORDER BY mr.created_at DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $requests = $db->getMultiple($stmt, [$owner_id]);
        foreach ($requests as $request) {
            $request['tenant_name'] = 'Tenant';
            $request['tenant_phone'] = '';
            $request['tenant_email'] = '';
            
            // Map database columns to expected format
            $request['request_type'] = $request['issue_type'] ?? 'other';
            $request['description'] = $request['notes'] ?? '';
            $request['request_id'] = $request['maintenance_id']; // Map maintenance_id to request_id for form
            
            $maintenance_requests[] = $request;
        }
    }
} catch (Exception $e) {
    $maintenance_requests = [];
}

// Get statistics
$stats = [];
$stats['total'] = count($maintenance_requests);
$stats['pending'] = 0;
$stats['in_progress'] = 0;
$stats['completed'] = 0;
$stats['urgent'] = 0;

foreach ($maintenance_requests as $request) {
    switch ($request['status']) {
        case 'pending':
            $stats['pending']++;
            break;
        case 'in_progress':
            $stats['in_progress']++;
            break;
        case 'completed':
            $stats['completed']++;
            break;
    }
    if ($request['priority'] === 'urgent') {
        $stats['urgent']++;
    }
}

// Handle form submission for status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $owner_reply = $_POST['owner_reply'] ?? '';
    
    try {
        // Update request status and owner reply
        $sql = "UPDATE maintenance_requests SET status = ?, owner_reply = ?, updated_at = NOW() WHERE maintenance_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$status, $owner_reply, $request_id]);
        
        // Get request details for notification
        $sql = "SELECT mr.*, p.title as property_title, u.full_name as tenant_name, u.user_id as tenant_id
                FROM maintenance_requests mr
                JOIN properties p ON mr.property_id = p.property_id
                LEFT JOIN users u ON mr.tenant_id = u.user_id
                WHERE mr.maintenance_id = ?";
        $stmt = $db->prepare($sql);
        $request = $db->getSingle($stmt, [$request_id]);
        
        if ($request && $request['tenant_id']) {
            $status_messages = [
                'pending' => 'Your maintenance request is pending review.',
                'in_progress' => 'Your maintenance request is now being worked on.',
                'completed' => 'Your maintenance request has been completed.'
            ];
            
            $message = $status_messages[$status] ?? 'Your maintenance request status has been updated.';
            if (!empty($owner_reply)) {
                $message .= ' Owner: ' . $owner_reply;
            }
            
            // Send notification to tenant
            $sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                    VALUES (?, 'Maintenance Request Update', ?, 'info', NOW())";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$request['tenant_id'], $message]);
        }
        
        header('Location: maintenance.php?success=1');
        exit;
    } catch (Exception $e) {
        $error = 'Error updating maintenance request. Please try again.';
    }
}

include '../includes/header.php';
?>

<style>
.maintenance-gradient {
    background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
    color: white;
}
.maintenance-card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 15px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.maintenance-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}
.stat-card {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}
.request-item {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.3s ease;
}
.request-item:hover {
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 0 -1rem;
    padding: 1rem;
}
.priority-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-weight: 600;
}
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-weight: 600;
}
.issue-type-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-right: 1rem;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="maintenance-gradient rounded-4 p-4 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-2">Maintenance Management</h1>
                        <p class="mb-0 opacity-90">Manage maintenance requests from your tenants</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex align-items-center justify-content-end">
                            <div class="text-end me-3">
                                <div class="h5 mb-0"><?php echo $stats['pending']; ?></div>
                                <small class="opacity-90">Pending</small>
                            </div>
                            <div class="text-end">
                                <div class="h5 mb-0"><?php echo $stats['urgent']; ?></div>
                                <small class="opacity-90">Urgent</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Request Updated!</h6>
                            <p class="mb-0">Maintenance request status has been updated successfully.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-primary text-white">
                        <div class="stat-icon bg-white bg-opacity-20 text-primary mx-auto">
                            <i class="fas fa-tools fa-2x"></i>
                        </div>
                        <h3 class="display-6 fw-bold"><?php echo $stats['total']; ?></h3>
                        <p class="mb-0 opacity-90">Total Requests</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-warning text-white">
                        <div class="stat-icon bg-white bg-opacity-20 text-warning mx-auto">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <h3 class="display-6 fw-bold"><?php echo $stats['pending']; ?></h3>
                        <p class="mb-0 opacity-90">Pending</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-info text-white">
                        <div class="stat-icon bg-white bg-opacity-20 text-info mx-auto">
                            <i class="fas fa-spinner fa-2x"></i>
                        </div>
                        <h3 class="display-6 fw-bold"><?php echo $stats['in_progress']; ?></h3>
                        <p class="mb-0 opacity-90">In Progress</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-success text-white">
                        <div class="stat-icon bg-white bg-opacity-20 text-success mx-auto">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <h3 class="display-6 fw-bold"><?php echo $stats['completed']; ?></h3>
                        <p class="mb-0 opacity-90">Completed</p>
                    </div>
                </div>
            </div>

            <!-- Urgent Requests Alert -->
            <?php if ($stats['urgent'] > 0): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Urgent Maintenance Requests</h6>
                            <p class="mb-0">You have <?php echo $stats['urgent']; ?> urgent maintenance request(s) that require immediate attention.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Maintenance Requests List -->
            <div class="card maintenance-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Maintenance Requests</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($maintenance_requests)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tools fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Maintenance Requests</h5>
                            <p class="text-muted">No maintenance requests have been submitted by tenants yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="request-list">
                            <?php foreach ($maintenance_requests as $request): ?>
                                <div class="request-item" data-status="<?php echo $request['status']; ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="issue-type-icon bg-light">
                                            <?php
                                            $icons = [
                                                'plumbing' => 'fa-wrench',
                                                'electrical' => 'fa-bolt',
                                                'hvac' => 'fa-wind',
                                                'appliance' => 'fa-blender',
                                                'structural' => 'fa-home',
                                                'pest_control' => 'fa-bug',
                                                'cleaning' => 'fa-broom',
                                                'other' => 'fa-tools'
                                            ];
                                            $icon = $icons[$request['request_type']] ?? 'fa-tools';
                                            ?>
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $request['request_type']))); ?></h6>
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-home me-1"></i>
                                                        <?php echo htmlspecialchars($request['property_title']); ?>
                                                    </p>
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo htmlspecialchars($request['tenant_name']); ?>
                                                    </p>
                                                    <p class="mb-2"><?php echo htmlspecialchars($request['description']); ?></p>
                                                    <?php 
                                                    $description_parts = explode("\n\nAccess Instructions:", $request['description'], 2);
                                                    if (!empty($description_parts[1])): 
                                                    ?>
                                                        <div class="alert alert-info py-2 mb-2">
                                                            <small><i class="fas fa-key me-1"></i><strong>Access Instructions:</strong> <?php echo htmlspecialchars(trim($description_parts[1])); ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <div class="mb-2">
                                                        <?php
                                                        $priority_colors = [
                                                            'low' => 'secondary',
                                                            'medium' => 'warning',
                                                            'high' => 'danger',
                                                            'urgent' => 'danger'
                                                        ];
                                                        ?>
                                                        <span class="priority-badge bg-<?php echo $priority_colors[$request['priority']] ?? 'secondary'; ?> text-white">
                                                            <?php echo ucfirst($request['priority']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <?php
                                                        $status_colors = [
                                                            'pending' => 'warning',
                                                            'in_progress' => 'info',
                                                            'completed' => 'success'
                                                        ];
                                                        ?>
                                                        <span class="status-badge bg-<?php echo $status_colors[$request['status']] ?? 'secondary'; ?> text-white">
                                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($request['owner_reply'])): ?>
                                                <div class="alert alert-success py-2 mb-2">
                                                    <small><i class="fas fa-reply me-1"></i><strong>Your Response:</strong> <?php echo htmlspecialchars($request['owner_reply']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="openUpdateModal(<?php echo $request['maintenance_id']; ?>, '<?php echo $request['status']; ?>', '<?php echo htmlspecialchars($request['owner_reply'] ?? ''); ?>')"
                                                        data-bs-toggle="modal" data-bs-target="#updateModal" 
                                                        data-request-id="<?php echo $request['maintenance_id']; ?>"
                                                        data-status="<?php echo $request['status']; ?>"
                                                        data-reply="<?php echo htmlspecialchars($request['owner_reply'] ?? ''); ?>">
                                                    <i class="fas fa-edit me-1"></i>Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="request_id" id="request_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Update Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" id="status_select" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Response to Tenant</label>
                        <textarea name="owner_reply" class="form-control" rows="4" id="owner_reply" 
                                  placeholder="Provide details about the maintenance work, estimated completion time, or any instructions for the tenant."></textarea>
                        <small class="text-muted">This response will be sent to the tenant as a notification.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manual modal opening function
    window.openUpdateModal = function(requestId, status, reply) {
        console.log('Opening modal for request:', requestId, status, reply);
        
        // Set form values
        document.getElementById('request_id').value = requestId;
        document.getElementById('status_select').value = status;
        document.getElementById('owner_reply').value = reply || '';
        
        // Try to open modal with Bootstrap
        try {
            const modal = new bootstrap.Modal(document.getElementById('updateModal'));
            modal.show();
        } catch (e) {
            console.error('Bootstrap modal error:', e);
            // Fallback: show modal manually
            document.getElementById('updateModal').style.display = 'block';
            document.getElementById('updateModal').classList.add('show');
            document.body.classList.add('modal-open');
        }
    };
    
    // Manual modal closing function
    window.closeUpdateModal = function() {
        console.log('Closing modal');
        
        // Try to close modal with Bootstrap
        try {
            const modal = bootstrap.Modal.getInstance(document.getElementById('updateModal'));
            if (modal) {
                modal.hide();
            } else {
                // Fallback: hide modal manually
                document.getElementById('updateModal').style.display = 'none';
                document.getElementById('updateModal').classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        } catch (e) {
            console.error('Bootstrap modal close error:', e);
            // Fallback: hide modal manually
            document.getElementById('updateModal').style.display = 'none';
            document.getElementById('updateModal').classList.remove('show');
            document.body.classList.remove('modal-open');
        }
    };
    
    // Status filter functionality
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            const requestItems = document.querySelectorAll('.request-item');
            
            requestItems.forEach(item => {
                if (selectedStatus === '' || item.dataset.status === selectedStatus) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Update modal functionality
    const updateModal = document.getElementById('updateModal');
    if (updateModal) {
        updateModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-request-id');
            const status = button.getAttribute('data-status');
            const reply = button.getAttribute('data-reply');
            
            document.getElementById('request_id').value = requestId;
            document.getElementById('status_select').value = status;
            document.getElementById('owner_reply').value = reply || '';
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
