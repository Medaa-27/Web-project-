<?php
require_once '../includes/config.php';

// Require admin login for all operations
$session->requireRole('admin');

// Set JSON response header
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_user':
            getUser();
            break;
        case 'get_user_details':
            getUserDetails();
            break;
        case 'get_user_activity':
            getUserActivity();
            break;
        case 'get_statistics':
            getUserStatistics();
            break;
        case 'export_csv':
            exportUsersCSV();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function getUser() {
    global $db;

    $userId = (int)($_GET['user_id'] ?? 0);
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    $sql = "SELECT user_id, full_name, email, phone, role, status, profile_image,
                   created_at, last_login, updated_at
            FROM users WHERE user_id = ?";

    $stmt = $db->prepare($sql);
    $user = $db->getSingle($stmt, [$userId]);

    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

function getUserDetails() {
    global $db;

    $userId = (int)($_GET['user_id'] ?? 0);
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    // Get user basic info
    $sql = "SELECT u.*, COUNT(DISTINCT ra.agreement_id) as active_rentals,
                   COUNT(DISTINCT p.property_id) as owned_properties,
                   COUNT(DISTINCT rr.request_id) as total_requests
            FROM users u
            LEFT JOIN rental_agreements ra ON u.user_id = ra.tenant_id AND ra.status = 'active'
            LEFT JOIN properties p ON u.user_id = p.owner_id
            LEFT JOIN rental_requests rr ON u.user_id = rr.tenant_id
            WHERE u.user_id = ?
            GROUP BY u.user_id";

    $stmt = $db->prepare($sql);
    $user = $db->getSingle($stmt, [$userId]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    // Get recent activity
    $activitySql = "SELECT al.*, u2.full_name as actor_name
                    FROM audit_log al
                    LEFT JOIN users u2 ON al.user_id = u2.user_id
                    WHERE al.user_id = ? OR al.details LIKE ?
                    ORDER BY al.created_at DESC LIMIT 10";

    $stmt = $db->prepare($activitySql);
    $activity = $db->getMultiple($stmt, [$userId, '%user_id":"' . $userId . '"%']);

    // Get recent payments (if tenant)
    $payments = [];
    if ($user['role'] === 'tenant') {
        $paymentSql = "SELECT p.*, prop.title as property_title
                       FROM payments p
                       JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
                       JOIN properties prop ON ra.property_id = prop.property_id
                       WHERE p.tenant_id = ?
                       ORDER BY p.created_at DESC LIMIT 5";

        $stmt = $db->prepare($paymentSql);
        $payments = $db->getMultiple($stmt, [$userId]);
    }

    // Generate HTML content
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($user['profile_image']): ?>
                        <img src="../<?php echo PROFILE_IMG_PATH . $user['profile_image']; ?>"
                             alt="Profile" class="rounded-circle mb-3" style="width: 100px; height: 100px;">
                    <?php else: ?>
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 100px; height: 100px; font-size: 2rem; font-weight: bold;">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></p>
                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'employee' ? 'info' : ($user['role'] === 'owner' ? 'success' : 'primary')); ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Account Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <?php if ($user['role'] === 'tenant'): ?>
                            <div class="col-6">
                                <h4 class="text-primary"><?php echo $user['active_rentals']; ?></h4>
                                <small class="text-muted">Active Rentals</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success"><?php echo $user['total_requests']; ?></h4>
                                <small class="text-muted">Total Requests</small>
                            </div>
                        <?php elseif ($user['role'] === 'owner'): ?>
                            <div class="col-12">
                                <h4 class="text-success"><?php echo $user['owned_properties']; ?></h4>
                                <small class="text-muted">Properties Owned</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Account Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
                            <p><strong>Status:</strong>
                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'secondary' : ($user['status'] === 'suspended' ? 'danger' : 'warning')); ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </p>
                            <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php if ($user['last_login']): ?>
                        <p><strong>Last Login:</strong> <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($payments)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Recent Payments</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($payments as $payment): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($payment['property_title']); ?></h6>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></small>
                                        </div>
                                        <span class="badge bg-success"><?php echo CURRENCY . number_format($payment['amount'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($activity)): ?>
                        <p class="text-muted mb-0">No recent activity</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($activity as $item): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['action']); ?></h6>
                                        <p class="mb-1 text-muted small"><?php echo htmlspecialchars($item['details'] ?? ''); ?></p>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($item['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .timeline-marker {
        position: absolute;
        left: -38px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .timeline-content {
        padding-left: 10px;
    }
    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: -32px;
        top: 17px;
        width: 2px;
        height: calc(100% + 3px);
        background: #e9ecef;
    }
    </style>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
}

function getUserActivity() {
    global $db;

    $userId = (int)($_GET['user_id'] ?? 0);
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }

    // Get user info
    $userSql = "SELECT full_name FROM users WHERE user_id = ?";
    $stmt = $db->prepare($userSql);
    $user = $db->getSingle($stmt, [$userId]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    // Get user activity from audit log
    $activitySql = "SELECT al.*, u.full_name as actor_name
                    FROM audit_log al
                    LEFT JOIN users u ON al.user_id = u.user_id
                    WHERE al.user_id = ? OR al.details LIKE ?
                    ORDER BY al.created_at DESC LIMIT 50";

    $stmt = $db->prepare($activitySql);
    $activities = $db->getMultiple($stmt, [$userId, '%user_id":"' . $userId . '"%']);

    // Get login history (if available in audit log)
    $loginActivities = array_filter($activities, function($activity) {
        return stripos($activity['action'], 'login') !== false;
    });

    // Generate HTML content
    ob_start();
    ?>
    <div class="activity-log">
        <h6 class="mb-3">Activity for <?php echo htmlspecialchars($user['full_name']); ?></h6>

        <?php if (empty($activities)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-history fa-3x mb-3"></i>
                <p>No activity recorded for this user</p>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($activities as $activity): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">
                                <i class="fas fa-<?php
                                    echo strpos($activity['action'], 'login') !== false ? 'sign-in-alt' :
                                         (strpos($activity['action'], 'logout') !== false ? 'sign-out-alt' :
                                         (strpos($activity['action'], 'create') !== false ? 'plus' :
                                         (strpos($activity['action'], 'update') !== false ? 'edit' :
                                         (strpos($activity['action'], 'delete') !== false ? 'trash' : 'circle'))));
                                ?> me-2"></i>
                                <?php echo htmlspecialchars($activity['action']); ?>
                            </h6>
                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                        </div>
                        <?php if ($activity['details']): ?>
                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars($activity['details']); ?></p>
                        <?php endif; ?>
                        <small class="text-muted">
                            <?php if ($activity['ip_address']): ?>
                                IP: <?php echo htmlspecialchars($activity['ip_address']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($loginActivities)): ?>
            <div class="mt-4">
                <h6 class="mb-3">Recent Login History</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Action</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($loginActivities, 0, 10) as $login): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($login['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($login['action']); ?></td>
                                    <td><?php echo htmlspecialchars($login['ip_address'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
}

function getUserStatistics() {
    global $db;

    $stats = [
        'total' => 0,
        'tenants' => 0,
        'owners' => 0,
        'employees' => 0,
        'admins' => 0,
        'active' => 0,
        'inactive' => 0,
        'suspended' => 0,
        'pending' => 0,
        'this_month' => 0,
        'active_week' => 0
    ];

    // Get basic counts
    $sql = "SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN role = 'tenant' THEN 1 END) as tenants,
                COUNT(CASE WHEN role = 'owner' THEN 1 END) as owners,
                COUNT(CASE WHEN role = 'employee' THEN 1 END) as employees,
                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
                COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as this_month,
                COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_week
            FROM users";

    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);

    // Update stats with actual values
    if ($result) {
        foreach ($result as $key => $value) {
            $stats[$key] = (int)$value;
        }
    }

    echo json_encode(['success' => true, 'stats' => $stats]);
}

function exportUsersCSV() {
    global $db;

    // Build filter conditions
    $whereClause = '';
    $params = [];
    $search = $_GET['search'] ?? '';
    $roleFilter = $_GET['role'] ?? '';
    $statusFilter = $_GET['status'] ?? '';

    if (!empty($search)) {
        $whereClause .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($roleFilter)) {
        $whereClause .= " AND u.role = ?";
        $params[] = $roleFilter;
    }

    if (!empty($statusFilter)) {
        $whereClause .= " AND u.status = ?";
        $params[] = $statusFilter;
    }

    // Get users data
    $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.role, u.status,
                   u.created_at, u.last_login,
                   COUNT(DISTINCT ra.agreement_id) as active_rentals,
                   COUNT(DISTINCT p.property_id) as owned_properties
            FROM users u
            LEFT JOIN rental_agreements ra ON u.user_id = ra.tenant_id AND ra.status = 'active'
            LEFT JOIN properties p ON u.user_id = p.owner_id
            WHERE 1=1 $whereClause
            GROUP BY u.user_id
            ORDER BY u.created_at DESC";

    $stmt = $db->prepare($sql);
    $users = $db->getMultiple($stmt, $params);

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output CSV headers
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'User ID',
        'Full Name',
        'Email',
        'Phone',
        'Role',
        'Status',
        'Registration Date',
        'Last Login',
        'Active Rentals',
        'Owned Properties'
    ]);

    // Output user data
    foreach ($users as $user) {
        fputcsv($output, [
            $user['user_id'],
            $user['full_name'],
            $user['email'],
            $user['phone'],
            ucfirst($user['role']),
            ucfirst($user['status']),
            date('Y-m-d H:i:s', strtotime($user['created_at'])),
            $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never',
            $user['active_rentals'],
            $user['owned_properties']
        ]);
    }

    fclose($output);
    exit;
}
?>
