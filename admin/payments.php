<?php
require_once '../includes/config.php';
$title = "Payment Management - Admin Dashboard";

// Require admin login
$session->requireRole('admin');

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$payment_method_filter = $_GET['payment_method'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($payment_method_filter)) {
    $where_conditions[] = "p.payment_method = ?";
    $params[] = $payment_method_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(p.created_at) = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(p.transaction_id LIKE ? OR u.full_name LIKE ? OR p.notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM payments p 
              LEFT JOIN users u ON p.tenant_id = u.user_id 
              LEFT JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id 
              LEFT JOIN properties prop ON ra.property_id = prop.property_id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$total_result = $db->getSingle($stmt, $params);
$total_payments = $total_result['total'];
$total_pages = ceil($total_payments / $limit);

// Get payments with pagination
$sql = "SELECT p.*, u.full_name as tenant_name, u.email as tenant_email,
               ra.agreement_id, prop.title as property_title, prop.property_id,
               ra.start_date, ra.end_date
        FROM payments p 
        LEFT JOIN users u ON p.tenant_id = u.user_id 
        LEFT JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id 
        LEFT JOIN properties prop ON ra.property_id = prop.property_id 
        $where_clause
        ORDER BY p.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$payments = $db->getMultiple($stmt, $params);

// Get statistics
$stats_sql = "SELECT 
                 COUNT(*) as total,
                 COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                 COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                 COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                 SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                 SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'completed' THEN amount ELSE 0 END) as monthly_revenue
              FROM payments";
$stmt = $db->prepare($stats_sql);
$stats = $db->getSingle($stmt);

include '../includes/header.php';
?>

<!-- Payment Management Content -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Payment Management</h1>
                <div>
                    <span class="badge bg-info">Admin</span>
                    <span class="text-muted">Financial Overview</span>
                </div>
            </div>
            
            <!-- Payment Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-money-bill-wave fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                    <p class="mb-0">Total Payments</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <small class="text-white-50">
                                <i class="fas fa-check-circle me-1"></i><?php echo $stats['completed']; ?> Completed
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h3 class="mb-0"><?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                    <p class="mb-0">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <small class="text-white-50">
                                <i class="fas fa-calendar me-1"></i><?php echo number_format($stats['monthly_revenue'], 2); ?> This Month
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h3 class="mb-0"><?php echo $stats['pending']; ?></h3>
                                    <p class="mb-0">Pending</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <small class="text-white-50">
                                <i class="fas fa-hourglass-half me-1"></i>Awaiting Verification
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h3 class="mb-0"><?php echo $stats['failed']; ?></h3>
                                    <p class="mb-0">Failed</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <small class="text-white-50">
                                <i class="fas fa-times-circle me-1"></i>Payment Issues
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filter & Search Payments
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Transaction ID, tenant, notes..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="">All Methods</option>
                                <option value="telebirr" <?php echo $payment_method_filter === 'telebirr' ? 'selected' : ''; ?>>Telebirr</option>
                                <option value="cbe" <?php echo $payment_method_filter === 'cbe' ? 'selected' : ''; ?>>CBE</option>
                                <option value="awash" <?php echo $payment_method_filter === 'awash' ? 'selected' : ''; ?>>Awash Bank</option>
                                <option value="dashen" <?php echo $payment_method_filter === 'dashen' ? 'selected' : ''; ?>>Dashen Bank</option>
                                <option value="boa" <?php echo $payment_method_filter === 'boa' ? 'selected' : ''; ?>>BOA</option>
                                <option value="cash" <?php echo $payment_method_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary text-nowrap">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php if (!empty($status_filter) || !empty($payment_method_filter) || !empty($date_filter) || !empty($search)): ?>
                        <div class="mt-2">
                            <a href="payments.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="exportPayments()">
                            <i class="fas fa-download me-2"></i>Export CSV
                        </button>
                        <button type="button" class="btn btn-info" onclick="generateReport()">
                            <i class="fas fa-file-alt me-2"></i>Generate Report
                        </button>
                        <button type="button" class="btn btn-warning" onclick="sendReminders()">
                            <i class="fas fa-bell me-2"></i>Send Reminders
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Payments Table -->
            <style>
                /* Ensure wide tables can scroll on small screens */
                .table-responsive { overflow-x: auto !important; }
                .table-responsive table th, .table-responsive table td { white-space: nowrap; }
            </style>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Payment Transactions
                        <span class="badge bg-secondary ms-2"><?php echo $total_payments; ?> Total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-money-bill-wave fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No payments found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                        <th><i class="fas fa-user me-1"></i>Tenant</th>
                                        <th><i class="fas fa-home me-1"></i>Property</th>
                                        <th><i class="fas fa-money-bill me-1"></i>Amount</th>
                                        <th><i class="fas fa-credit-card me-1"></i>Method</th>
                                        <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                        <th><i class="fas fa-calendar me-1"></i>Date</th>
                                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">#<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($payment['transaction_id'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($payment['tenant_name'] ?? ''); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['tenant_email'] ?? ''); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($payment['property_title'] ?? ''); ?></strong>
                                                    <br>
                                                    <small class="text-muted">ID: <?php echo $payment['property_id']; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($payment['amount'], 2); ?></strong>
                                                <br>
                                                <small class="text-muted">ETB</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucfirst($payment['payment_method']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getPaymentStatusColor($payment['status']); ?>">
                                                    <i class="fas fa-<?php echo getPaymentStatusIcon($payment['status']); ?> me-1"></i>
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo formatDate($payment['created_at'], 'M d, H:i'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" onclick="viewPayment(<?php echo $payment['payment_id']; ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info" onclick="sendReceipt(<?php echo $payment['payment_id']; ?>)" title="Send Receipt">
                                                        <i class="fas fa-paper-plane"></i> Send Receipt
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Payments pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($payment_method_filter) ? '&payment_method=' . urlencode($payment_method_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($payment_method_filter) ? '&payment_method=' . urlencode($payment_method_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($payment_method_filter) ? '&payment_method=' . urlencode($payment_method_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                Next
                                            </a>
                                        </li>
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

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetails">
                <!-- Payment details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Helper functions
function getPaymentStatusColor($status) {
    $colors = [
        'completed' => 'success',
        'pending' => 'warning',
        'failed' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getPaymentStatusIcon($status) {
    $icons = [
        'completed' => 'check-circle',
        'pending' => 'clock',
        'failed' => 'times-circle'
    ];
    return $icons[$status] ?? 'question-circle';
}
?>

<script>
function viewPayment(paymentId) {
    fetch('../api/payment-details.php?payment_id=' + paymentId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const payment = data.payment;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Payment ID:</strong> #${payment.payment_id}<br>
                            <strong>Transaction ID:</strong> ${payment.transaction_id}<br>
                            <strong>Amount:</strong> ${parseFloat(payment.amount).toFixed(2)} ETB<br>
                            <strong>Method:</strong> ${payment.payment_method}<br>
                            <strong>Status:</strong> ${payment.status}<br>
                            <strong>Date:</strong> ${new Date(payment.created_at).toLocaleString()}
                        </div>
                        <div class="col-md-6">
                            <strong>Tenant:</strong> ${payment.tenant_name}<br>
                            <strong>Email:</strong> ${payment.tenant_email}<br>
                            <strong>Property:</strong> ${payment.property_title}<br>
                            <strong>Agreement ID:</strong> ${payment.agreement_id}<br>
                            <strong>Notes:</strong> ${payment.notes || 'N/A'}
                        </div>
                    </div>
                `;
                document.getElementById('paymentDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            } else {
                alert('Error loading payment details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading payment details');
        });
}

function verifyPayment(paymentId) {
    if (confirm('Are you sure you want to verify this payment?')) {
        fetch('../api/verify-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                payment_id: paymentId,
                action: 'verify'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment verified successfully!');
                location.replace(window.location.href);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error verifying payment');
        });
    }
}

function rejectPayment(paymentId) {
    if (confirm('Are you sure you want to reject this payment?')) {
        fetch('../api/verify-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                payment_id: paymentId,
                action: 'reject'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment rejected successfully!');
                location.replace(window.location.href);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error rejecting payment');
        });
    }
}

function sendReceipt(paymentId) {
    const email = prompt('Enter email address to send receipt:');
    if (email) {
        fetch('../api/send-receipt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                payment_id: paymentId,
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Receipt sent successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending receipt');
        });
    }
}

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('payments.php?' + params.toString(), '_blank');
}

function generateReport() {
    alert('Payment report generation would be implemented here');
}

function sendReminders() {
    if (confirm('Send payment reminders to all tenants with pending payments?')) {
        fetch('../api/send-payment-reminders.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminders sent successfully! ' + data.count + ' reminders sent.');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending reminders');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
