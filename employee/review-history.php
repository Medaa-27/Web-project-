<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Property Review History";

$employee_id = $session->getUserId();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query conditions
$where_conditions = ["p.reviewed_by = ?"];
$params = [$employee_id];

if ($status_filter !== 'all') {
    $where_conditions[] = "p.review_status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(p.review_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(p.review_date) <= ?";
    $params[] = $date_to;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$sql = "SELECT COUNT(*) as total 
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        {$where_clause}";
$stmt = $db->prepare($sql);
$total_result = $db->getSingle($stmt, $params);
$total_reviews = $total_result['total'];
$total_pages = ceil($total_reviews / $limit);

// Get review history
$sql = "SELECT p.*, u.full_name as owner_name, u.email as owner_email,
               l.location_name, l.subcity,
               reviewer.full_name as reviewer_name
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id
        {$where_clause}
        ORDER BY p.review_date DESC
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$reviews = $db->getMultiple($stmt, array_merge($params, [$limit, $offset]));

// Get statistics
$stats = [];
$sql = "SELECT review_status, COUNT(*) as count 
        FROM properties 
        WHERE reviewed_by = ?
        GROUP BY review_status";
$stmt = $db->prepare($sql);
$review_stats = $db->getMultiple($stmt, [$employee_id]);
foreach ($review_stats as $stat) {
    $stats[$stat['review_status']] = $stat['count'];
}

// Get monthly review trends
$sql = "SELECT DATE_FORMAT(review_date, '%Y-%m') as month, COUNT(*) as count
        FROM properties 
        WHERE reviewed_by = ? AND review_date IS NOT NULL
        GROUP BY DATE_FORMAT(review_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12";
$stmt = $db->prepare($sql);
$monthly_trends = $db->getMultiple($stmt, [$employee_id]);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Header -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">Property Review History</h1>
                            <p class="text-muted mb-0">Track your property review activities and decisions</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="fas fa-download me-2"></i>Export Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo array_sum($stats); ?></h3>
                            <p class="text-muted mb-0">Total Reviews</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['approved'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Approved</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-danger bg-opacity-10 text-danger mx-auto">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['rejected'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Rejected</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">
                                <i class="fas fa-edit fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['needs_revision'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Needs Revision</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="needs_revision" <?php echo $status_filter === 'needs_revision' ? 'selected' : ''; ?>>Needs Revision</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label><br>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row">
                <!-- Review History -->
                <div class="col-lg-8 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <h5 class="mb-0">Review History</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($reviews)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No reviews found</h5>
                                    <p class="text-muted">No reviews match your current filters.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Property</th>
                                                <th>Owner</th>
                                                <th>Status</th>
                                                <th>Review Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reviews as $review): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($review['title']); ?></h6>
                                                                <small class="text-muted">
                                                                    <?php echo ucfirst($review['property_type']); ?> • 
                                                                    <?php echo $review['bedrooms']; ?> bed • 
                                                                    <?php echo $review['bathrooms']; ?> bath
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($review['owner_name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($review['owner_email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_colors = [
                                                            'approved' => 'success',
                                                            'rejected' => 'danger',
                                                            'needs_revision' => 'warning'
                                                        ];
                                                        $color = $status_colors[$review['review_status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $color; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $review['review_status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($review['review_date']): ?>
                                                            <small><?php echo date('M d, Y H:i', strtotime($review['review_date'])); ?></small>
                                                        <?php else: ?>
                                                            <small class="text-muted">Not reviewed</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#reviewDetailsModal<?php echo $review['property_id']; ?>">
                                                                <i class="fas fa-eye"></i> Details
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Review Details Modal -->
                                                <div class="modal fade" id="reviewDetailsModal<?php echo $review['property_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Review Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-muted">Property Information</h6>
                                                                        <table class="table table-sm">
                                                                            <tr>
                                                                                <td><strong>Title:</strong></td>
                                                                                <td><?php echo htmlspecialchars($review['title']); ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Type:</strong></td>
                                                                                <td><?php echo ucfirst($review['property_type']); ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Location:</strong></td>
                                                                                <td><?php echo htmlspecialchars($review['location_name'] ?? 'Not specified'); ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Monthly Rent:</strong></td>
                                                                                <td>ETB <?php echo number_format($review['monthly_rent'], 2); ?></td>
                                                                            </tr>
                                                                        </table>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-muted">Review Information</h6>
                                                                        <table class="table table-sm">
                                                                            <tr>
                                                                                <td><strong>Status:</strong></td>
                                                                                <td>
                                                                                    <span class="badge bg-<?php echo $color; ?>">
                                                                                        <?php echo ucfirst(str_replace('_', ' ', $review['review_status'])); ?>
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Review Date:</strong></td>
                                                                                <td>
                                                                                    <?php if ($review['review_date']): ?>
                                                                                        <?php echo date('M d, Y H:i', strtotime($review['review_date'])); ?>
                                                                                    <?php else: ?>
                                                                                        Not reviewed
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>Reviewed By:</strong></td>
                                                                                <td><?php echo htmlspecialchars($review['reviewer_name'] ?? 'N/A'); ?></td>
                                                                            </tr>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                                
                                                                <?php if ($review['review_comments']): ?>
                                                                <div class="mt-3">
                                                                    <h6 class="text-muted">Review Comments</h6>
                                                                    <div class="alert alert-info">
                                                                        <?php echo nl2br(htmlspecialchars($review['review_comments'])); ?>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="card-footer">
                                        <nav>
                                            <ul class="pagination pagination-sm mb-0 justify-content-center">
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&page=<?php echo $i; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Trends -->
                <div class="col-lg-4 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <h5 class="mb-0">Monthly Review Trends</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($monthly_trends)): ?>
                                <p class="text-muted text-center">No data available</p>
                            <?php else: ?>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="trendsChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Review History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="export-reviews.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select name="format" class="form-select" required>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" name="date_from" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-6">
                                <input type="date" name="date_to" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Filter</label>
                        <select name="status" class="form-select">
                            <option value="all">All Status</option>
                            <option value="approved">Approved Only</option>
                            <option value="rejected">Rejected Only</option>
                            <option value="needs_revision">Needs Revision Only</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($monthly_trends)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const trendsData = <?php echo json_encode(array_reverse($monthly_trends)); ?>;
const ctx = document.getElementById('trendsChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: trendsData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Reviews Count',
            data: trendsData.map(item => item.count),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
