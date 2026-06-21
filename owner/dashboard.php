<?php

require_once '../includes/config.php';

require_once '../includes/wallet-functions.php';

$session->requireRole('owner');

$title = "Property Owner Dashboard";



$owner_id = $session->getUserId();



// Get statistics

$stats = [];



// Total properties

$sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ?";

$stmt = $db->prepare($sql);

$result = $db->getSingle($stmt, [$owner_id]);

$stats['total_properties'] = $result ? $result['count'] : 0;



// Available properties (only approved and available)

$sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ? AND status = 'available' AND review_status = 'approved'";

$stmt = $db->prepare($sql);

$result = $db->getSingle($stmt, [$owner_id]);

$stats['available_properties'] = $result ? $result['count'] : 0;



// Rejected properties

$sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ? AND review_status = 'rejected'";

$stmt = $db->prepare($sql);

$result = $db->getSingle($stmt, [$owner_id]);

$stats['rejected_properties'] = $result ? $result['count'] : 0;



// Pending requests

$sql = "SELECT COUNT(*) as count FROM rental_requests r 

        JOIN properties p ON r.property_id = p.property_id 

        WHERE p.owner_id = ? AND r.status = 'pending'";

$stmt = $db->prepare($sql);

$result = $db->getSingle($stmt, [$owner_id]);

$stats['pending_requests'] = $result ? $result['count'] : 0;



// Active rentals

$sql = "SELECT COUNT(*) as count FROM rental_agreements a 

        JOIN properties p ON a.property_id = p.property_id 

        WHERE p.owner_id = ? AND a.status = 'active'";

$stmt = $db->prepare($sql);

$result = $db->getSingle($stmt, [$owner_id]);

$stats['active_rentals'] = $result ? $result['count'] : 0;



// Monthly income

$current_month = date('Y-m');

$sql = "SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p 

        JOIN rental_agreements a ON p.agreement_id = a.agreement_id 

        JOIN properties pr ON a.property_id = pr.property_id 

        WHERE pr.owner_id = ? AND p.month_year = ? AND p.status = 'completed' 

        AND p.payment_for IN ('rent', 'advance')";

$stmt = $db->prepare($sql);

$result = $db->getSingle($stmt, [$owner_id, $current_month]);

$stats['monthly_income'] = $result ? ($result['total'] ?? 0) : 0;



// Inbox statistics

// Unread notifications (regular + property review)
$unread_regular = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$owner_id]);
    $unread_regular = $result ? $result['count'] : 0;
} catch (Exception $e) {
    $unread_regular = 0;
}

$unread_property_reviews = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM property_review_notifications WHERE owner_id = ? AND is_read = 0";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$owner_id]);
    $unread_property_reviews = $result ? $result['count'] : 0;
} catch (Exception $e) {
    $unread_property_reviews = 0;
}

$stats['unread_notifications'] = $unread_regular + $unread_property_reviews;



// Pending feedback
try {
$sql = "SELECT COUNT(*) as count FROM feedback f 

        JOIN properties p ON f.property_id = p.property_id 

        WHERE p.owner_id = ? AND f.status = 'pending'";

$stmt = $db->prepare($sql);

$result = $db->getSingle($stmt, [$owner_id]);

$stats['pending_feedback'] = $result ? $result['count'] : 0;
} catch (Exception $e) {
    $stats['pending_feedback'] = 0;
}

// Wallet Balance
$stats['wallet_balance'] = getWalletBalance($owner_id);

// Recent notifications for inbox
$sql = "SELECT notification_id, title, message, type, is_read, created_at, link
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$recent_notifications = $db->getMultiple($stmt, [$owner_id]);

// Also get property review notifications
$property_review_notifications = [];
try {
    $sql = "SELECT notification_id, message, notification_type, is_read, created_at 
            FROM property_review_notifications 
            WHERE owner_id = ? 
            ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $property_notifications = $db->getMultiple($stmt, [$owner_id]);
        // Add notification_source to distinguish from regular notifications
        foreach ($property_notifications as &$notification) {
            $notification['notification_source'] = 'property_review';
            $notification['title'] = 'Property Review Update - ' . ucfirst(str_replace('_', ' ', $notification['notification_type']));
        }
        $property_review_notifications = $property_notifications;
    }
} catch (Exception $e) {
    // Table might not exist, continue with empty array
    $property_review_notifications = [];
}

// Combine both types of notifications

$all_notifications = array_merge($recent_notifications, $property_review_notifications);

// Sort by created_at

usort($all_notifications, function($a, $b) {

    return strtotime($b['created_at']) - strtotime($a['created_at']);

});

// Take only the 5 most recent

$recent_notifications = array_slice($all_notifications, 0, 5);



// Recent feedback for inbox - simplified

$recent_feedback = [];

try {
    $sql = "SELECT f.*, p.title as property_title 
            FROM feedback f
            JOIN properties p ON f.property_id = p.property_id
            WHERE p.owner_id = ? 
            ORDER BY f.created_at DESC LIMIT 5";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $feedback = $db->getMultiple($stmt, [$owner_id]);
        foreach ($feedback as $item) {
            $item['user_name'] = 'User';
            $recent_feedback[] = $item;
        }
    }
} catch (Exception $e) {
    $recent_feedback = [];
}

// Recent properties - simplified
$properties = [];
try {
    $sql = "SELECT p.*, p.review_status as review_status, 'Addis Ababa' as location_name 
            FROM properties p 
            WHERE p.owner_id = ? 
            ORDER BY p.created_at DESC LIMIT 5";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $properties = $db->getMultiple($stmt, [$owner_id]);
    }
} catch (Exception $e) {
    $properties = [];
}


// Recent requests - simplified
$requests = [];
try {
    $sql = "SELECT r.*, p.title as property_title 
            FROM rental_requests r 
            JOIN properties p ON r.property_id = p.property_id 
            WHERE p.owner_id = ? AND r.status = 'pending' 
            ORDER BY r.request_date DESC LIMIT 5";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $request_data = $db->getMultiple($stmt, [$owner_id]);
        foreach ($request_data as $item) {
            $item['tenant_name'] = 'Tenant';
            $requests[] = $item;
        }
    }
} catch (Exception $e) {
    $requests = [];
}



include '../includes/header.php';

?>



<div class="container-fluid py-4">

    <div class="row">

        <div class="col-lg-3">

            <?php include '../includes/sidebar.php'; ?>

        </div>

        

        <div class="col-lg-9">

            <!-- Welcome Banner -->

            <div class="card dashboard-card mb-4">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h1 class="h3 mb-2">Property Owner Dashboard</h1>

                            <p class="text-muted mb-0">Manage your properties and rental activities</p>

                        </div>

                        <div class="col-md-4 text-end">

                            <a href="add-property.php" class="btn btn-primary btn-lg">

                                <i class="fas fa-plus me-2"></i>Add New Property

                            </a>

                        </div>

                    </div>

                </div>

            </div>

            

            <!-- Statistics Cards -->

            <div class="row mb-4">

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">

                                <i class="fas fa-wallet fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold">ETB <?php echo number_format(max(0, $stats['wallet_balance']), 2); ?></h3>

                            <p class="text-muted mb-0">Wallet Balance</p>

                        </div>

                    </div>

                </div>

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-primary bg-opacity-10 text-primary mx-auto">

                                <i class="fas fa-home fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold"><?php echo $stats['total_properties']; ?></h3>

                            <p class="text-muted mb-0">Total Properties</p>

                        </div>

                    </div>

                </div>

                

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">

                                <i class="fas fa-check-circle fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold"><?php echo $stats['available_properties']; ?></h3>

                            <p class="text-muted mb-0">Available</p>

                        </div>

                    </div>

                </div>

                

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-danger bg-opacity-10 text-danger mx-auto">

                                <i class="fas fa-times-circle fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold"><?php echo $stats['rejected_properties']; ?></h3>

                            <p class="text-muted mb-0">Rejected</p>

                        </div>

                    </div>

                </div>

                

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">

                                <i class="fas fa-clock fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold"><?php echo $stats['pending_requests']; ?></h3>

                            <p class="text-muted mb-0">Pending Requests</p>

                        </div>

                    </div>

                </div>

                

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">

                                <i class="fas fa-chart-line fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold">ETB <?php echo number_format($stats['monthly_income'], 0); ?></h3>

                            <p class="text-muted mb-0">Monthly Income</p>

                        </div>

                    </div>

                </div>

            </div>

            

            <div class="row">

                <!-- Recent Properties -->

                <div class="col-lg-6 mb-4">

                    <div class="card dashboard-card h-100">

                        <div class="card-header d-flex justify-content-between align-items-center">

                            <h5 class="mb-0">My Properties</h5>

                            <a href="properties.php" class="btn btn-sm btn-outline-primary">View All</a>

                        </div>

                        <div class="card-body p-0">

                            <div class="table-responsive">

                                <table class="table table-hover mb-0">

                                    <thead>

                                        <tr>

                                            <th>Property</th>

                                            <th>Status</th>

                                            <th>Rent</th>

                                            <th>Action</th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php if (empty($properties)): ?>

                                            <tr>

                                                <td colspan="4" class="text-center py-4 text-muted">

                                                    No properties listed yet

                                                </td>

                                            </tr>

                                        <?php else: ?>

                                            <?php foreach ($properties as $property): ?>

                                                <tr>

                                                    <td>

                                                        <strong><?php echo htmlspecialchars($property['title']); ?></strong>

                                                        <div class="small text-muted">

                                                            <?php echo htmlspecialchars($property['location_name']); ?>

                                                        </div>

                                                    </td>

                                                    <td>

                                                        <?php 
                                                        $review_status = $property['review_status'] ?? 'pending';
                                                        $property_status = $property['status'] ?? 'pending';
                                                        
                                                        // Show appropriate status based on review status
                                                        if ($review_status === 'pending') {
                                                            $status_text = 'Waiting for employee approval';
                                                            $status_class = 'warning';
                                                        } elseif ($review_status === 'approved') {
                                                            $status_text = ucfirst($property_status);
                                                            $status_class = $property_status === 'available' ? 'success' : 
                                                                         ($property_status === 'rented' ? 'info' : 'secondary');
                                                        } elseif ($review_status === 'rejected') {
                                                            $status_text = 'Rejected';
                                                            $status_class = 'danger';
                                                        } elseif ($review_status === 'needs_revision') {
                                                            $status_text = 'Needs revision';
                                                            $status_class = 'warning';
                                                        } else {
                                                            $status_text = ucfirst($property_status);
                                                            $status_class = 'secondary';
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?php echo $status_class; ?>">
                                                            <?php echo $status_text; ?>
                                                        </span>

                                                    </td>

                                                    <td>

                                                        <strong>ETB <?php echo number_format($property['monthly_rent'], 0); ?></strong>

                                                    </td>

                                                    <td>

                                                        <a href="edit-property.php?id=<?php echo $property['property_id']; ?>" 

                                                           class="btn btn-sm btn-outline-primary">

                                                            <i class="fas fa-edit"></i>

                                                        </a>

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

                

                <!-- Pending Requests -->

                <div class="col-lg-6 mb-4">

                    <div class="card dashboard-card h-100">

                        <div class="card-header d-flex justify-content-between align-items-center">

                            <h5 class="mb-0">Pending Requests</h5>

                            <a href="requests.php" class="btn btn-sm btn-outline-primary">View All</a>

                        </div>

                        <div class="card-body p-0">

                            <div class="list-group list-group-flush">

                                <?php if (empty($requests)): ?>

                                    <div class="list-group-item text-center py-4 text-muted">

                                        No pending requests

                                    </div>

                                <?php else: ?>

                                    <?php foreach ($requests as $request): ?>

                                        <div class="list-group-item list-group-item-action">

                                            <div class="d-flex justify-content-between align-items-start">

                                                <div>

                                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['title']); ?></h6>

                                                    <p class="small text-muted mb-1">

                                                        Requested by: <?php echo htmlspecialchars($request['tenant_name']); ?>

                                                    </p>

                                                    <small class="text-muted">

                                                        <?php echo date('M d, Y', strtotime($request['request_date'])); ?>

                                                    </small>

                                                </div>

                                                <div class="btn-group">

                                                    <button class="btn btn-sm btn-success approve-btn" 

                                                            data-request-id="<?php echo $request['request_id']; ?>">

                                                        <i class="fas fa-check"></i>

                                                    </button>

                                                    <button class="btn btn-sm btn-danger reject-btn" 

                                                            data-request-id="<?php echo $request['request_id']; ?>">

                                                        <i class="fas fa-times"></i>

                                                    </button>

                                                </div>

                                            </div>

                                            <?php if ($request['message']): ?>

                                                <div class="mt-2 p-2 bg-light rounded">

                                                    <small class="text-muted"><?php echo htmlspecialchars($request['message']); ?></small>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            

            <!-- Notifications Section -->
            <div class="col-lg-6 mb-4">
                <div class="card dashboard-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bell me-2"></i>
                            Notifications
                            <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_notifications)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-2"></i>
                                <p>No notifications yet</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_notifications as $notification): 
                                    // Debug: Log notification data
                                    error_log("DASHBOARD DEBUG: Notification data - " . print_r($notification, true));
                                ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php 
                                                    // Show different icons for different notification types
                                                    $icon_class = 'fas fa-info-circle';
                                                    $badge_class = 'bg-info';
                                                    
                                                    if (isset($notification['notification_type'])) {
                                                        switch ($notification['notification_type']) {
                                                            case 'approved':
                                                                $icon_class = 'fas fa-check-circle text-success';
                                                                $badge_class = 'bg-success';
                                                                break;
                                                            case 'rejected':
                                                                $icon_class = 'fas fa-times-circle text-danger';
                                                                $badge_class = 'bg-danger';
                                                                break;
                                                            case 'needs_revision':
                                                                $icon_class = 'fas fa-edit text-warning';
                                                                $badge_class = 'bg-warning';
                                                                break;
                                                            case 'file_transfer':
                                                                $icon_class = 'fas fa-file-upload text-primary';
                                                                $badge_class = 'bg-primary';
                                                                break;
                                                            default:
                                                                $icon_class = 'fas fa-info-circle text-info';
                                                                $badge_class = 'bg-info';
                                                        }
                                                    } else {
                                                        $icon_class = 'fas fa-info-circle text-info';
                                                        $badge_class = 'bg-info';
                                                    }
                                                    ?>
                                                    <i class="<?php echo $icon_class; ?> me-2"></i>
                                                    <?php 
                                                    // Show unread badge for property review notifications
                                                    $unread_badge = (isset($notification['notification_type']) && $notification['is_read'] == 0 && $notification['notification_type'] !== 'info') ? 
                                                        '<span class="badge bg-danger ms-2">New</span>' : ''; 
                                                    ?>
                                                </h6>
                                                <p class="mb-1">
                                                    <?php 
                                                    // Property review notifications use 'message' column, regular notifications use 'title'
                                                    if (isset($notification['notification_type'])) {
                                                        // This is a property review notification
                                                        echo '<strong>Property Review Update</strong>';
                                                    } else {
                                                        // This is a regular notification
                                                        echo '<strong>' . htmlspecialchars($notification['title']) . '</strong>';
                                                    }
                                                    
                                                    if (isset($notification['notification_type']) && $notification['notification_type'] === 'file_transfer'): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-paperclip me-1"></i>
                                                            File Transfer Request
                                                        </small>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mb-1 small">
                                                    <?php 
                                                    // Show the message content
                                                    echo htmlspecialchars($notification['message'] ?? $notification['title'] ?? 'No message');
                                                    ?>
                                                </p>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                                    <?php echo $unread_badge; ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <?php 
                                                // Debug: Check link and notification type
                                                error_log("DASHBOARD DEBUG: Link check - link='" . ($notification['link'] ?? 'empty') . "', notification_type=" . (isset($notification['notification_type']) ? 'set' : 'not set'));
                                                ?>
                                                <?php if (!empty($notification['link']) && !isset($notification['notification_type'])): 
                                                    $link = $notification['link'];
                                                    if (strpos($link, 'vacating-notice-details.php') !== false) {
                                                        $sep = strpos($link, '?') === false ? '?' : '&';
                                                        $link .= $sep . 'notification_id=' . (int)$notification['notification_id'] . '&mark_notification_read=1';
                                                    }
                                                    error_log("DASHBOARD DEBUG: Final link - " . $link);
                                                ?>
                                                <a href="<?php echo htmlspecialchars($link); ?>" class="btn btn-sm btn-outline-primary me-2">
                                                    View Details
                                                </a>
                                                <?php else: ?>
                                                    <?php error_log("DASHBOARD DEBUG: No link button shown - link empty or notification_type set"); ?>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-secondary mark-read-btn" 
                                                        data-notification-id="<?php echo $notification['notification_id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                    Mark as Read
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->

            <div class="row">

                <div class="col-12">

                    <div class="card dashboard-card">

                        <div class="card-header">

                            <h5 class="mb-0">Quick Actions</h5>

                        </div>

                        <div class="card-body">

                            <div class="row g-3">

                                <div class="col-md-3">

                                    <a href="add-property.php" class="btn btn-outline-primary w-100 py-3">

                                        <i class="fas fa-plus fa-2x mb-2"></i><br>

                                        Add Property

                                    </a>

                                </div>

                                <div class="col-md-3">

                                    <a href="properties.php" class="btn btn-outline-success w-100 py-3">

                                        <i class="fas fa-home fa-2x mb-2"></i><br>

                                        My Properties

                                    </a>

                                </div>

                                <div class="col-md-3">

                                    <a href="requests.php" class="btn btn-outline-warning w-100 py-3">

                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>

                                        Manage Requests

                                    </a>

                                </div>

                                <div class="col-md-3">

                                    <a href="payments.php" class="btn btn-outline-info w-100 py-3">

                                        <i class="fas fa-credit-card fa-2x mb-2"></i><br>

                                        View Payments

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<?php include '../includes/footer.php'; ?>



<script>

$(document).ready(function() {

    // Approve request

    $('.approve-btn').click(function() {

        const requestId = $(this).data('request-id');

        if (confirm('Approve this rental request?')) {

            $.ajax({

                url: '../api/process-request.php',

                method: 'POST',

                data: { request_id: requestId, action: 'approve' },

                dataType: 'json',

                success: function(response) {

                    if (response.success) {

                        alert('Request approved successfully!');

                        location.reload();

                    } else {

                        alert('Error: ' + response.message);

                    }

                }

            });

        }

    });

    

    // Reject request

    $('.reject-btn').click(function() {

        const requestId = $(this).data('request-id');

        if (confirm('Reject this rental request?')) {

            $.ajax({

                url: '../api/process-request.php',

                method: 'POST',

                data: { request_id: requestId, action: 'reject' },

                dataType: 'json',

                success: function(response) {

                    if (response.success) {

                        alert('Request rejected.');

                        location.reload();

                    } else {

                        alert('Error: ' + response.message);

                    }

                }

            });

});

// Mark notification as read
$('.mark-read-btn').click(function() {
const notificationId = $(this).data('notification-id');
const notificationItem = $(this).closest('.list-group-item');
const isPropertyReview = notificationItem.find('.badge.bg-secondary').text().includes('Property Review');
        
// Determine the correct API endpoint based on notification type
const apiUrl = '../api/mark-notification-read-api.php';
        
$.ajax({
url: apiUrl,
method: 'POST',
data: { notification_id: notificationId },
dataType: 'json',
success: function(response) {
if (response.success) {
// Remove unread badge and update UI
notificationItem.find('.badge.bg-danger').remove();
notificationItem.find('.mark-read-btn').remove();
                    
// Update the background color
notificationItem.removeClass('bg-light');
                    
// Show success message
const successAlert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
'<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
'<strong>Success!</strong> Notification marked as read.' +
'</div>');
notificationItem.after(successAlert);
                    
// Auto-hide success message after 3 seconds
setTimeout(function() {
successAlert.fadeOut(300, function() {
successAlert.remove();
});
}, 3000);
} else {
alert('Error: ' + response.message);
}
},
error: function() {
alert('An error occurred. Please try again.');
}
});
});
});
</script>
