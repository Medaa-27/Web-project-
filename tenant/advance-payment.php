<?php
require_once '../includes/init.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: ../login.php');
    exit;
}

$tenant_id = $_SESSION['user_id'];
$message = '';
$payment_details = null;

// Handle payment creation
if (isset($_POST['create_payment']) && isset($_POST['property_id']) && isset($_POST['amount'])) {
    $property_id = (int)$_POST['property_id'];
    $amount = (float)$_POST['amount'];

    // Check if payment already exists
    if (!hasPendingAdvancePayment($tenant_id, $property_id)) {
        $payment_id = createAdvancePayment($tenant_id, $property_id, $amount);
        if ($payment_id) {
            $message = '<div class="alert alert-success">Advance payment record created successfully. You can now process the payment.</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to create payment record. Please try again.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">You already have a pending payment for this property.</div>';
    }
}
if (isset($_POST['process_payment']) && isset($_POST['payment_id'])) {
    $payment_id = (int)$_POST['payment_id'];

    // Verify the payment belongs to this tenant
    $payment = getAdvancePayment($payment_id);
    if ($payment && $payment['tenant_id'] == $tenant_id) {
        $result = processAdvancePayment($payment_id);
        if ($result['success']) {
            $message = '<div class="alert alert-success">' . $result['message'] . '</div>';
        } else {
            $message = '<div class="alert alert-danger">' . $result['message'] . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Invalid payment request.</div>';
    }
}

// Get approved rental requests that need advance payment
$approved_requests = [];
$sql = "SELECT rr.request_id, rr.property_id, p.property_name, p.monthly_rent, ra.advance_payment
        FROM rental_requests rr
        JOIN properties p ON rr.property_id = p.property_id
        LEFT JOIN rental_agreements ra ON rr.request_id = ra.request_id
        WHERE rr.tenant_id = ? AND rr.status = 'approved'
        AND NOT EXISTS (
            SELECT 1 FROM advance_payments ap
            WHERE ap.tenant_id = rr.tenant_id
            AND ap.property_id = rr.property_id
            AND ap.status IN ('paid', 'pending')
        )";
$stmt = $db->prepare($sql);
$approved_requests = $db->getMultiple($stmt, [$tenant_id]);

$page_title = 'Advance Payment';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-credit-card me-2"></i>Advance Payment</h4>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>

                    <?php if (empty($approved_requests)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No pending advance payments. All your approved rental requests have been processed.
                        </div>
                    <?php else: ?>
                        <p class="mb-4">You have approved rental requests that require a 20% advance payment. Please complete the payment to proceed with your rental agreement.</p>

                        <?php foreach ($approved_requests as $request): ?>
                            <div class="card mb-3 border-primary">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($request['property_name']); ?></h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Monthly Rent:</strong> <?php echo formatCurrency($request['monthly_rent']); ?></p>
                                            <p class="mb-1"><strong>Advance Payment (20%):</strong> <?php echo formatCurrency($request['advance_payment']); ?></p>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="property_id" value="<?php echo $request['property_id']; ?>">
                                                <input type="hidden" name="amount" value="<?php echo $request['advance_payment']; ?>">
                                                <button type="submit" name="create_payment" class="btn btn-primary">
                                                    <i class="fas fa-credit-card me-2"></i>Pay Advance
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php
                    // Show existing payments
                    $payments = getTenantAdvancePayments($tenant_id);
                    if (!empty($payments)):
                    ?>
                        <hr>
                        <h5>Your Payment History</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Amount</th>
                                        <th>Reference Code</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['property_name']); ?></td>
                                            <td><?php echo formatCurrency($payment['amount']); ?></td>
                                            <td><code><?php echo htmlspecialchars($payment['reference_code']); ?></code></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $payment['status'] === 'paid' ? 'success' :
                                                         ($payment['status'] === 'pending' ? 'warning' : 'danger');
                                                ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $payment['payment_date'] ? formatDate($payment['payment_date']) : '-'; ?></td>
                                            <td>
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="payment_id" value="<?php echo $payment['payment_id']; ?>">
                                                        <button type="submit" name="process_payment" class="btn btn-sm btn-success"
                                                                onclick="return confirm('Are you sure you want to process this payment?')">
                                                            <i class="fas fa-play me-1"></i>Process
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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

<?php include '../includes/footer.php'; ?>