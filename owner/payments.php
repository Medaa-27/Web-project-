<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = 'Payments - Aksum House Rental System';

$owner_id = $session->getUserId();
$csrf_token = generateCSRFToken();

$status_filter = $_GET['status'] ?? 'all';
if (!in_array($status_filter, ['all', 'pending', 'completed', 'failed', 'cancelled'], true)) {
    $status_filter = 'all';
}

$sql = "SELECT pay.*, pr.title as property_title, pr.address, l.location_name,
               u.full_name as tenant_name, ra.start_date, ra.end_date
        FROM payments pay
        LEFT JOIN rental_agreements ra ON pay.agreement_id = ra.agreement_id
        LEFT JOIN properties pr ON pay.property_id = pr.property_id
        LEFT JOIN locations l ON pr.location_id = l.location_id
        JOIN users u ON pay.tenant_id = u.user_id
        WHERE (pr.owner_id = ? OR ra.agreement_id IN (SELECT agreement_id FROM rental_agreements ra2 JOIN properties pr2 ON ra2.property_id = pr2.property_id WHERE pr2.owner_id = ?))";
$params = [$owner_id, $owner_id];

if ($status_filter !== 'all') {
    $sql .= " AND pay.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY pay.created_at DESC";
$stmt = $db->prepare($sql);
$payments = $db->getMultiple($stmt, $params);

function getOwnerReceivedAmount(array $payment): float {
    if (isset($payment['owner_receives']) && $payment['owner_receives'] !== null && $payment['owner_receives'] !== '') {
        return (float)$payment['owner_receives'];
    }

    if (in_array($payment['payment_for'], ['rent', 'advance'], true) && isset($payment['amount'])) {
        $gross = (float)$payment['amount'];
        if ($gross > 0) {
            $baseRent = $gross / 1.03;
            return $baseRent * 0.95;
        }
    }

    return (float)($payment['amount'] ?? 0);
}

$stats = [
    'total_amount' => 0,
    'pending' => 0,
    'completed' => 0
];
foreach ($payments as $p) {
    if ($p['status'] === 'pending') {
        $stats['pending']++;
    }
    if ($p['status'] === 'completed') {
        $stats['completed']++;
        $stats['total_amount'] += getOwnerReceivedAmount($p);
    }
}

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
                            <h1 class="h3 mb-0">Payments</h1>
                            <p class="text-muted mb-0">Track payments for your properties</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <h6 class="mb-0 text-success" id="total-paid-display">ETB <?php echo number_format((float)$stats['total_amount'], 0); ?></h6>
                            <small class="text-muted">Total (filtered)</small>
                            <input type="hidden" id="current-total-amount" value="<?php echo $stats['total_amount']; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-8 text-end">
                            <a href="payments.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Property</th>
                                    <th>Tenant</th>
                                    <th>For</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No payments found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($p['payment_date'])); ?>
                                                <div class="small text-muted"><?php echo date('H:i', strtotime($p['created_at'])); ?></div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($p['property_title']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($p['location_name'] ?? ''); ?></div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($p['tenant_name']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php 
                                                    $for_labels = [
                                                        'rent' => 'Monthly Rent',
                                                        'deposit' => 'Security Deposit',
                                                        'advance' => 'Advance Payment',
                                                        'utility' => 'Utility Bill',
                                                        'penalty' => 'Penalty',
                                                        'other' => 'Other'
                                                    ];
                                                    echo htmlspecialchars($for_labels[$p['payment_for']] ?? ucfirst($p['payment_for'])); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $netAmount = getOwnerReceivedAmount($p);
                                                $showGross = in_array($p['payment_for'], ['rent', 'advance'], true) && $p['status'] === 'completed' && $netAmount !== (float)$p['amount'];
                                                ?>
                                                <strong class="text-success">ETB <?php echo number_format($netAmount, 0); ?></strong>
                                                <?php if ($showGross): ?>
                                                    <br><small class="text-muted">Gross: ETB <?php echo number_format((float)$p['amount'], 0); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['payment_method']); ?></span></td>
                                            <td>
                                                <?php
                                                $badge = [
                                                    'pending' => 'warning',
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                    'cancelled' => 'secondary'
                                                ];
                                                ?>
                                                <span id="status-badge-<?php echo $p['payment_id']; ?>" class="badge bg-<?php echo $badge[$p['status']] ?? 'secondary'; ?>"><?php echo htmlspecialchars($p['status']); ?></span>
                                            </td>
                                            <td id="action-cell-<?php echo $p['payment_id']; ?>">
                                                <?php if ($p['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" onclick="ownerVerifyPayment(<?php echo (int)$p['payment_id']; ?>)">
                                                        <i class="fas fa-check me-1"></i>Verify
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="ownerRejectPayment(<?php echo (int)$p['payment_id']; ?>)">
                                                        <i class="fas fa-times me-1"></i>Decline
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Processed</span>
                                                <?php endif; ?>
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

<script>
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

function formatCurrency(amount) {
    return 'ETB ' + new Intl.NumberFormat('en-US').format(amount);
}

function updateUIDynamically(paymentId, newStatus, amount = 0) {
    // Update Status Badge
    const badge = document.getElementById('status-badge-' + paymentId);
    if (badge) {
        badge.textContent = newStatus;
        badge.className = 'badge bg-' + (newStatus === 'completed' ? 'success' : (newStatus === 'failed' ? 'danger' : 'secondary'));
    }

    // Update Action Cell
    const actionCell = document.getElementById('action-cell-' + paymentId);
    if (actionCell) {
        actionCell.innerHTML = '<span class="text-muted">Processed</span>';
    }

    // Update Total Amount if approved
    if (newStatus === 'completed' && amount > 0) {
        const totalDisplay = document.getElementById('total-paid-display');
        const totalInput = document.getElementById('current-total-amount');
        if (totalDisplay && totalInput) {
            let currentTotal = parseFloat(totalInput.value);
            let newTotal = currentTotal + parseFloat(amount);
            totalInput.value = newTotal;
            totalDisplay.textContent = formatCurrency(newTotal);
        }
    }
}

function ownerVerifyPayment(paymentId, amount) {
    console.log('Verifying payment:', paymentId, 'Amount:', amount);
    if (!confirm('Are you sure you want to approve this payment?')) return;
    
    fetch('../api/verify-payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({ 
            payment_id: paymentId, 
            action: 'approve',
            csrf_token: CSRF_TOKEN
        }),
        credentials: 'same-origin'
    })
    .then(res => {
        console.log('Response status:', res.status);
        if (!res.ok) {
            return res.text().then(text => { 
                console.error('Error response text:', text);
                throw new Error('HTTP ' + res.status + ': ' + text); 
            });
        }
        return res.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            location.replace(window.location.href);
        } else {
            console.error('Verification failed:', data.message);
            alert('Error: ' + (data.message || 'Unable to verify payment.'));
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert('Error verifying payment. ' + err.message);
    });
}

function ownerRejectPayment(paymentId) {
    console.log('Rejecting payment:', paymentId);
    if (!confirm('Are you sure you want to reject this payment?')) return;
    const notes = prompt('Please enter a reason for rejection (optional):');
    if (notes === null) return; // User cancelled prompt

    fetch('../api/verify-payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({ 
            payment_id: paymentId, 
            action: 'reject',
            notes: notes,
            csrf_token: CSRF_TOKEN
        }),
        credentials: 'same-origin'
    })
    .then(res => {
        console.log('Response status:', res.status);
        if (!res.ok) {
            return res.text().then(text => { 
                console.error('Error response text:', text);
                throw new Error('HTTP ' + res.status + ': ' + text); 
            });
        }
        return res.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            location.replace(window.location.href);
        } else {
            console.error('Rejection failed:', data.message);
            alert('Error: ' + (data.message || 'Unable to reject payment.'));
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert('Error rejecting payment. ' + err.message);
    });
}
</script>

<?php include '../includes/footer.php'; ?>
