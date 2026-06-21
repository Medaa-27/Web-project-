<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Property Maintenance - Aksum Rental System";

$user_id = $session->getUserId();

// Get maintenance requests - simplified approach
$maintenance_requests = [];
try {
    $sql = "SELECT mr.*, p.title as property_title 
            FROM maintenance_requests mr
            JOIN properties p ON mr.property_id = p.property_id
            WHERE mr.tenant_id = ?
            ORDER BY mr.created_at DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $requests = $db->getMultiple($stmt, [$user_id]);
        foreach ($requests as $request) {
            $request['location_name'] = 'Addis Ababa';
            $request['owner_name'] = 'Property Owner';
            $request['owner_phone'] = '';
            $request['owner_email'] = '';
            $request['agreement_id'] = '';
            $request['monthly_rent'] = 0;
            
            // Map database columns to expected format
            $request['request_type'] = $request['issue_type'] ?? 'other';
            $request['priority'] = $request['priority'] ?? 'medium';
            $request['description'] = $request['notes'] ?? '';
            
            $request['access_instructions'] = '';
            if (!empty($request['description'])) {
                $parts = explode("\n\nAccess Instructions:", $request['description'], 2);
                $request['description'] = trim($parts[0]);
                if (!empty($parts[1])) {
                    $request['access_instructions'] = trim($parts[1]);
                }
            }
            
            $maintenance_requests[] = $request;
        }
    }
} catch (Exception $e) {
    $maintenance_requests = [];
}

// Get active rental agreements for the dropdown
$active_agreements = [];
try {
    $sql = "SELECT ra.agreement_id, p.title as property_title, l.location_name
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            LEFT JOIN locations l ON p.location_id = l.location_id
            WHERE ra.tenant_id = ? AND ra.status = 'active'
            ORDER BY p.title";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $agreements = $db->getMultiple($stmt, [$user_id]);
        foreach ($agreements as $agreement) {
            // Set default location if not found
            $agreement['location_name'] = $agreement['location_name'] ?? 'Addis Ababa';
            $active_agreements[] = $agreement;
        }
        error_log("Found " . count($active_agreements) . " active agreements for user " . $user_id);
    } else {
        error_log("Failed to prepare agreements query for user " . $user_id);
    }
} catch (Exception $e) {
    $active_agreements = [];
    error_log("Active agreements error: " . $e->getMessage());
}

// Debug: Let's also check all rental agreements for this tenant
$all_agreements = [];
try {
    $sql = "SELECT ra.agreement_id, ra.status, p.title as property_title
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            WHERE ra.tenant_id = ?
            ORDER BY ra.created_at DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $all_agreements = $db->getMultiple($stmt, [$user_id]);
        error_log("Found " . count($all_agreements) . " total agreements for user " . $user_id);
        foreach ($all_agreements as $agreement) {
            error_log("Agreement ID: " . $agreement['agreement_id'] . ", Status: " . $agreement['status'] . ", Property: " . $agreement['property_title']);
        }
    }
} catch (Exception $e) {
    error_log("All agreements error: " . $e->getMessage());
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agreement_id = $_POST['agreement_id'];
    $issue_type = $_POST['issue_type'];
    $priority = $_POST['priority'];
    $description = $_POST['description'];
    $access_instructions = $_POST['access_instructions'] ?? '';

    try {
        // Validate that tenant has active rental agreements
        if (empty($active_agreements)) {
            throw new Exception('You must have an active rental agreement to submit maintenance requests.');
        }
        
        // Handle agreement_id to get property_id and owner_id
        $sql = "SELECT ra.property_id, p.owner_id FROM rental_agreements ra 
                JOIN properties p ON ra.property_id = p.property_id 
                WHERE ra.agreement_id = ? AND ra.tenant_id = ? AND ra.status = 'active'";
        $stmt = $db->prepare($sql);
        $agreement = $db->getSingle($stmt, [$agreement_id, $user_id]);

        if (!$agreement) {
            throw new Exception('Invalid rental agreement selected. Please select your active rental property.');
        }

        $property_id = $agreement['property_id'];
        $owner_id = $agreement['owner_id'];

        $priority_db = $priority;
        $full_description = $description;
        if (!empty($access_instructions)) {
            $full_description .= "\n\nAccess Instructions: " . $access_instructions;
        }

        // Insert maintenance request
        $sql = "INSERT INTO maintenance_requests (property_id, tenant_id, owner_id, issue_type, notes, priority, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$property_id, $user_id, $owner_id, $issue_type, $full_description, $priority_db]);

        // Send notification to property owner
        if ($owner_id) {
            $sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                    VALUES (?, 'New Maintenance Request', 
                    'A tenant has submitted a new maintenance request. Please check your maintenance panel.', 
                    'alert', NOW())";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$owner_id]);
        }

        header('Location: maintenance.php?success=1');
        exit;
    } catch (Exception $e) {
        $error = 'Error submitting maintenance request: ' . $e->getMessage();
        error_log("Maintenance request error: " . $e->getMessage());
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
                        <h1 class="h3 mb-2">Property Maintenance</h1>
                        <p class="mb-0 opacity-90">Report maintenance issues and track repair progress</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                            <i class="fas fa-plus-circle me-2"></i>New Request
                        </button>
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
                            <h6 class="alert-heading mb-1">Maintenance Request Submitted!</h6>
                            <p class="mb-0">Your maintenance request has been submitted successfully. The property owner will be notified.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Error!</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
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
                            <h6 class="alert-heading mb-1">Urgent Maintenance Request</h6>
                            <p class="mb-0">You have <?php echo $stats['urgent']; ?> urgent maintenance request(s) that require immediate attention.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Maintenance Requests List -->
            <div class="card maintenance-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Maintenance History</h5>
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
                            <p class="text-muted">You haven't submitted any maintenance requests yet.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                                <i class="fas fa-plus-circle me-2"></i>Submit Your First Request
                            </button>
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
                                                    <p class="mb-2"><?php echo htmlspecialchars(substr($request['description'], 0, 100)) . '...'; ?></p>
                                                    <?php if (!empty($request['access_instructions'])): ?>
                                                        <div class="alert alert-info py-2 mb-2">
                                                            <small><i class="fas fa-key me-1"></i>Access: <?php echo htmlspecialchars(substr($request['access_instructions'], 0, 80)) . '...'; ?></small>
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
                                                        <?php echo date('M d, Y', strtotime($request['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <?php if (!empty($request['owner_reply'])): ?>
                                                <div class="alert alert-success py-2 mb-2">
                                                    <small><i class="fas fa-reply me-1"></i><strong>Owner Response:</strong> <?php echo htmlspecialchars(substr($request['owner_reply'], 0, 150)) . '...'; ?></small>
                                                </div>
                                            <?php endif; ?>
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

<!-- New Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="maintenance.php">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Property *</label>
                        <select name="agreement_id" class="form-select" required id="propertySelect">
                            <option value="">Choose your rental property...</option>
                            <?php if (!empty($active_agreements)): ?>
                                <?php foreach ($active_agreements as $agreement): ?>
                                    <option value="<?php echo $agreement['agreement_id']; ?>">
                                        <?php echo htmlspecialchars($agreement['property_title']); ?> - <?php echo htmlspecialchars($agreement['location_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php elseif (!empty($all_agreements)): ?>
                                <?php foreach ($all_agreements as $agreement): ?>
                                    <option value="<?php echo $agreement['agreement_id']; ?>">
                                        <?php echo htmlspecialchars($agreement['property_title']); ?> - <?php echo htmlspecialchars($agreement['location_name'] ?? 'Addis Ababa'); ?> (Status: <?php echo htmlspecialchars($agreement['status']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Add test options for debugging -->
                                <option value="test_1">Modern 3 Bedroom House - Addis Ababa (Test)</option>
                                <option value="test_2">Cozy 2 Bedroom Apartment - Addis Ababa (Test)</option>
                                <option value="test_3">Luxury 4 Bedroom Villa - Addis Ababa (Test)</option>
                            <?php endif; ?>
                        </select>
                        
                        <!-- Always show debug info -->
                        <div class="alert alert-info mt-2">
                            <small>
                                <strong>Debug Info:</strong><br>
                                Active Agreements: <?php echo count($active_agreements); ?><br>
                                Total Agreements: <?php echo count($all_agreements); ?><br>
                                User ID: <?php echo $user_id; ?><br>
                                <?php if (!empty($all_agreements)): ?>
                                    Your Agreements:
                                    <?php foreach ($all_agreements as $agreement): ?>
                                        <br>- <?php echo htmlspecialchars($agreement['property_title']); ?> (<?php echo htmlspecialchars($agreement['status']); ?>)
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <?php if (empty($active_agreements) && empty($all_agreements)): ?>
                            <div class="alert alert-warning mt-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>No Rental Agreements:</strong> You don't have any rental agreements. Using test options for demonstration.
                            </div>
                        <?php elseif (empty($active_agreements) && !empty($all_agreements)): ?>
                            <div class="alert alert-info mt-2">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>No Active Rentals:</strong> You have rental agreements but none are currently active. Showing all agreements above.
                            </div>
                        <?php else: ?>
                            <small class="text-muted">Select the property where you need maintenance service.</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Issue Type *</label>
                        <select name="issue_type" class="form-select" required>
                            <option value="">Select issue type</option>
                            <option value="plumbing">Plumbing Issues</option>
                            <option value="electrical">Electrical Problems</option>
                            <option value="hvac">HVAC/Air Conditioning</option>
                            <option value="appliance">Appliance Repair</option>
                            <option value="structural">Structural Issues</option>
                            <option value="pest_control">Pest Control</option>
                            <option value="cleaning">Cleaning Services</option>
                            <option value="other">Other Issues</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Priority Level *</label>
                        <select name="priority" class="form-select" required>
                            <option value="">Select priority</option>
                            <option value="low">Low - Can wait a few days</option>
                            <option value="medium">Medium - Needs attention soon</option>
                            <option value="high">High - Urgent attention needed</option>
                            <option value="urgent">Urgent - Emergency situation</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="4" required 
                                  placeholder="Please describe the issue in detail. Include when it started, how often it occurs, and any specific details that would help the maintenance team."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Access Instructions</label>
                        <textarea name="access_instructions" class="form-control" rows="3" 
                                  placeholder="Any special instructions for accessing the property (e.g., 'Call before arriving', 'Dog on premises', 'Use back entrance', etc.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php include '../includes/footer.php'; ?>
