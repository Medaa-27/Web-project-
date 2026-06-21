<?php
require_once '../includes/config.php';
$title = "View User";
$session->requireLogin();
if (!in_array($session->getUserRole(), ['admin', 'employee'], true)) {
    $_SESSION['error'] = "You don't have permission to view this user.";
    $session->redirectToDashboard();
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: users.php"); exit; }
$user = null;
try {
    $stmt = $db->prepare("SELECT user_id, full_name, email, phone, role, status, id_number, address, id_image, profile_image, created_at FROM users WHERE user_id = ?");
    $user = $db->getSingle($stmt, [$id]);
} catch (Exception $e) {}
if (!$user) { $_SESSION['error'] = 'User not found'; header("Location: users.php"); exit; }
$properties = [];
$requests = [];
$agreements = [];
try {
    if ($user['role'] === 'owner') {
        $ps = $db->prepare("SELECT property_id, title, sub_city, created_at FROM properties WHERE owner_id = ? ORDER BY created_at DESC LIMIT 50");
        $properties = $db->getMultiple($ps, [$id]);
    } elseif ($user['role'] === 'tenant') {
        $rs = $db->prepare("SELECT request_id, property_id, status, created_at FROM rental_requests WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 50");
        $requests = $db->getMultiple($rs, [$id]);
        $as = $db->prepare("SELECT agreement_id, property_id, status, start_date, end_date FROM rental_agreements WHERE tenant_id = ? ORDER BY start_date DESC LIMIT 50");
        $agreements = $db->getMultiple($as, [$id]);
    }
} catch (Exception $e) {}
include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">User Details</h1>
                <div class="d-flex gap-2">
                    <a href="user-edit.php?id=<?php echo (int)$user['user_id']; ?>" class="btn btn-outline-secondary"><i class="fas fa-edit me-2"></i>Edit</a>
                    <a href="users.php" class="btn btn-outline-secondary"><i class="fas fa-users me-2"></i>Users</a>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div><?php echo htmlspecialchars($user['email']); ?></div>
                            <div><?php echo htmlspecialchars($user['phone']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div>Role: <?php echo ucfirst($user['role']); ?></div>
                            <div>Status: <?php echo ucfirst($user['status']); ?></div>
                            <?php if (!empty($user['id_image']) || !empty($user['profile_image'])): ?>
                                <?php $idImagePath = !empty($user['id_image']) ? $user['id_image'] : $user['profile_image']; ?>
                                <div>
                                    ID Image:
                                    <a href="../<?php echo htmlspecialchars($idImagePath); ?>" target="_blank">View</a>
                                </div>
                            <?php endif; ?>
                            <div>Registered: <?php echo htmlspecialchars($user['created_at']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($user['role'] === 'owner'): ?>
                <div class="card mb-3">
                    <div class="card-header"><i class="fas fa-home me-2"></i>Owner Properties</div>
                    <div class="card-body">
                        <?php if (empty($properties)): ?>
                            <div class="text-muted">No properties</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light"><tr><th>ID</th><th>Title</th><th>Sub-city</th><th>Created</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($properties as $p): ?>
                                            <tr>
                                                <td><?php echo (int)$p['property_id']; ?></td>
                                                <td><?php echo htmlspecialchars($p['title']); ?></td>
                                                <td><?php echo htmlspecialchars($p['sub_city']); ?></td>
                                                <td><?php echo htmlspecialchars($p['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($user['role'] === 'tenant'): ?>
                <div class="card mb-3">
                    <div class="card-header"><i class="fas fa-paper-plane me-2"></i>Tenant Requests</div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="text-muted">No requests</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light"><tr><th>ID</th><th>Property</th><th>Status</th><th>Created</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($requests as $r): ?>
                                            <tr>
                                                <td><?php echo (int)$r['request_id']; ?></td>
                                                <td><?php echo (int)$r['property_id']; ?></td>
                                                <td><?php echo htmlspecialchars($r['status']); ?></td>
                                                <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header"><i class="fas fa-file-contract me-2"></i>Tenant Agreements</div>
                    <div class="card-body">
                        <?php if (empty($agreements)): ?>
                            <div class="text-muted">No agreements</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light"><tr><th>ID</th><th>Property</th><th>Status</th><th>Start</th><th>End</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($agreements as $a): ?>
                                            <tr>
                                                <td><?php echo (int)$a['agreement_id']; ?></td>
                                                <td><?php echo (int)$a['property_id']; ?></td>
                                                <td><?php echo htmlspecialchars($a['status']); ?></td>
                                                <td><?php echo htmlspecialchars($a['start_date']); ?></td>
                                                <td><?php echo htmlspecialchars($a['end_date']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
