<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Notifications - Aksum House Rental System";

$user_id = $session->getUserId();

// Get notifications with pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt, [$user_id]);
$total_notifications = $result['total'];
$total_pages = ceil($total_notifications / $limit);

// Get notifications
$sql = "SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$notifications = $db->getMultiple($stmt, [$user_id, $limit, $offset]);

// Get unread count
$sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt, [$user_id]);
$unread_count = $result['count'];

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Notifications Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">Notifications</h1>
                            <p class="text-muted mb-0">Stay updated with your rental activities</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-end">
                                <h5 class="text-warning mb-0"><?php echo $unread_count; ?></h5>
                                <small class="text-muted">Unread</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="markAllAsRead()">
                                <i class="fas fa-check-double me-2"></i>Mark All as Read
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteReadNotifications()">
                                <i class="fas fa-trash me-2"></i>Delete Read
                            </button>
                        </div>
                        <div>
                            <span class="text-muted">Showing <?php echo min($limit, $total_notifications); ?> of <?php echo $total_notifications; ?> notifications</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <?php if (empty($notifications)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Notifications</h5>
                        <p class="text-muted">You're all caught up! No new notifications to show.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item list-group-item-action <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?> border-0" 
                                 onclick="viewNotification(<?php echo $notification['notification_id']; ?>)">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <?php
                                        $type_class = 'info';
                                        switch (($notification['type'] ?? 'info')) {
                                            case 'success':
                                                $type_class = 'success';
                                                break;
                                            case 'warning':
                                                $type_class = 'warning';
                                                break;
                                            case 'error':
                                                $type_class = 'danger';
                                                break;
                                            case 'alert':
                                                $type_class = 'warning';
                                                break;
                                            case 'info':
                                            default:
                                                $type_class = 'info';
                                                break;
                                        }
                                        ?>
                                        <div class="avatar-sm bg-<?php echo $type_class; ?> bg-opacity-10 text-<?php echo $type_class; ?> rounded-circle d-flex align-items-center justify-content-center">
                                            <?php
                                            $icon = 'fa-bell';
                                            switch(($notification['type'] ?? 'info')) {
                                                case 'success': $icon = 'fa-check-circle'; break;
                                                case 'warning': $icon = 'fa-exclamation-triangle'; break;
                                                case 'error': $icon = 'fa-times-circle'; break;
                                                case 'info': $icon = 'fa-info-circle'; break;
                                                case 'alert': $icon = 'fa-bell'; break;
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <span class="badge bg-primary ms-2">New</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo formatDate($notification['created_at'], 'M d, Y H:i'); ?>
                                                </small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if (!$notification['is_read']): ?>
                                                            <li><a class="dropdown-item" href="#" onclick="markAsRead(<?php echo $notification['notification_id']; ?>); return false;">
                                                                <i class="fas fa-check me-2"></i>Mark as Read
                                                            </a></li>
                                                        <?php endif; ?>
                                                        <li><a class="dropdown-item" href="#" onclick="deleteNotification(<?php echo $notification['notification_id']; ?>); return false;">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Auto-refresh notifications every 2 minutes
    setInterval(function() {
        location.reload();
    }, 120000);
    console.log('Notifications script loaded');
});

function viewNotification(notificationId) {
    console.log('viewNotification', notificationId);

    // Mark as read if unread
    $.ajax({
        url: '../api/mark-notification-read.php',
        method: 'POST',
        data: { notification_id: notificationId },
        dataType: 'json',
        success: function(response) {
            // Reload page to update UI
            location.reload();
        }
    });
}

function markAsRead(notificationId) {
    console.log('markAsRead', notificationId);
    fetch('../api/mark-notification-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notification_id=' + encodeURIComponent(notificationId)
    })
    .then(response => response.json())
    .then(data => {
        console.log('markAsRead response', data);
        if (data.success) {
            location.reload();
        } else {
            alert('Error marking notification as read: ' + (data.message || 'unknown'));
        }
    })
    .catch(err => {
        console.error('markAsRead error', err);
        alert('Error marking notification as read: ' + err.message);
    });
}

function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;
    console.log('markAllAsRead');

    fetch('../api/mark-all-notifications-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        console.log('markAllAsRead response', data);
        if (data.success) {
            location.reload();
        } else {
            alert('Error marking notifications as read: ' + (data.message || 'unknown'));
        }
    })
    .catch(err => {
        console.error('markAllAsRead error', err);
        alert('Error marking notifications as read: ' + err.message);
    });
}

function deleteNotification(notificationId) {
    if (!confirm('Delete this notification?')) return;

    console.log('deleteNotification', notificationId);

    fetch('../api/delete-notification.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notification_id=' + encodeURIComponent(notificationId)
    })
    .then(response => response.json())
    .then(data => {
        console.log('deleteNotification response', data);
        if (data.success) {
            location.reload();
        } else {
            alert('Error deleting notification: ' + (data.message || 'unknown'));
        }
    })
    .catch(err => {
        console.error('deleteNotification error', err);
        alert('Error deleting notification: ' + err.message);
    });
}

function deleteReadNotifications() {
    console.log('deleteReadNotifications');
    if (!confirm('Delete all read notifications?')) return;
    $.ajax({
        url: '../api/delete-read-notifications.php',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            console.log('deleteReadNotifications response', response);
            if (response.success) {
                location.reload();
            } else {
                alert('Error deleting notifications: ' + (response.message || 'unknown'));
            }
        },
        error: function(xhr, status, error) {
            console.error('deleteReadNotifications error', status, error, xhr.responseText);
            alert('Error deleting notifications: ' + error);
        }
    });
}
</script>
