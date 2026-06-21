<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$db = new Database();
$property_id = intval($_POST['property_id'] ?? 0);

if ($property_id <= 0) {
    exit(json_encode(['success' => false, 'message' => 'Invalid property ID']));
}

try {
    // Get property activity log (if table exists)
    $activities = [];
    try {
        $activity_sql = "SELECT pal.*, u.full_name as employee_name
                         FROM property_activity_log pal
                         LEFT JOIN users u ON pal.employee_id = u.user_id
                         WHERE pal.property_id = ?
                         ORDER BY pal.created_at DESC
                         LIMIT 50";
        $stmt = $db->prepare($activity_sql);
        $activities = $db->getMultiple($stmt, [$property_id]);
    } catch (Exception $e) {
        // Table doesn't exist, continue with empty activities
        $activities = [];
    }

    // Get rental requests history
    $requests_sql = "SELECT rr.*, u.full_name as tenant_name
                     FROM rental_requests rr
                     LEFT JOIN users u ON rr.tenant_id = u.user_id
                     WHERE rr.property_id = ?
                     ORDER BY rr.created_at DESC";
    $requests = $db->getMultiple($db->prepare($requests_sql), [$property_id]);

    // Get rental agreements history
    $agreements_sql = "SELECT ra.*, u.full_name as tenant_name
                       FROM rental_agreements ra
                       LEFT JOIN users u ON ra.tenant_id = u.user_id
                       WHERE ra.property_id = ?
                       ORDER BY ra.created_at DESC";
    $agreements = $db->getMultiple($db->prepare($agreements_sql), [$property_id]);

    // Get maintenance history
    $maintenance_sql = "SELECT mr.*, u.full_name as tenant_name
                        FROM maintenance_requests mr
                        LEFT JOIN users u ON mr.tenant_id = u.user_id
                        WHERE mr.property_id = ?
                        ORDER BY mr.created_at DESC";
    $maintenance = $db->getMultiple($db->prepare($maintenance_sql), [$property_id]);

    ob_start();
    ?>
    <div class="activity-section mb-4">
        <h6 class="mb-3">Status Changes & Employee Actions</h6>
        <?php if (empty($activities)): ?>
            <p class="text-muted">No status changes recorded.</p>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
                <div class="activity-item mb-2">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                    </div>
                    <div class="text-muted small">
                        by <?php echo htmlspecialchars($activity['employee_name'] ?? 'System'); ?>
                        <?php if ($activity['old_value'] && $activity['new_value']): ?>
                            <br>Changed from: <?php echo htmlspecialchars($activity['old_value']); ?> → <?php echo htmlspecialchars($activity['new_value']); ?>
                        <?php endif; ?>
                        <?php if ($activity['notes']): ?>
                            <br>Notes: <?php echo htmlspecialchars($activity['notes']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="activity-section mb-4">
        <h6 class="mb-3">Rental Requests History</h6>
        <?php if (empty($requests)): ?>
            <p class="text-muted">No rental requests found.</p>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="activity-item mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>Rental Request</strong>
                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($request['created_at'])); ?></small>
                    </div>
                    <div class="text-muted small">
                        Tenant: <?php echo htmlspecialchars($request['tenant_name']); ?>
                        <br>Status: <span class="badge bg-<?php echo $request['status'] === 'pending' ? 'warning' : ($request['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($request['status']); ?></span>
                        <?php if ($request['message']): ?>
                            <br>Message: <?php echo htmlspecialchars(substr($request['message'], 0, 100)) . (strlen($request['message']) > 100 ? '...' : ''); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="activity-section mb-4">
        <h6 class="mb-3">Rental Agreements History</h6>
        <?php if (empty($agreements)): ?>
            <p class="text-muted">No rental agreements found.</p>
        <?php else: ?>
            <?php foreach ($agreements as $agreement): ?>
                <div class="activity-item mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>Rental Agreement</strong>
                        <small class="text-muted"><?php echo date('M j, Y', strtotime($agreement['created_at'])); ?></small>
                    </div>
                    <div class="text-muted small">
                        Tenant: <?php echo htmlspecialchars($agreement['tenant_name']); ?>
                        <br>Period: <?php echo date('M j, Y', strtotime($agreement['start_date'])) . ' - ' . date('M j, Y', strtotime($agreement['end_date'])); ?>
                        <br>Status: <span class="badge bg-<?php echo $agreement['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($agreement['status']); ?></span>
                        <br>Rent: <?php echo number_format($agreement['monthly_rent']); ?> ETB/month
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="activity-section">
        <h6 class="mb-3">Maintenance History</h6>
        <?php if (empty($maintenance)): ?>
            <p class="text-muted">No maintenance requests found.</p>
        <?php else: ?>
            <?php foreach ($maintenance as $main): ?>
                <div class="activity-item mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>Maintenance Request</strong>
                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($main['created_at'])); ?></small>
                    </div>
                    <div class="text-muted small">
                        Tenant: <?php echo htmlspecialchars($main['tenant_name']); ?>
                        <br>Issue: <?php echo htmlspecialchars($main['issue_type']); ?>
                        <br>Priority: <span class="badge bg-<?php echo $main['priority'] === 'urgent' ? 'danger' : ($main['priority'] === 'high' ? 'warning' : 'info'); ?>"><?php echo ucfirst($main['priority']); ?></span>
                        <br>Status: <span class="badge bg-<?php echo $main['status'] === 'pending' ? 'warning' : ($main['status'] === 'in_progress' ? 'info' : 'success'); ?>"><?php echo ucfirst(str_replace('_', ' ', $main['status'])); ?></span>
                        <?php if ($main['notes']): ?>
                            <br>Notes: <?php echo htmlspecialchars(substr($main['notes'], 0, 100)) . (strlen($main['notes']) > 100 ? '...' : ''); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    $content = ob_get_clean();
    echo json_encode(['success' => true, 'content' => $content]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
