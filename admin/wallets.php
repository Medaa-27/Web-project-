<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

$title = "Wallet Management - Admin Dashboard";

// Require admin login
$session->requireRole('admin');

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = ["1=1"];
$params = [];

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM wallets w
              JOIN users u ON w.user_id = u.user_id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$total_result = $db->getSingle($stmt, $params);
$total_wallets = $total_result['total'];
$total_pages = ceil($total_wallets / $limit);

// Get wallets with pagination
$sql = "SELECT w.*, u.full_name, u.email, u.role, u.phone
        FROM wallets w
        JOIN users u ON w.user_id = u.user_id 
        $where_clause
        ORDER BY w.balance DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$wallets = $db->getMultiple($stmt, $params);

// Get statistics
$stats_sql = "SELECT 
                 COUNT(*) as total_wallets,
                 SUM(balance) as total_system_balance,
                 AVG(balance) as average_balance,
                 SUM(CASE WHEN u.role = 'tenant' THEN balance ELSE 0 END) as tenant_total,
                 SUM(CASE WHEN u.role = 'owner' THEN balance ELSE 0 END) as owner_total,
                 SUM(CASE WHEN u.role = 'admin' THEN balance ELSE 0 END) as admin_total
              FROM wallets w
              JOIN users u ON w.user_id = u.user_id";
$stmt = $db->prepare($stats_sql);
$stats = $db->getSingle($stmt);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">System Wallets Overview</h1>
                <div>
                    <a href="withdrawals.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-hand-holding-usd me-1"></i>Manage Withdrawals
                    </a>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-2 opacity-75">Total System Balance</h6>
                            <h3 class="mb-0 fw-bold">ETB <?php echo number_format($stats['total_system_balance'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-2 opacity-75">Owner Balances</h6>
                            <h3 class="mb-0 fw-bold">ETB <?php echo number_format($stats['owner_total'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-2 opacity-75">Tenant Balances</h6>
                            <h3 class="mb-0 fw-bold">ETB <?php echo number_format($stats['tenant_total'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-secondary text-white h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-2 opacity-75">Admin Balances</h6>
                            <h3 class="mb-0 fw-bold">ETB <?php echo number_format($stats['admin_total'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Search Users</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Name, email or phone..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">User Role</label>
                            <select class="form-select form-select-sm" name="role">
                                <option value="">All Roles</option>
                                <option value="tenant" <?php echo $role_filter === 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                                <option value="owner" <?php echo $role_filter === 'owner' ? 'selected' : ''; ?>>Owner</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm px-4">Filter</button>
                                <a href="wallets.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Wallets Table -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">User</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                    <th>Balance</th>
                                    <th>Last Updated</th>
                                    <th class="pe-4 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($wallets)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <p class="text-muted mb-0">No wallets found matching your criteria.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($wallets as $wallet): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold"><?php echo htmlspecialchars($wallet['full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($wallet['email']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?php 
                                                    echo match($wallet['role']) {
                                                        'admin' => 'danger',
                                                        'owner' => 'success',
                                                        'tenant' => 'info',
                                                        default => 'secondary'
                                                    };
                                                ?> bg-opacity-10 text-<?php 
                                                    echo match($wallet['role']) {
                                                        'admin' => 'danger',
                                                        'owner' => 'success',
                                                        'tenant' => 'info',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($wallet['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($wallet['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong class="text-primary">ETB <?php echo number_format($wallet['balance'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($wallet['updated_at'])); ?>
                                                </small>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                        <li><a class="dropdown-item" href="user-details.php?id=<?php echo $wallet['user_id']; ?>"><i class="fas fa-user me-2"></i>View User</a></li>
                                                        <li><a class="dropdown-item" href="withdrawals.php?search=<?php echo urlencode($wallet['email']); ?>"><i class="fas fa-history me-2"></i>Withdrawals</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-edit me-2"></i>Adjust Balance</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white border-0 py-3">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $role_filter; ?>&search=<?php echo $search; ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $role_filter; ?>&search=<?php echo $search; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
