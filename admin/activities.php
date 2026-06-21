<?php
require_once '../includes/config.php';
$title = "System Activities - Admin Dashboard";

// Require admin login
$session->requireRole('admin');

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get filter parameters
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($action_filter)) {
    $where_conditions[] = "al.action LIKE ?";
    $params[] = "%$action_filter%";
}

if (!empty($user_filter)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$user_filter%";
    $params[] = "%$user_filter%";
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(al.created_at) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM audit_log al 
              LEFT JOIN users u ON al.user_id = u.user_id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$total_result = $db->getSingle($stmt, $params);
$total_activities = $total_result['total'];
$total_pages = ceil($total_activities / $limit);

// Get activities with pagination
$sql = "SELECT al.*, u.full_name, u.email, u.role as user_role 
        FROM audit_log al 
        LEFT JOIN users u ON al.user_id = u.user_id 
        $where_clause
        ORDER BY al.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$activities = $db->getMultiple($stmt, $params);

// Get unique actions for filter
$actions_sql = "SELECT DISTINCT action FROM audit_log ORDER BY action";
$stmt = $db->prepare($actions_sql);
$available_actions = $db->getMultiple($stmt);

include '../includes/header.php';
?>

<!-- System Activities Content -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">System Activities</h1>
                <div>
                    <span class="badge bg-info">Activity Log</span>
                    <span class="text-muted">Total: <?php echo $total_activities; ?> activities</span>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Activities
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="action" class="form-label">Action</label>
                            <select class="form-select" id="action" name="action">
                                <option value="">All Actions</option>
                                <?php foreach ($available_actions as $action): ?>
                                    <option value="<?php echo htmlspecialchars($action['action']); ?>" 
                                            <?php echo $action_filter === $action['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action['action']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="user" class="form-label">User</label>
                            <input type="text" class="form-control" id="user" name="user" 
                                   placeholder="Name or email" value="<?php echo htmlspecialchars($user_filter); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php if (!empty($action_filter) || !empty($user_filter) || !empty($date_filter)): ?>
                        <div class="mt-2">
                            <a href="activities.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Activities Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Activity Log
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-success" onclick="exportActivities()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="clearOldActivities()">
                            <i class="fas fa-trash me-1"></i>Clear Old
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No activities found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later for new activities.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-clock me-1"></i>Time</th>
                                        <th><i class="fas fa-user me-1"></i>User</th>
                                        <th><i class="fas fa-cog me-1"></i>Action</th>
                                        <th><i class="fas fa-info-circle me-1"></i>Details</th>
                                        <th><i class="fas fa-globe me-1"></i>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo formatDate($activity['created_at'], 'M d, Y H:i:s'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($activity['full_name']): ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($activity['email']); ?>
                                                            <span class="badge bg-secondary ms-1"><?php echo ucfirst($activity['user_role'] ?? 'Unknown'); ?></span>
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?> me-1"></i>
                                                    <?php echo htmlspecialchars($activity['action']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php if (!empty($activity['details'])): ?>
                                                        <?php echo htmlspecialchars(substr($activity['details'], 0, 100)); ?>
                                                        <?php if (strlen($activity['details']) > 100): ?>
                                                            <a href="javascript:void(0)" onclick="showDetails(<?php echo $activity['log_id']; ?>)">...more</a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        No details
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $activity['ip_address'] ?? 'N/A'; ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Activities pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($action_filter) ? '&action=' . urlencode($action_filter) : ''; ?><?php echo !empty($user_filter) ? '&user=' . urlencode($user_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($action_filter) ? '&action=' . urlencode($action_filter) : ''; ?><?php echo !empty($user_filter) ? '&user=' . urlencode($user_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($action_filter) ? '&action=' . urlencode($action_filter) : ''; ?><?php echo !empty($user_filter) ? '&user=' . urlencode($user_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">
                                                Next
                                            </a>
                                        </li>
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

<!-- Activity Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="activityDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Helper function for activity icons
function getActivityIcon($action) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'register' => 'user-plus',
        'create' => 'plus',
        'update' => 'edit',
        'delete' => 'trash',
        'payment' => 'credit-card',
        'request' => 'paper-plane',
        'approval' => 'check',
        'rejection' => 'times',
        'maintenance' => 'tools',
        'feedback' => 'comment',
        'agreement' => 'file-contract',
        'property' => 'home',
        'user' => 'user'
    ];
    
    foreach ($icons as $key => $icon) {
        if (stripos($action, $key) !== false) {
            return $icon;
        }
    }
    
    return 'circle';
}
?>

<script>
function showDetails(logId) {
    fetch('../api/activity-details.php?log_id=' + logId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const details = data.activity;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Action:</strong> ${details.action}<br>
                            <strong>User:</strong> ${details.full_name || 'System'}<br>
                            <strong>Role:</strong> ${details.user_role || 'N/A'}<br>
                            <strong>Email:</strong> ${details.email || 'N/A'}
                        </div>
                        <div class="col-md-6">
                            <strong>Time:</strong> ${new Date(details.created_at).toLocaleString()}<br>
                            <strong>IP Address:</strong> ${details.ip_address || 'N/A'}<br>
                            <strong>User Agent:</strong> ${details.user_agent || 'N/A'}
                        </div>
                    </div>
                    <hr>
                    <strong>Details:</strong><br>
                    <pre class="bg-light p-3 rounded">${details.details || 'No details available'}</pre>
                `;
                document.getElementById('activityDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            } else {
                alert('Error loading activity details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading activity details');
        });
}

function exportActivities() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('activities.php?' + params.toString(), '_blank');
}

function clearOldActivities() {
    if (confirm('Are you sure you want to delete activities older than 30 days? This action cannot be undone.')) {
        fetch('../api/clear-activities.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                days: 30
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Old activities cleared successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clearing activities.');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
