<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = 'Notifications - Aksum Rental System';

$user_id = $session->getUserId();

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get both regular notifications and property review notifications
$all_notifications = [];
$total_notifications = 0;
$unread_count = 0;

// Get regular notifications
try {
    $sql = "SELECT *, 'regular' as notification_source FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $regular_notifications = $db->getMultiple($stmt, [$user_id]);
    $all_notifications = array_merge($all_notifications, $regular_notifications);
} catch (Exception $e) {
    $regular_notifications = [];
}

// Get property review notifications
try {
    $sql = "SELECT *, 'property_review' as notification_source FROM property_review_notifications WHERE owner_id = ? ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $property_notifications = $db->getMultiple($stmt, [$user_id]);
    $all_notifications = array_merge($all_notifications, $property_notifications);
} catch (Exception $e) {
    $property_notifications = [];
}

// Sort all notifications by created_at
usort($all_notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get total count
$total_notifications = count($all_notifications);

// Count unread notifications (regular notifications + unread property review notifications)
$unread_regular = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $result = $db->getSingle($db->prepare($sql), [$user_id]);
    $unread_regular = (int)($result['count'] ?? 0);
} catch (Exception $e) {
    $unread_regular = 0;
}

$unread_property_reviews = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM property_review_notifications WHERE owner_id = ? AND is_read = 0";
    $result = $db->getSingle($db->prepare($sql), [$user_id]);
    $unread_property_reviews = (int)($result['count'] ?? 0);
} catch (Exception $e) {
    $unread_property_reviews = 0;
}

$unread_count = $unread_regular + $unread_property_reviews;

// Paginate the sorted notifications
$notifications = array_slice($all_notifications, $offset, $limit);
$total_pages = (int)ceil($total_notifications / $limit);

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
                            <h1 class="h3 mb-0">Notifications</h1>
                            <p class="text-muted mb-0">Updates related to your properties and rentals</p>
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

            <?php if (empty($notifications)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Notifications</h5>
                        <p class="text-muted">You're all caught up!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item list-group-item-action <?php echo (!$notification['is_read'] && $notification['notification_source'] === 'regular') || ($notification['is_read'] == 0 && $notification['notification_source'] === 'property_review') ? 'bg-light' : ''; ?> border-0" onclick="viewNotification(<?php echo (int)$notification['notification_id']; ?>, '<?php echo $notification['notification_source']; ?>')">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <?php
                                        // Handle different notification sources
                                        if ($notification['notification_source'] === 'property_review') {
                                            // Property review notification
                                            $type_class = 'info';
                                            switch (($notification['notification_type'] ?? 'info')) {
                                                case 'approved':
                                                    $type_class = 'success';
                                                    break;
                                                case 'rejected':
                                                    $type_class = 'danger';
                                                    break;
                                                case 'needs_revision':
                                                    $type_class = 'warning';
                                                    break;
                                                default:
                                                    $type_class = 'info';
                                                    break;
                                            }
                                            
                                            $icon = 'fa-home';
                                            switch(($notification['notification_type'] ?? 'info')) {
                                                case 'approved': $icon = 'fa-check-circle'; break;
                                                case 'rejected': $icon = 'fa-times-circle'; break;
                                                case 'needs_revision': $icon = 'fa-edit'; break;
                                                default: $icon = 'fa-info-circle'; break;
                                            }
                                        } else {
                                            // Regular notification
                                            $type_class = 'info';
                                            $icon = 'fa-bell';
                                            
                                            // Special handling for vacating notice notifications
                                            if (strpos($notification['title'], 'Vacating Notice') !== false) {
                                                $type_class = 'warning';
                                                $icon = 'fa-home';
                                            }
                                            
                                            switch (($notification['type'] ?? 'info')) {
                                                case 'success':
                                                    $type_class = 'success';
                                                    $icon = 'fa-check-circle';
                                                    break;
                                                case 'warning':
                                                    $type_class = 'warning';
                                                    $icon = 'fa-exclamation-triangle';
                                                    break;
                                                case 'error':
                                                    $type_class = 'danger';
                                                    $icon = 'fa-times-circle';
                                                    break;
                                                case 'alert':
                                                    $type_class = 'warning';
                                                    $icon = 'fa-bell';
                                                    break;
                                                case 'info':
                                                default:
                                                    $type_class = 'info';
                                                    $icon = 'fa-info-circle';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <div class="avatar-sm bg-<?php echo $type_class; ?> bg-opacity-10 text-<?php echo $type_class; ?> rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 <?php echo (!$notification['is_read'] && $notification['notification_source'] === 'regular') || ($notification['is_read'] == 0 && $notification['notification_source'] === 'property_review') ? 'fw-bold' : ''; ?>">
                                                    <?php 
                                                    if ($notification['notification_source'] === 'property_review') {
                                                        echo '<strong>Property Review Update</strong>';
                                                        echo ' - ' . ucfirst(str_replace('_', ' ', $notification['notification_type']));
                                                    } else {
                                                        echo htmlspecialchars($notification['title']);
                                                    }
                                                    
                                                    // Show unread badge
                                                    $is_unread = ($notification['notification_source'] === 'regular' && !$notification['is_read']) || 
                                                               ($notification['notification_source'] === 'property_review' && $notification['is_read'] == 0);
                                                    if ($is_unread): ?>
                                                        <span class="badge bg-primary ms-2">New</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo formatDate($notification['created_at'], 'M d, Y H:i'); ?>
                                                    <?php if ($notification['notification_source'] === 'property_review'): ?>
                                                        <span class="badge bg-secondary ms-2">Property Review</span>
                                                    <?php endif; ?>
                                                </small>
                                                <?php if (!empty($notification['link']) && $notification['notification_source'] === 'regular'): ?>
                                                    <div class="mt-2">
                                                        <?php
                                                        $link = $notification['link'];
                                                        // Normalize relative owner links to avoid owner/owner paths
                                                        if (!empty($link) && preg_match('#^(?:owner/|[^./]|\.\.)#', $link)) {
                                                            if (strpos($link, 'owner/') === 0) {
                                                                $link = '../' . ltrim($link, '/');
                                                            } elseif (!preg_match('#^(?:https?://|/|\.\./)#', $link)) {
                                                                // If the file exists in current directory, don't prepend ../
                                                                $pure_link = explode('?', $link)[0];
                                                                if (!file_exists($pure_link)) {
                                                                    $link = '../' . ltrim($link, '/');
                                                                }
                                                            }
                                                        }
                                                        // For vacating notice details, add notification_id to mark as read
                                                        if (strpos($link, 'vacating-notice-details.php') !== false) {
                                                            $link .= (strpos($link, '?') === false ? '?' : '&') . 'notification_id=' . (int)$notification['notification_id'] . '&mark_notification_read=1';
                                                        }
                                                        ?>
                                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($link); ?>" onclick="event.stopPropagation();">
                                                            View Details
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <div class="dropdown" onclick="event.stopPropagation();">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php 
                                                        $is_unread = ($notification['notification_source'] === 'regular' && !$notification['is_read']) || 
                                                                   ($notification['notification_source'] === 'property_review' && $notification['is_read'] == 0);
                                                        if ($is_unread): ?>
                                                            <li><a class="dropdown-item" href="#" onclick="markAsRead(<?php echo (int)$notification['notification_id']; ?>, '<?php echo $notification['notification_source']; ?>'); return false;">
                                                                <i class="fas fa-check me-2"></i>Mark as Read
                                                            </a></li>
                                                        <?php endif; ?>
                                                        <li><a class="dropdown-item" href="#" onclick="deleteNotification(<?php echo (int)$notification['notification_id']; ?>, '<?php echo $notification['notification_source']; ?>'); return false;">
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

                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span></li>
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
function viewNotification(notificationId, source) {
    fetch('../api/mark-notification-read-api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notification_id=' + encodeURIComponent(notificationId)
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload();
          } else {
              alert('Unable to mark notification as read: ' + (data.message || 'Unknown error'));
          }
      })
      .catch(() => {
          alert('Unable to mark notification as read. Please try again.');
      });
}

function markAsRead(notificationId, source) {
    fetch('../api/mark-notification-read-api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notification_id=' + encodeURIComponent(notificationId)
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload();
          } else {
              alert('Unable to mark notification as read: ' + (data.message || 'Unknown error'));
          }
      })
      .catch(() => {
          alert('Unable to mark notification as read. Please try again.');
      });
}

function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;

    fetch('../api/mark-all-notifications-read.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to mark all notifications as read');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error marking all notifications as read: ' + err.message);
        });
}

function deleteNotification(notificationId, source) {
    if (!confirm('Delete this notification?')) return;

    fetch('../api/delete-notification.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notification_id=' + encodeURIComponent(notificationId) + '&source=' + encodeURIComponent(source)
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload();
          } else {
              throw new Error(data.message || 'Failed to delete notification');
          }
      })
      .catch(err => {
          console.error(err);
          alert('Error deleting notification: ' + err.message);
      });
}

function deleteReadNotifications() {
    if (!confirm('Delete all read notifications?')) return;

    fetch('../api/delete-read-notifications.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to delete read notifications');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error deleting read notifications: ' + err.message);
        });
}
</script>
