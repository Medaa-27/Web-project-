<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Submit Vacating Notice - Aksum Rental System";

$user_id = $session->getUserId();

// ONE-TIME TABLE FIX: Ensure notice_id is auto_increment
try {
    $stmt = $db->prepare("SHOW COLUMNS FROM vacating_notices WHERE Field = 'notice_id'");
    $col = $db->getSingle($stmt);
    if ($col && strpos($col['Extra'], 'auto_increment') === false) {
        // First delete any broken rows with ID 0 to avoid primary key conflicts
        $db->execute($db->prepare("DELETE FROM vacating_notices WHERE notice_id = 0"));
        // Then apply the auto_increment
        $db->execute($db->prepare("ALTER TABLE vacating_notices MODIFY notice_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY"));
    }
} catch (Exception $e) {
    error_log("TABLE FIX ERROR: " . $e->getMessage());
}

// Get active rental agreements
$sql = "SELECT ra.*, p.title, p.monthly_rent, l.location_name
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        WHERE ra.tenant_id = ? AND ra.status = 'active'
        ORDER BY ra.created_at DESC";
$stmt = $db->prepare($sql);
$active_agreements = $db->getMultiple($stmt, [$user_id]);

// Get existing vacating notices
$sql = "SELECT vn.*, p.title as property_title, l.location_name
        FROM vacating_notices vn
        JOIN rental_agreements ra ON vn.agreement_id = ra.agreement_id
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        WHERE vn.tenant_id = ?
        ORDER BY vn.created_at DESC";
$stmt = $db->prepare($sql);
$existing_notices = $db->getMultiple($stmt, [$user_id]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agreement_id = $_POST['agreement_id'];
    $vacating_date = $_POST['vacating_date'];
    $reason = $_POST['reason'];
    $forwarding_address = $_POST['forwarding_address'];
    $contact_info = $_POST['contact_info'];
    
    // Validate that vacating date is at least 2 weeks from now
    $min_date = date('Y-m-d', strtotime('+2 weeks'));
    if ($vacating_date < $min_date) {
        $error = "Vacating date must be at least 2 weeks from today.";
    } else {
        // Get property_id from the rental agreement
        $sql = "SELECT property_id FROM rental_agreements WHERE agreement_id = ?";
        $stmt = $db->prepare($sql);
        $agreement = $db->getSingle($stmt, [$agreement_id]);
        $property_id = $agreement['property_id'] ?? 0;

        // Insert vacating notice
        $sql = "INSERT INTO vacating_notices (agreement_id, tenant_id, property_id, vacating_date, reason, forwarding_address, contact_info, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            $error = "Database prepare failed: " . $db->getLastError();
        } else {
            try {
                $db->execute($stmt, [$agreement_id, $user_id, $property_id, $vacating_date, $reason, $forwarding_address, $contact_info, 'pending']);
                
                // Get the newly created notice ID
                $notice_id = $db->lastInsertId();
                
                // Fallback: If lastInsertId() returns 0, try to find the ID by querying with exact data
                if (!$notice_id || $notice_id == 0) {
                    $fallback_sql = "SELECT notice_id FROM vacating_notices 
                                   WHERE tenant_id = ? AND agreement_id = ? AND vacating_date = ? AND status = 'pending'
                                   ORDER BY created_at DESC LIMIT 1";
                    $fallback_stmt = $db->prepare($fallback_sql);
                    $fallback_result = $db->getSingle($fallback_stmt, [$user_id, $agreement_id, $vacating_date]);
                    if ($fallback_result) {
                        $notice_id = $fallback_result['notice_id'];
                    }
                }
                
                // Final check: if it's still 0 or empty, we have a database integrity issue
                if (!$notice_id || $notice_id == 0) {
                    throw new Exception("Critical Error: Database failed to generate a valid ID for the vacating notice. Please contact support.");
                }
                
                error_log("TENANT DEBUG: Final notice ID: " . $notice_id);
                
                // Send notification to property owner
                $sql = "SELECT p.owner_id FROM rental_agreements ra JOIN properties p ON ra.property_id = p.property_id WHERE ra.agreement_id = ?";
                $stmt = $db->prepare($sql);
                if (!$stmt) {
                    $error = "Database prepare failed: " . $db->getLastError();
                } else {
                    $result = $db->getSingle($stmt, [$agreement_id]);
                    
                    if ($result) {
                        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, link, created_at) 
                                                    VALUES (?, 'Vacating Notice Submitted', 'A tenant has submitted a vacating notice for your property. Click to view the details.', 'warning', ?, NOW())";
                        $notification_stmt = $db->prepare($notification_sql);
                        if (!$notification_stmt) {
                            $error = "Database prepare failed: " . $db->getLastError();
                        } else {
                            $link = SITE_URL . 'owner/vacating-notice-details.php?notice_id=' . $notice_id;
                            error_log("TENANT DEBUG: Creating notification with link: " . $link);
                            error_log("TENANT DEBUG: Owner ID: " . $result['owner_id']);
                            $db->execute($notification_stmt, [$result['owner_id'], $link]);
                            error_log("TENANT DEBUG: Notification created successfully");
                        }
                    }
                    
                    $success = "Vacating notice submitted successfully! The property owner will be notified.";
                }
            } catch (Exception $e) {
                $error = "Error submitting vacating notice: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-0">Submit Vacating Notice</h1>
                    <p class="text-muted mb-0">Provide at least 2 weeks notice before vacating your rental property</p>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Vacating Notice Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">New Vacating Notice</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($active_agreements)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-home fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No Active Rentals</h6>
                            <p class="text-muted">You don't have any active rental agreements to submit vacating notice for.</p>
                            <a href="search.php" class="btn btn-primary">Find Properties</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Select Property *</label>
                                        <select name="agreement_id" class="form-select" required>
                                            <option value="">Choose property to vacate</option>
                                            <?php foreach ($active_agreements as $agreement): ?>
                                                <option value="<?php echo $agreement['agreement_id']; ?>">
                                                    <?php echo htmlspecialchars($agreement['title']); ?> - <?php echo htmlspecialchars($agreement['location_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Vacating Date *</label>
                                        <input type="date" name="vacating_date" class="form-control" 
                                               min="<?php echo date('Y-m-d', strtotime('+2 weeks')); ?>" required>
                                        <small class="text-muted">Minimum 2 weeks notice required</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason for Vacating *</label>
                                <textarea name="reason" class="form-control" rows="3" required
                                          placeholder="Please provide a reason for vacating the property..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Forwarding Address</label>
                                <textarea name="forwarding_address" class="form-control" rows="2"
                                          placeholder="Your new address where security deposit should be sent (if applicable)..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contact Information</label>
                                <input type="text" name="contact_info" class="form-control" 
                                       placeholder="Phone number or email where we can reach you after vacating..."
                                       value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                                <ul class="mb-0">
                                    <li>You must provide at least 2 weeks notice before vacating</li>
                                    <li>Your security deposit will be processed within 30 days after vacating</li>
                                    <li>The property will be inspected for damages before deposit return</li>
                                    <li>All utilities must be transferred out of your name</li>
                                    <li>The property must be left in clean condition</li>
                                </ul>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Notice
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Existing Notices -->
            <?php if (!empty($existing_notices)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Vacating Notices</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Location</th>
                                        <th>Vacating Date</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existing_notices as $notice): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($notice['property_title']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($notice['location_name']); ?></td>
                                            <td>
                                                <strong><?php echo date('M d, Y', strtotime($notice['vacating_date'])); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $notice['status'] == 'approved' ? 'success' : ($notice['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($notice['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($notice['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewNoticeDetails(<?php echo $notice['notice_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($notice['status'] == 'pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="cancelNotice(<?php echo $notice['notice_id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Notice Details Modal -->
<div class="modal fade" id="noticeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vacating Notice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="noticeDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // View notice details
    window.viewNoticeDetails = function(noticeId) {
        $.ajax({
            url: '../api/get-notice-details.php',
            method: 'GET',
            data: { notice_id: noticeId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Property Information</h6>
                                <p><strong>Property:</strong> ${response.data.property_title}</p>
                                <p><strong>Location:</strong> ${response.data.location_name}</p>
                                <p><strong>Vacating Date:</strong> ${new Date(response.data.vacating_date).toLocaleDateString()}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Notice Information</h6>
                                <p><strong>Status:</strong> <span class="badge bg-${response.data.status == 'approved' ? 'success' : (response.data.status == 'rejected' ? 'danger' : 'warning')}">${response.data.status}</span></p>
                                <p><strong>Submitted:</strong> ${new Date(response.data.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6>Reason for Vacating</h6>
                            <p>${response.data.reason}</p>
                        </div>
                        ${response.data.forwarding_address ? `
                        <div class="mt-3">
                            <h6>Forwarding Address</h6>
                            <p>${response.data.forwarding_address}</p>
                        </div>
                        ` : ''}
                        ${response.data.contact_info ? `
                        <div class="mt-3">
                            <h6>Contact Information</h6>
                            <p>${response.data.contact_info}</p>
                        </div>
                        ` : ''}
                    `;
                    $('#noticeDetailsContent').html(html);
                    $('#noticeDetailsModal').modal('show');
                } else {
                    alert('Error loading notice details');
                }
            },
            error: function() {
                alert('Error loading notice details');
            }
        });
    };
    
    // Cancel notice
    window.cancelNotice = function(noticeId) {
        if (confirm('Are you sure you want to cancel this vacating notice?')) {
            $.ajax({
                url: '../api/cancel-notice.php',
                method: 'POST',
                data: { notice_id: noticeId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Vacating notice cancelled successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error cancelling notice');
                }
            });
        }
    };
});
</script>
