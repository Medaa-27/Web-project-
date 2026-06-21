<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Payment History - Aksum Rental System";

$user_id = $session->getUserId();

$clear_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_history'])) {
    $stmt = $db->prepare("DELETE pt FROM payment_transactions pt JOIN payments p ON pt.payment_id = p.payment_id WHERE p.tenant_id = ?");
    $db->execute($stmt, [$user_id]);

    $stmt = $db->prepare("DELETE FROM payments WHERE tenant_id = ?");
    $db->execute($stmt, [$user_id]);

    $clear_message = 'Payment history has been cleared successfully.';
}

// Get all payments with property details and enhanced tracking
$sql = "SELECT p.*, prop.title as property_title, prop.monthly_rent, l.location_name,
               ra.agreement_id, ra.start_date, ra.end_date, ra.status as agreement_status,
               p.payment_type, p.total_amount, p.amount_paid, p.balance_remaining,
               p.transaction_reference, p.payment_status, p.verified_at,
               pt.gateway_provider, pt.gateway_transaction_id
        FROM payments p
        JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
        JOIN properties prop ON ra.property_id = prop.property_id
        LEFT JOIN locations l ON prop.location_id = l.location_id
        LEFT JOIN payment_transactions pt ON p.payment_id = pt.payment_id
        WHERE p.tenant_id = ?
        ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$payments = $db->getMultiple($stmt, [$user_id]);

// Get statistics with enhanced metrics
$stats = [
    'total' => count($payments),
    'total_amount' => array_sum(array_column($payments, 'amount')),
    'verified' => 0,
    'pending' => 0,
    'failed' => 0,
    'this_month' => 0,
    'this_month_amount' => 0,
    'full_payments' => 0,
    'minimum_payments' => 0,
    'monthly_payments' => 0,
    'total_balance_remaining' => 0
];

$current_month = date('Y-m');
foreach ($payments as $payment) {
    switch ($payment['payment_status']) {
        case 'Verified':
            $stats['verified']++;
            break;
        case 'Pending':
            $stats['pending']++;
            break;
        case 'Failed':
            $stats['failed']++;
            break;
    }
    
    switch ($payment['payment_type']) {
        case 'FULL':
            $stats['full_payments']++;
            break;
        case 'MINIMUM':
            $stats['minimum_payments']++;
            break;
        case 'MONTHLY':
            $stats['monthly_payments']++;
            break;
    }
    
    if (date('Y-m', strtotime($payment['created_at'])) === $current_month) {
        $stats['this_month']++;
        $stats['this_month_amount'] += $payment['amount'];
    }
    
    $stats['total_balance_remaining'] += ($payment['balance_remaining'] ?? 0);
}

// Filter by agreement if specified
$agreement_filter = $_GET['agreement'] ?? '';
if ($agreement_filter && is_numeric($agreement_filter)) {
    $payments = array_filter($payments, function($payment) use ($agreement_filter) {
        return $payment['agreement_id'] == $agreement_filter;
    });
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">Payment History</h1>
                            <p class="text-muted mb-0">View your complete payment history and receipts</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <form method="post" class="d-inline" onsubmit="return confirm('Clear all payment history? This action cannot be undone.');">
                                <button type="submit" name="clear_history" class="btn btn-outline-danger me-2">
                                    <i class="fas fa-trash-alt me-2"></i>Clear History
                                </button>
                            </form>
                            <a href="payments.php" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i>Make Payment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!empty($clear_message)): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($clear_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-primary bg-opacity-10 text-primary mx-auto">
                                <i class="fas fa-receipt fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['total']; ?></h3>
                            <p class="text-muted mb-0">Total Payments</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['verified']; ?></h3>
                            <p class="text-muted mb-0">Verified</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['pending']; ?></h3>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['this_month']; ?></h3>
                            <p class="text-muted mb-0">This Month</p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Total Amount Summary -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-gradient-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title mb-1">Total Amount Paid</h5>
                                    <p class="mb-0">All time payment total</p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <h3 class="mb-0">ETB <?php echo number_format($stats['total_amount'], 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($stats['this_month_amount'] !== $stats['total_amount']): ?>
                    <div class="col-md-6">
                        <div class="card bg-gradient-success text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-1">This Month</h5>
                                        <p class="mb-0">Payments in <?php echo date('F Y'); ?></p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h3 class="mb-0">ETB <?php echo number_format($stats['this_month_amount'], 0); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment History</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                            <option value="">All Status</option>
                            <option value="Verified">Verified</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                        </select>
                        <select class="form-select form-select-sm" id="typeFilter" style="width: auto;">
                            <option value="">All Types</option>
                            <option value="FULL">Full Payment</option>
                            <option value="MINIMUM">Minimum Payment</option>
                            <option value="MONTHLY">Monthly Payment</option>
                        </select>
                        <select class="form-select form-select-sm" id="monthFilter" style="width: auto;">
                            <option value="">All Time</option>
                            <option value="this-month">This Month</option>
                            <option value="last-month">Last Month</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Payment History</h5>
                            <p class="text-muted">You haven't made any payments yet.</p>
                            <a href="payments.php" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i>Make Your First Payment
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Property</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Transaction</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): 
                                        $status_color = ($payment['payment_status'] ?? '') === 'Verified' ? 'success' : 'warning';
                                        $status_icon = ($payment['payment_status'] ?? '') === 'Verified' ? 'check' : 'clock';
                                    ?>
                                        <tr data-status="<?php echo $payment['payment_status']; ?>" data-type="<?php echo $payment['payment_type']; ?>" data-date="<?php echo date('Y-m', strtotime($payment['created_at'])); ?>">
                                            <td>
                                                <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                                <br><small class="text-muted"><?php echo date('H:i', strtotime($payment['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($payment['property_title']); ?></strong>
                                                <br><small class="text-muted">Agreement #<?php echo str_pad($payment['agreement_id'], 6, '0', STR_PAD_LEFT); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $type_labels = [
                                                    'FULL' => 'Full Payment',
                                                    'MINIMUM' => 'Minimum',
                                                    'MONTHLY' => 'Monthly'
                                                ];
                                                $type_colors = [
                                                    'FULL' => 'success',
                                                    'MINIMUM' => 'warning',
                                                    'MONTHLY' => 'primary'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $type_colors[$payment['payment_type']] ?? 'secondary'; ?>">
                                                    <?php echo $type_labels[$payment['payment_type']] ?? $payment['payment_type']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-success">ETB <?php echo number_format($payment['amount'], 0); ?></strong>
                                                <?php if ($payment['total_amount'] && $payment['total_amount'] != $payment['amount']): ?>
                                                    <br><small class="text-muted">of ETB <?php echo number_format($payment['total_amount'], 0); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (($payment['balance_remaining'] ?? 0) > 0): ?>
                                                    <span class="text-warning">ETB <?php echo number_format($payment['balance_remaining'], 0); ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">Paid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo ucfirst($payment['payment_method']); ?></span>
                                                <?php if ($payment['gateway_provider']): ?>
                                                    <br><small class="text-muted"><?php echo $payment['gateway_provider']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_colors = [
                                                    'Verified' => 'success',
                                                    'Pending' => 'warning',
                                                    'Failed' => 'danger',
                                                    'Cancelled' => 'danger'
                                                ];
                                                $status_icons = [
                                                    'Verified' => 'check-circle',
                                                    'Pending' => 'clock',
                                                    'Failed' => 'times-circle',
                                                    'Cancelled' => 'times-circle'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_colors[$payment['payment_status']] ?? 'secondary'; ?>">
                                                    <i class="fas fa-<?php echo $status_icons[$payment['payment_status']] ?? 'question-circle'; ?> me-1"></i>
                                                    <?php echo $payment['payment_status']; ?>
                                                </span>
                                                <?php if ($payment['verified_at']): ?>
                                                    <br><small class="text-muted">Verified: <?php echo date('M d', strtotime($payment['verified_at'])); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['transaction_reference']): ?>
                                                    <small class="text-monospace">#<?php echo substr($payment['transaction_reference'], -8); ?></small>
                                                    <?php if ($payment['gateway_transaction_id']): ?>
                                                        <br><small class="text-muted">GW: <?php echo substr($payment['gateway_transaction_id'], -8); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['payment_status'] == 'Verified'): ?>
                                                    <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>&download=1" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-download"></i> Receipt
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="viewPaymentDetails(<?php echo $payment['payment_id']; ?>)" disabled>
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="Payment history pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Content will be loaded via JavaScript -->
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
    // Status filter
    $('#statusFilter').change(function() {
        filterPayments();
    });

    // Type filter
    $('#typeFilter').change(function() {
        filterPayments();
    });

    // Month filter
    $('#monthFilter').change(function() {
        filterPayments();
    });

    function filterPayments() {
        const status = $('#statusFilter').val();
        const type = $('#typeFilter').val();
        const month = $('#monthFilter').val();
        
        $('#paymentsTable tbody tr').each(function() {
            let show = true;
            
            // Filter by status
            if (status && $(this).data('status') !== status) {
                show = false;
            }
            
            // Filter by type
            if (type && $(this).data('type') !== type) {
                show = false;
            }
            
            // Filter by month
            if (month === 'this-month') {
                const currentMonth = new Date().toISOString().slice(0, 7);
                if ($(this).data('date') !== currentMonth) {
                    show = false;
                }
            } else if (month === 'last-month') {
                const lastMonth = new Date();
                lastMonth.setMonth(lastMonth.getMonth() - 1);
                const lastMonthStr = lastMonth.toISOString().slice(0, 7);
                if ($(this).data('date') !== lastMonthStr) {
                    show = false;
                }
            }
            
            $(this).toggle(show);
        });
    }

    // View payment details
    window.viewPaymentDetails = function(paymentId) {
        const payments = <?php echo json_encode($payments); ?>;
        const payment = payments.find(p => p.payment_id == paymentId);
        
        if (payment) {
            const content = `
                <div class="row">
                    <div class="col-12">
                        <h6>Payment Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Payment ID:</strong></td>
                                <td>#${String(payment.payment_id).padStart(6, '0')}</td>
                            </tr>
                            <tr>
                                <td><strong>Transaction Reference:</strong></td>
                                <td><code>${payment.transaction_reference || 'N/A'}</code></td>
                            </tr>
                            <tr>
                                <td><strong>Date:</strong></td>
                                <td>${new Date(payment.created_at).toLocaleString()}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td class="text-success"><strong>ETB ${Number(payment.amount).toLocaleString()}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Type:</strong></td>
                                <td><span class="badge bg-primary">${payment.payment_type}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="badge bg-${payment.payment_status === 'Verified' ? 'success' : payment.payment_status === 'Pending' ? 'warning' : 'danger'}">${payment.payment_status}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Method:</strong></td>
                                <td>${payment.payment_method} ${payment.gateway_provider ? `(${payment.gateway_provider})` : ''}</td>
                            </tr>
                            ${payment.total_amount && payment.total_amount != payment.amount ? `
                            <tr>
                                <td><strong>Total Agreement:</strong></td>
                                <td>ETB ${Number(payment.total_amount).toLocaleString()}</td>
                            </tr>
                            <tr>
                                <td><strong>Balance Remaining:</strong></td>
                                <td class="${payment.balance_remaining > 0 ? 'text-warning' : 'text-success'}">ETB ${Number(payment.balance_remaining || 0).toLocaleString()}</td>
                            </tr>` : ''}
                        </table>
                        
                        <h6 class="mt-3">Property Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Property:</strong></td>
                                <td>${payment.property_title}</td>
                            </tr>
                            <tr>
                                <td><strong>Agreement ID:</strong></td>
                                <td>#${String(payment.agreement_id).padStart(6, '0')}</td>
                            </tr>
                            <tr>
                                <td><strong>Monthly Rent:</strong></td>
                                <td>ETB ${Number(payment.monthly_rent).toLocaleString()}</td>
                            </tr>
                        </table>
                        
                        ${payment.notes ? `
                        <h6 class="mt-3">Notes</h6>
                        <div class="bg-light p-2 rounded">
                            ${payment.notes}
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            $('#paymentDetailsContent').html(content);
            $('#paymentDetailsModal').modal('show');
        }
    };
});
</script>
