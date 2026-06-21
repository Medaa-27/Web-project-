<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = 'My Tenants - Aksum Rental System';

$owner_id = $session->getUserId();
$status_filter = $_GET['status'] ?? 'active';
if (!in_array($status_filter, ['active', 'pending', 'terminated', 'expired', 'all'], true)) {
    $status_filter = 'active';
}

$sql = "SELECT ra.*, p.title as property_title, p.address, l.location_name,
               u.user_id as tenant_user_id, u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone, u.id_image as tenant_id_image, u.profile_image as tenant_profile_image
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        JOIN users u ON ra.tenant_id = u.user_id
        WHERE p.owner_id = ?";
$params = [$owner_id];

if ($status_filter !== 'all') {
    $sql .= " AND ra.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY ra.created_at DESC";
$stmt = $db->prepare($sql);
$tenants = $db->getMultiple($stmt, $params);

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
                            <h1 class="h3 mb-0">My Tenants</h1>
                            <p class="text-muted mb-0">View tenants renting your properties</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <select class="form-select" onchange="location.href='?status=' + this.value;">
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="terminated" <?php echo $status_filter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                                <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tenant</th>
                                    <th>Property</th>
                                    <th>Agreement</th>
                                    <th>Status</th>
                                    <th>ID Image</th>
                                    <th>Rent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tenants)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No tenants found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tenants as $t): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($t['tenant_name']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($t['tenant_email']); ?></div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($t['property_title']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($t['location_name'] ?? ''); ?></div>
                                            </td>
                                            <td>
                                                <div class="small text-muted">Start</div>
                                                <div><?php echo date('M d, Y', strtotime($t['start_date'])); ?></div>
                                                <div class="small text-muted mt-1">End</div>
                                                <div><?php echo date('M d, Y', strtotime($t['end_date'])); ?></div>
                                            </td>
                                            <td>
                                                <?php
                                                $badge = [
                                                    'active' => 'success',
                                                    'pending' => 'warning',
                                                    'terminated' => 'danger',
                                                    'expired' => 'secondary'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $badge[$t['status']] ?? 'secondary'; ?>"><?php echo htmlspecialchars($t['status']); ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($t['tenant_id_image']) || !empty($t['tenant_profile_image'])): ?>
                                                    <?php $tenantIdImage = !empty($t['tenant_id_image']) ? $t['tenant_id_image'] : $t['tenant_profile_image']; ?>
                                                    <a href="../<?php echo htmlspecialchars($tenantIdImage); ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                                <?php else: ?>
                                                    <span class="text-muted small">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong>ETB <?php echo number_format((float)$t['monthly_rent'], 0); ?></strong>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>
