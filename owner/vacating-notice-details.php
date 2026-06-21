<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = "Vacating Notice Details - Aksum Rental System";

$user_id = $session->getUserId();
$notice_id = $_GET['notice_id'] ?? null;

// Handle link normalization if notice_id is missing but might be in the URL path
if (!$notice_id && isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('/notice_id=(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
        $notice_id = $matches[1];
    }
}

// Debug: Log parameters
error_log("DEBUG - User ID: " . $user_id . ", Notice ID: " . ($notice_id ?? 'NULL'));
error_log("DEBUG - REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NULL'));
error_log("DEBUG - GET DATA: " . print_r($_GET, true));

if ($notice_id === null || $notice_id === '' || !is_numeric($notice_id)) {
    error_log("DEBUG: Invalid notice_id - " . ($notice_id ?? 'NULL'));
    $_SESSION['error'] = "Invalid vacating notice ID: " . htmlspecialchars($notice_id ?? 'NULL');
    header('Location: dashboard.php');
    exit;
}

// Get vacating notice details with verification that this notice exists
$sql = "SELECT vn.*, 
               ra.tenant_id, ra.start_date, ra.end_date, ra.monthly_rent,
               p.title as property_title, p.property_id, p.bedrooms, p.bathrooms, p.property_type, p.is_furnished,
               l.location_name, l.subcity,
               u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone,
               pi.image_url as property_image
        FROM vacating_notices vn
        LEFT JOIN rental_agreements ra ON vn.agreement_id = ra.agreement_id
        LEFT JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON vn.tenant_id = u.user_id
        LEFT JOIN property_images pi ON p.property_id = pi.property_id AND pi.is_primary = 1
        WHERE vn.notice_id = ?";

$stmt = $db->prepare($sql);
error_log("DEBUG: SQL prepared successfully");
error_log("DEBUG: Executing with notice_id=" . $notice_id);
$notice = $db->getSingle($stmt, [$notice_id]);

error_log("DEBUG: Query result - " . ($notice ? "Found notice" : "No notice found for ID $notice_id"));
if ($notice) {
    error_log("DEBUG: Notice data - " . print_r($notice, true));
    
    // Check if the owner actually owns this property
    $owner_check_sql = "SELECT p.owner_id 
                        FROM properties p 
                        JOIN rental_agreements ra ON p.property_id = ra.property_id 
                        JOIN vacating_notices vn ON ra.agreement_id = vn.agreement_id 
                        WHERE vn.notice_id = ?";
    $owner_check_stmt = $db->prepare($owner_check_sql);
    $owner_result = $db->getSingle($owner_check_stmt, [$notice_id]);
    
    if (!$owner_result || $owner_result['owner_id'] != $user_id) {
        error_log("DEBUG: Unauthorized access - Notice ID $notice_id does not belong to Owner ID $user_id. Property Owner: " . ($owner_result['owner_id'] ?? 'Unknown'));
        $_SESSION['error'] = "You do not have permission to view this notice.";
        header('Location: dashboard.php');
        exit;
    }
    
    // Check if the notice is actually linked to a property
    if (empty($notice['property_id'])) {
        error_log("DEBUG: Notice ID $notice_id is not correctly linked to a property.");
        $_SESSION['error'] = "Notice data is incomplete or corrupted.";
        header('Location: dashboard.php');
        exit;
    }
} else {
    // If notice not found, let's check if the ID actually exists in the table at all
    $check_exists_sql = "SELECT COUNT(*) as count FROM vacating_notices WHERE notice_id = ?";
    $exists_result = $db->getSingle($db->prepare($check_exists_sql), [$notice_id]);
    $actual_exists = $exists_result['count'] ?? 0;
    
    error_log("DEBUG: Redirecting to dashboard - notice not found in database for ID: " . $notice_id . ". Actual exists in table: " . $actual_exists);
    $_SESSION['error'] = "Vacating notice not found (ID: " . htmlspecialchars($notice_id) . "). " . ($actual_exists ? "Notice exists but joins failed." : "Notice ID does not exist.");
    header('Location: dashboard.php');
    exit;
}

// Mark notification as read if this was accessed from notification
if (isset($_GET['mark_notification_read']) && $_GET['mark_notification_read'] == '1') {
    $notification_id = $_GET['notification_id'] ?? null;
    if ($notification_id && is_numeric($notification_id)) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$notification_id, $user_id]);
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
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">Vacating Notice Details</h1>
                            <p class="text-muted mb-0">Review the tenant's vacating notice information</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-<?php echo $notice['status'] == 'completed' ? 'success' : ($notice['status'] == 'acknowledged' ? 'info' : 'warning'); ?> fs-6">
                                <?php echo ucfirst($notice['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-home me-2"></i>Property Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="<?php echo htmlspecialchars($notice['property_image'] ?: '../assets/images/default-property.svg'); ?>" 
                                 class="img-fluid rounded" alt="Property Image">
                        </div>
                        <div class="col-md-8">
                            <h4><?php echo htmlspecialchars($notice['property_title']); ?></h4>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($notice['location_name']); ?>, <?php echo htmlspecialchars($notice['subcity'] ?? ''); ?>
                            </p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Bedrooms:</strong> <?php echo (int)$notice['bedrooms']; ?></p>
                                    <p class="mb-1"><strong>Bathrooms:</strong> <?php echo (int)$notice['bathrooms']; ?></p>
                                    <p class="mb-1"><strong>Type:</strong> <?php echo htmlspecialchars($notice['property_type']); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p class="mb-1"><strong>Furnished:</strong> <?php echo $notice['is_furnished'] ? 'Yes' : 'No'; ?></p>
                                    <p class="mb-1"><strong>Monthly Rent:</strong> <?php echo number_format($notice['monthly_rent'], 2); ?> ETB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notice Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Notice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Vacating Date:</strong></p>
                            <h5 class="text-primary mb-3">
                                <?php echo date('F d, Y', strtotime($notice['vacating_date'])); ?>
                            </h5>
                            
                            <p class="mb-2"><strong>Notice Submitted:</strong></p>
                            <p class="text-muted"><?php echo date('F d, Y H:i', strtotime($notice['created_at'])); ?></p>
                            
                            <p class="mb-2"><strong>Last Updated:</strong></p>
                            <p class="text-muted"><?php echo date('F d, Y H:i', strtotime($notice['updated_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Rental Agreement Period:</strong></p>
                            <p class="text-muted">
                                <?php echo date('M d, Y', strtotime($notice['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($notice['end_date'])); ?>
                            </p>
                            
                            <p class="mb-2"><strong>Days Until Vacating:</strong></p>
                            <h5 class="<?php 
                                $days = (strtotime($notice['vacating_date']) - strtotime(date('Y-m-d'))) / 86400;
                                echo $days <= 7 ? 'text-danger' : ($days <= 30 ? 'text-warning' : 'text-success');
                            ?>">
                                <?php 
                                $days = (strtotime($notice['vacating_date']) - strtotime(date('Y-m-d'))) / 86400;
                                echo $days > 0 ? max(0, round($days)) . ' days' : 'Overdue';
                                ?>
                            </h5>
                        </div>
                    </div>
                    
                    <?php if (!empty($notice['reason'])): ?>
                    <div class="mt-3">
                        <p class="mb-2"><strong>Reason for Vacating:</strong></p>
                        <div class="p-3 bg-light rounded">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($notice['reason'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tenant Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Tenant Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Full Name:</strong></p>
                            <h6><?php echo htmlspecialchars($notice['tenant_name']); ?></h6>
                            
                            <p class="mb-2"><strong>Email:</strong></p>
                            <p class="text-muted"><?php echo htmlspecialchars($notice['tenant_email']); ?></p>
                            
                            <p class="mb-2"><strong>Phone:</strong></p>
                            <p class="text-muted"><?php echo htmlspecialchars($notice['tenant_phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($notice['forwarding_address'])): ?>
                            <p class="mb-2"><strong>Forwarding Address:</strong></p>
                            <div class="p-3 bg-light rounded">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($notice['forwarding_address'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($notice['contact_info'])): ?>
                            <p class="mb-2 mt-3"><strong>Post-Vacating Contact:</strong></p>
                            <div class="p-3 bg-light rounded">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($notice['contact_info'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <?php if ($notice['status'] == 'pending'): ?>
                            <button type="button" class="btn btn-success" onclick="updateNoticeStatus('acknowledged')">
                                <i class="fas fa-check me-2"></i>Acknowledge Notice
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($notice['status'] == 'acknowledged'): ?>
                            <button type="button" class="btn btn-primary" onclick="updateNoticeStatus('completed')">
                                <i class="fas fa-check-circle me-2"></i>Mark as Completed
                            </button>
                        <?php endif; ?>
                        
                        <a href="mailto:<?php echo htmlspecialchars($notice['tenant_email']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i>Contact Tenant
                        </a>
                        
                        <a href="tel:<?php echo htmlspecialchars($notice['tenant_phone']); ?>" class="btn btn-outline-success">
                            <i class="fas fa-phone me-2"></i>Call Tenant
                        </a>
                        
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Notice
                        </button>
                        
                        <a href="notifications.php" class="btn btn-outline-danger">
                            <i class="fas fa-arrow-left me-2"></i>Back to Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function updateNoticeStatus(newStatus) {
    const statusText = newStatus === 'acknowledged' ? 'acknowledge' : 'mark as completed';
    
    if (!confirm(`Are you sure you want to ${statusText} this vacating notice?`)) {
        return;
    }
    
    fetch('../api/update-vacating-notice-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notice_id=<?php echo $notice_id; ?>&status=' + encodeURIComponent(newStatus)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Vacating notice status updated successfully!');
            location.reload();
        } else {
            alert('Error updating notice status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating notice status');
    });
}
</script>
