<?php
require_once '../includes/config.php';
$title = "Edit User";
$session->requireRole('admin');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: users.php"); exit; }
$user = null;
try {
    $stmt = $db->prepare("SELECT user_id, full_name, email, phone, role, status FROM users WHERE user_id = ?");
    $user = $db->getSingle($stmt, [$id]);
} catch (Exception $e) {}
if (!$user) { $_SESSION['error'] = 'User not found'; header("Location: users.php"); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $status = $_POST['status'] ?? $user['status'];
    if ($full_name === '' || $email === '') {
        $_SESSION['error'] = 'Full name and email are required';
    } else {
        $dupe = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ?");
        if ($db->getSingle($dupe, [$email, $id])) {
            $_SESSION['error'] = 'Email already used by another account';
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE user_id = ?");
            if ($db->execute($stmt, [$full_name, $email, $phone, $role, $status, $id])) {
                $_SESSION['success'] = 'User updated successfully';
                header("Location: users.php");
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update user';
            }
        }
    }
}
include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">Edit User</h1>
                <a href="users.php" class="btn btn-outline-secondary"><i class="fas fa-users me-2"></i>Users</a>
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="admin" <?php echo $user['role']==='admin'?'selected':''; ?>>Admin</option>
                                <option value="employee" <?php echo $user['role']==='employee'?'selected':''; ?>>Employee</option>
                                <option value="owner" <?php echo $user['role']==='owner'?'selected':''; ?>>Owner</option>
                                <option value="tenant" <?php echo $user['role']==='tenant'?'selected':''; ?>>Tenant</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $user['status']==='active'?'selected':''; ?>>Active</option>
                                <option value="inactive" <?php echo $user['status']==='inactive'?'selected':''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $user['status']==='suspended'?'selected':''; ?>>Suspended</option>
                                <option value="pending" <?php echo $user['status']==='pending'?'selected':''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
