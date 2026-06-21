<?php
require_once '../includes/config.php';
$title = "Notifications - Admin Dashboard";
$session->requireRole('admin');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nid = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
    if ($action === 'mark_read' && $nid > 0) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
        if ($db->execute($stmt, [$nid])) {
            $_SESSION['success'] = "Notification marked as read";
        } else {
            $_SESSION['error'] = "Failed to update notification";
        }
        header("Location: notifications.php");
        exit;
    } elseif ($action === 'mark_unread' && $nid > 0) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 0 WHERE notification_id = ?");
        if ($db->execute($stmt, [$nid])) {
            $_SESSION['success'] = "Notification marked as unread";
        } else {
            $_SESSION['error'] = "Failed to update notification";
        }
        header("Location: notifications.php");
        exit;
    } elseif ($action === 'delete' && $nid > 0) {
        $stmt = $db->prepare("DELETE FROM notifications WHERE notification_id = ?");
        if ($db->execute($stmt, [$nid])) {
            $_SESSION['success'] = "Notification deleted";
        } else {
            $_SESSION['error'] = "Failed to delete notification";
        }
        header("Location: notifications.php");
        exit;
    }
}

// Filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$role_filter = $_GET['role'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];
if ($status_filter === 'unread') {
    $where[] = "n.is_read = 0";
} elseif ($status_filter === 'read') {
    $where[] = "n.is_read = 1";
}
if (!empty($type_filter)) {
    $where[] = "n.type = ?";
    $params[] = $type_filter;
}
if (!empty($role_filter)) {
    $where[] = "u.role = ?";
    $params[] = $role_filter;
}
if (!empty($date_filter)) {
    $where[] = "DATE(n.created_at) = ?";
    $params[] = $date_filter;
}
if (!empty($search)) {
    $where[] = "(n.title LIKE ? OR n.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count
$count_sql = "SELECT COUNT(*) as total
              FROM notifications n
              LEFT JOIN users u ON n.user_id = u.user_id
              $where_clause";
$count_stmt = $db->prepare($count_sql);
$total = $db->getSingle($count_stmt, $params);
$total_notifications = $total ? (int)$total['total'] : 0;
$total_pages = max(1, ceil($total_notifications / $limit));

// List
$sql = "SELECT n.notification_id, n.title, n.message, n.type, n.is_read, n.link, n.created_at,
               u.full_name as user_name, u.email as user_email, u.role as user_role
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.user_id
        $where_clause
        ORDER BY n.created_at DESC
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$items = $db->getMultiple($stmt, $params);

// Stats
$stats_sql = "SELECT 
                 COUNT(*) as total,
                 COUNT(CASE WHEN is_read = 1 THEN 1 END) as read_count,
                 COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_count
              FROM notifications";
$stats = $db->getSingle($db->prepare($stats_sql)) ?: ['total' => 0, 'read_count' => 0, 'unread_count' => 0];

include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Notifications</h1>
                <div>
                    <a href="notification-create.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Create
                    </a>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p class="mb-0">Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['read_count']; ?></h3>
                            <p class="mb-0">Read</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['unread_count']; ?></h3>
                            <p class="mb-0">Unread</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                <option value="unread" <?php echo $status_filter==='unread'?'selected':''; ?>>Unread</option>
                                <option value="read" <?php echo $status_filter==='read'?'selected':''; ?>>Read</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="">All</option>
                                <option value="info" <?php echo $type_filter==='info'?'selected':''; ?>>Info</option>
                                <option value="success" <?php echo $type_filter==='success'?'selected':''; ?>>Success</option>
                                <option value="warning" <?php echo $type_filter==='warning'?'selected':''; ?>>Warning</option>
                                <option value="error" <?php echo $type_filter==='error'?'selected':''; ?>>Error</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="">All</option>
                                <option value="admin" <?php echo $role_filter==='admin'?'selected':''; ?>>Admin</option>
                                <option value="employee" <?php echo $role_filter==='employee'?'selected':''; ?>>Employee</option>
                                <option value="owner" <?php echo $role_filter==='owner'?'selected':''; ?>>Owner</option>
                                <option value="tenant" <?php echo $role_filter==='tenant'?'selected':''; ?>>Tenant</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" placeholder="Title or message..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Filter</button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <a href="notifications.php" class="btn btn-outline-secondary"><i class="fas fa-times me-2"></i>Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-bell me-2"></i>Notification Items</h5>
                    <a href="notification-analytics.php" class="btn btn-outline-info btn-sm"><i class="fas fa-chart-line me-1"></i>Analytics</a>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-bell fa-3x mb-3"></i>
                            <p>No notifications found</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Recipient</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($items as $n): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                            <div class="text-muted small"><?php echo htmlspecialchars($n['message']); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($n['user_name']); ?> <span class="badge bg-secondary ms-1"><?php echo ucfirst($n['user_role']); ?></span></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($n['user_email']); ?></small>
                                        </td>
                                        <td><span class="badge bg-<?php echo $n['type']==='success'?'success':($n['type']==='warning'?'warning':($n['type']==='error'?'danger':'info')); ?>"><?php echo ucfirst($n['type']); ?></span></td>
                                        <td><?php echo $n['is_read'] ? '<span class="badge bg-success">Read</span>' : '<span class="badge bg-warning">Unread</span>'; ?></td>
                                        <td><small class="text-muted"><?php echo formatDate($n['created_at'], 'M d, Y H:i'); ?></small></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="notification_id" value="<?php echo (int)$n['notification_id']; ?>">
                                                <input type="hidden" name="action" value="<?php echo $n['is_read'] ? 'mark_unread' : 'mark_read'; ?>">
                                                <button class="btn btn-outline-primary btn-sm"><i class="fas fa-check"></i> <?php echo $n['is_read'] ? 'Mark Unread' : 'Mark Read'; ?></button>
                                            </form>
                                            <form method="POST" class="d-inline ms-1" onsubmit="return confirm('Delete this notification?')">
                                                <input type="hidden" name="notification_id" value="<?php echo (int)$n['notification_id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                            <?php if (!empty($n['link'])): ?>
                                                <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-outline-info btn-sm ms-1" target="_blank"><i class="fas fa-external-link-alt"></i> Open</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Notifications pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a></li>
                                    <?php endif; ?>
                                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                        <li class="page-item <?php echo $i===$page?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                    <?php endfor; ?>
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
