<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Rental History - Aksum Rental System";

$user_id = $session->getUserId();

// Get rental history with filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'property_type' => $_GET['property_type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Build query
$sql = "SELECT ra.*, p.title, p.property_type, p.monthly_rent, p.security_deposit,
               l.location_name, u.full_name as owner_name,
               (SELECT SUM(amount) FROM payments WHERE agreement_id = ra.agreement_id AND status = 'completed') as total_paid,
               TIMESTAMPDIFF(MONTH, ra.start_date, COALESCE(ra.end_date, NOW())) as duration_months
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE ra.tenant_id = ?";

$params = [$user_id];
$conditions = [];

if (!empty($filters['status'])) {
    $conditions[] = "ra.status = ?";
    $params[] = $filters['status'];
}

if (!empty($filters['property_type'])) {
    $conditions[] = "p.property_type = ?";
    $params[] = $filters['property_type'];
}

if (!empty($filters['date_from'])) {
    $conditions[] = "ra.start_date >= ?";
    $params[] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $conditions[] = "ra.end_date <= ?";
    $params[] = $filters['date_to'];
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY ra.created_at DESC";

$stmt = $db->prepare($sql);
$rental_history = $db->getMultiple($stmt, $params);

// Get statistics
$sql = "SELECT 
            COUNT(*) as total_rentals,
            SUM(CASE WHEN ra.status = 'active' THEN 1 ELSE 0 END) as active_rentals,
            SUM(CASE WHEN ra.status = 'completed' THEN 1 ELSE 0 END) as completed_rentals,
            SUM(ra.monthly_rent) as total_rent_value,
            AVG(ra.monthly_rent) as avg_rent
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        WHERE ra.tenant_id = ?";
$stmt = $db->prepare($sql);
$stats = $db->getSingle($stmt, [$user_id]);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-0">Rental History</h1>
                    <p class="text-muted mb-0">View your complete rental history and statistics</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="display-6"><?php echo $stats['total_rentals']; ?></h3>
                            <p class="mb-0">Total Rentals</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="display-6"><?php echo $stats['active_rentals']; ?></h3>
                            <p class="mb-0">Active Rentals</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="display-6">ETB <?php echo number_format($stats['total_rent_value'], 0); ?></h3>
                            <p class="mb-0">Total Rent Value</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3 class="display-6">ETB <?php echo number_format($stats['avg_rent'], 0); ?></h3>
                            <p class="mb-0">Average Rent</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter History</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $filters['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo $filters['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="expired" <?php echo $filters['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                    <option value="terminated" <?php echo $filters['status'] == 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Property Type</label>
                                <select name="property_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="house" <?php echo $filters['property_type'] == 'house' ? 'selected' : ''; ?>>House</option>
                                    <option value="apartment" <?php echo $filters['property_type'] == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                    <option value="villa" <?php echo $filters['property_type'] == 'villa' ? 'selected' : ''; ?>>Villa</option>
                                    <option value="condominium" <?php echo $filters['property_type'] == 'condominium' ? 'selected' : ''; ?>>Condominium</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo $filters['date_from']; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo $filters['date_to']; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                    <a href="rental-history.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rental History Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Rental History</h5>
                    <div>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="exportHistory()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($rental_history)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Rental History</h5>
                            <p class="text-muted">You haven't rented any properties yet.</p>
                            <a href="search.php" class="btn btn-primary">Find Properties</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Duration</th>
                                        <th>Monthly Rent</th>
                                        <th>Total Paid</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rental_history as $rental): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($rental['title']); ?></strong>
                                                <br><small class="text-muted">Owner: <?php echo htmlspecialchars($rental['owner_name']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($rental['location_name']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucfirst($rental['property_type']); ?></span>
                                            </td>
                                            <td>
                                                <small><?php echo date('M d, Y', strtotime($rental['start_date'])); ?></small>
                                                <br>
                                                <small><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></small>
                                                <br><strong><?php echo (int)($rental['duration_months'] ?? 0); ?> months</strong>
                                            </td>
                                            <td>
                                                <strong class="text-primary">ETB <?php echo number_format($rental['monthly_rent'], 0); ?></strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">ETB <?php echo number_format($rental['total_paid'] ?? 0, 0); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $rental['status'] == 'active' ? 'success' : ($rental['status'] == 'completed' ? 'primary' : ($rental['status'] == 'expired' ? 'warning' : 'danger')); ?>">
                                                    <?php echo ucfirst($rental['status']); ?>
                                                </span>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payments Modal -->
<div class="modal fade" id="paymentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // View payment history for a rental
    window.viewPayments = function(agreementId) {
        $.ajax({
            url: '../api/get-rental-payments.php',
            method: 'GET',
            data: { agreement_id: agreementId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = `
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    if (response.payments.length === 0) {
                        html += '<tr><td colspan="5" class="text-center">No payments found</td></tr>';
                    } else {
                        response.payments.forEach(function(payment) {
                            html += `
                                <tr>
                                    <td>${new Date(payment.created_at).toLocaleDateString()}</td>
                                    <td>ETB ${Number(payment.amount).toLocaleString()}</td>
                                    <td>${payment.payment_method}</td>
                                    <td><span class="badge bg-${payment.status == 'completed' ? 'success' : 'warning'}">${payment.status}</span></td>
                                    <td>${payment.status == 'completed' ? '<a href="receipt.php?id=' + payment.payment_id + '" class="btn btn-sm btn-outline-primary">Download</a>' : '-'}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <strong>Total Paid: ETB ${response.total_paid.toLocaleString()}</strong>
                        </div>
                    `;
                    
                    $('#paymentsContent').html(html);
                    $('#paymentsModal').modal('show');
                } else {
                    alert('Error loading payment history');
                }
            },
            error: function() {
                alert('Error loading payment history');
            }
        });
    };
    
    // Export rental history
    window.exportHistory = function() {
        window.location.href = '../api/export-rental-history.php?' + new URLSearchParams(window.location.search);
    };
});
</script>
