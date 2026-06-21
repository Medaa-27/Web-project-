<?php
require_once '../includes/config.php';
$title = "Notification Analytics - Admin";
$session->requireRole('admin');

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

if (strtotime($start_date) > strtotime($end_date)) {
    $tmp = $start_date; $start_date = $end_date; $end_date = $tmp;
}

$summary_sql = "SELECT 
    COUNT(*) as total_sent,
    COUNT(CASE WHEN is_read = 1 THEN 1 END) as total_read,
    COUNT(CASE WHEN is_read = 0 THEN 1 END) as total_unread
    FROM notifications WHERE created_at BETWEEN ? AND ?";
$summary = $db->getSingle($db->prepare($summary_sql), [$start_date, $end_date]) ?: ['total_sent'=>0,'total_read'=>0,'total_unread'=>0];

$by_type_sql = "SELECT type, COUNT(*) as count, COUNT(CASE WHEN is_read = 1 THEN 1 END) as read_count
                FROM notifications WHERE created_at BETWEEN ? AND ? GROUP BY type";
$by_type = $db->getMultiple($db->prepare($by_type_sql), [$start_date, $end_date]);

$by_role_sql = "SELECT u.role, COUNT(*) as count, COUNT(CASE WHEN n.is_read = 1 THEN 1 END) as read_count
                FROM notifications n LEFT JOIN users u ON n.user_id = u.user_id
                WHERE n.created_at BETWEEN ? AND ? GROUP BY u.role";
$by_role = $db->getMultiple($db->prepare($by_role_sql), [$start_date, $end_date]);

include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Notification Analytics</h1>
                <a href="notifications.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Date Range</h5></div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid"><button class="btn btn-primary" type="submit"><i class="fas fa-sync me-2"></i>Refresh</button></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $summary['total_sent']; ?></h3>
                            <p class="mb-0">Total Sent</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $summary['total_read']; ?></h3>
                            <p class="mb-0">Total Read</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $summary['total_unread']; ?></h3>
                            <p class="mb-0">Total Unread</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-layer-group me-2"></i>By Type</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead><tr><th>Type</th><th>Sent</th><th>Read</th><th>Read Rate</th></tr></thead>
                            <tbody>
                            <?php foreach ($by_type as $row): 
                                $rate = ($row['count']>0) ? round(($row['read_count']/$row['count'])*100, 1) : 0; ?>
                                <tr>
                                    <td><?php echo ucfirst($row['type']); ?></td>
                                    <td><?php echo (int)$row['count']; ?></td>
                                    <td><?php echo (int)$row['read_count']; ?></td>
                                    <td><?php echo $rate; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>By Role</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead><tr><th>Role</th><th>Sent</th><th>Read</th><th>Read Rate</th></tr></thead>
                            <tbody>
                            <?php foreach ($by_role as $row): 
                                $rate = ($row['count']>0) ? round(($row['read_count']/$row['count'])*100, 1) : 0; ?>
                                <tr>
                                    <td><?php echo ucfirst($row['role'] ?? 'Unknown'); ?></td>
                                    <td><?php echo (int)$row['count']; ?></td>
                                    <td><?php echo (int)$row['read_count']; ?></td>
                                    <td><?php echo $rate; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
