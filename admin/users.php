<?php

require_once '../includes/config.php';

$title = "Users";

$session->requireLogin();
if (!in_array($session->getUserRole(), ['admin', 'employee'], true)) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    $session->redirectToDashboard();
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$perPage = 15;

$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$role = isset($_GET['role']) ? trim($_GET['role']) : '';

$status = isset($_GET['status']) ? trim($_GET['status']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    error_log('users.php POST action=' . $action . ', user_id=' . ($_POST['user_id'] ?? '')); // debug

    if ($action === 'update_status') {

        $uid = (int)($_POST['user_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        if ($uid && in_array($newStatus, ['active','inactive','suspended','pending'])) {
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            if ($db->execute($stmt, [$newStatus, $uid])) {
                $_SESSION['success'] = 'User status updated';
            } else {
                $_SESSION['error'] = 'Failed to update status';
            }
        }

        header("Location: users.php");
        exit;

    } elseif ($action === 'delete_user') {

        $uid = (int)($_POST['user_id'] ?? 0);
        error_log('users.php delete_user branch start uid=' . $uid);

        if (!$uid) {
            $_SESSION['error'] = 'Invalid user ID';
        } else {
            $userCheck = $db->getSingle($db->prepare("SELECT user_id, role FROM users WHERE user_id = ?"), [$uid]);

            if (!$userCheck) {
                $_SESSION['error'] = 'User not found';
            } elseif ($uid == ($_SESSION['user_id'] ?? 0)) {
                $_SESSION['error'] = 'You cannot delete your own account';
            } elseif ($userCheck['role'] === 'admin') {
                $_SESSION['error'] = 'Cannot delete admin users';
            } else {
                try {
                    $db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0");

                    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                    if ($db->execute($stmt, [$uid])) {
                        $_SESSION['success'] = 'User deleted successfully';
                    } else {
                        $dbError = $db->getLastError();
                        $_SESSION['error'] = 'Failed to delete user' . ($dbError ? ': ' . $dbError : '');
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Deletion failed: ' . $e->getMessage();
                } finally {
                    $db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1");
                }
            }
        }

        header("Location: users.php");
        exit;

    }
}

if (isset($_GET['delete_user_id'])) {
    $uid = (int)$_GET['delete_user_id'];

    if (!$uid) {
        $_SESSION['error'] = 'Invalid user ID';
    } else {
        $userCheck = $db->getSingle($db->prepare("SELECT user_id, role FROM users WHERE user_id = ?"), [$uid]);

        if (!$userCheck) {
            $_SESSION['error'] = 'User not found';
        } elseif ($uid == ($_SESSION['user_id'] ?? 0)) {
            $_SESSION['error'] = 'You cannot delete your own account';
        } elseif ($userCheck['role'] === 'admin') {
            $_SESSION['error'] = 'Cannot delete admin users';
        } else {
            try {
                $db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0");

                $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                if ($db->execute($stmt, [$uid])) {
                    $_SESSION['success'] = 'User deleted successfully (via quick link)';
                } else {
                    $dbError = $db->getLastError();
                    $_SESSION['error'] = 'Failed to delete user' . ($dbError ? ': ' . $dbError : '');
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Deletion failed: ' . $e->getMessage();
            } finally {
                $db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
        }
    }

    header("Location: users.php");
    exit;
}

$sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.role, u.status, u.created_at

        FROM users u";

$countSql = "SELECT COUNT(*) as total FROM users u";

$where = [];

$params = [];

$countParams = [];

if ($search !== '') {

    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";

    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);

    $countParams = array_merge($countParams, ["%$search%", "%$search%", "%$search%"]);

}

if ($role !== '') {

    $where[] = "u.role = ?";

    $params[] = $role;

    $countParams[] = $role;

}

if ($status !== '') {

    $where[] = "u.status = ?";

    $params[] = $status;

    $countParams[] = $status;

}

if ($where) {

    $sql .= " WHERE " . implode(' AND ', $where);

    $countSql .= " WHERE " . implode(' AND ', $where);

}

$sql .= " ORDER BY u.created_at DESC LIMIT " . (int)$offset . ", " . (int)$perPage;

$totalUsers = 0;

$totalPages = 1;

$users = [];

try {

    $countStmt = $db->prepare($countSql);

    $count = $db->getSingle($countStmt, $countParams);

    $totalUsers = (int)($count['total'] ?? 0);

    $totalPages = max(1, (int)ceil($totalUsers / $perPage));

    $stmt = $db->prepare($sql);

    $users = $db->getMultiple($stmt, $params);

} catch (Exception $e) {

    $users = [];

}

include '../includes/header.php';

?>

<div class="container-fluid mt-4">

    <div class="row">

        <div class="col-lg-3"><?php include '../includes/sidebar.php'; ?></div>

        <div class="col-lg-9">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h1 class="h4 mb-0">Users</h1>

                <div class="d-flex gap-2">

                    <a href="user-add.php" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Add User</a>

                </div>

            </div>

            <div class="card mb-3">

                <div class="card-header"><i class="fas fa-filter me-2"></i>Search & Filters</div>

                <div class="card-body">

                    <form method="get" class="row g-3">

                        <div class="col-md-4">

                            <label class="form-label">Search</label>

                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, phone">

                        </div>

                        <div class="col-md-3">

                            <label class="form-label">Role</label>

                            <select class="form-select" name="role">

                                <option value="">All</option>

                                <option value="admin" <?php echo $role==='admin'?'selected':''; ?>>Admin</option>

                                <option value="employee" <?php echo $role==='employee'?'selected':''; ?>>Employee</option>

                                <option value="owner" <?php echo $role==='owner'?'selected':''; ?>>Owner</option>

                                <option value="tenant" <?php echo $role==='tenant'?'selected':''; ?>>Tenant</option>

                            </select>

                        </div>

                        <div class="col-md-3">

                            <label class="form-label">Status</label>

                            <select class="form-select" name="status">

                                <option value="">All</option>

                                <option value="active" <?php echo $status==='active'?'selected':''; ?>>Active</option>

                                <option value="inactive" <?php echo $status==='inactive'?'selected':''; ?>>Inactive</option>

                                <option value="suspended" <?php echo $status==='suspended'?'selected':''; ?>>Suspended</option>

                                <option value="pending" <?php echo $status==='pending'?'selected':''; ?>>Pending</option>

                            </select>

                        </div>

                        <div class="col-md-2 d-flex align-items-end">

                            <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button>

                        </div>

                    </form>

                </div>

            </div>

            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <div><i class="fas fa-users me-2"></i>User Accounts (<?php echo $totalUsers; ?>)</div>

                </div>

                <div class="card-body">

                    <?php if (empty($users)): ?>

                        <div class="text-center py-5 text-muted">No users found</div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table table-hover">

                                <thead class="table-light">

                                    <tr>

                                        <th>User</th>

                                        <th>Email</th>

                                        <th>Phone</th>

                                        <th>Role</th>

                                        <th>Status</th>

                                        <th>Registered</th>

                                        <th>Actions</th>

                                    </tr>

                                </thead>

                                <tbody>

                                <?php foreach ($users as $u): ?>

                                    <tr>

                                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>

                                        <td><?php echo htmlspecialchars($u['email']); ?></td>

                                        <td><?php echo htmlspecialchars($u['phone']); ?></td>

                                        <td><?php echo ucfirst($u['role']); ?></td>

                                        <td><?php echo ucfirst($u['status']); ?></td>

                                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))); ?></td>

                                        <td class="d-flex flex-wrap gap-2">

                                            <a class="btn btn-sm btn-outline-primary" href="user-view.php?id=<?php echo (int)$u['user_id']; ?>"><i class="fas fa-eye me-1"></i>View</a>

                                            <a class="btn btn-sm btn-outline-secondary" href="user-edit.php?id=<?php echo (int)$u['user_id']; ?>"><i class="fas fa-edit me-1"></i>Edit</a>

                                            <form method="post" class="d-inline">

                                                <input type="hidden" name="action" value="update_status">

                                                <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">

                                                <input type="hidden" name="new_status" value="<?php echo $u['status']==='active'?'inactive':'active'; ?>">

                                                <button class="btn btn-sm <?php echo $u['status']==='active'?'btn-outline-warning':'btn-outline-success'; ?>">

                                                    <?php echo $u['status']==='active'?'Deactivate':'Activate'; ?>

                                                </button>

                                            </form>

                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">

                                                <input type="hidden" name="action" value="delete_user">

                                                <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">

                                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash me-1"></i>Delete</button>

                                            </form>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                        <?php if ($totalPages > 1): ?>

                            <nav class="mt-3" aria-label="pagination">

                                <ul class="pagination justify-content-center">

                                    <?php if ($page > 1): ?>

                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>">Previous</a></li>

                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                                        <li class="page-item <?php echo $i===$page?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a></li>

                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>

                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>">Next</a></li>

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

