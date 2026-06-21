<?php
require_once '../includes/config.php';
$session->requireRole('tenant');

$payment_id = $_GET['id'] ?? '';
$user_id = $session->getUserId();

if (empty($payment_id) || !is_numeric($payment_id)) {
    header('Location: payment-history.php');
    exit;
}

// Get tenant user information
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$user = $db->getSingle($stmt, [$user_id]);

if (!$user) {
    header('Location: payment-history.php');
    exit;
}

// Get payment details with property and agreement information
$sql = "SELECT p.*, prop.title as property_title, prop.monthly_rent, l.location_name,
               ra.agreement_id, ra.start_date, ra.end_date, u.full_name as owner_name,
               u.email as owner_email, u.phone as owner_phone
        FROM payments p
        JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
        JOIN properties prop ON ra.property_id = prop.property_id
        LEFT JOIN locations l ON prop.location_id = l.location_id
        LEFT JOIN users u ON prop.owner_id = u.user_id
        WHERE p.payment_id = ? AND p.tenant_id = ?";
$stmt = $db->prepare($sql);
$payment = $db->getSingle($stmt, [$payment_id, $user_id]);

if (!$payment) {
    header('Location: payment-history.php');
    exit;
}

if (isset($_GET['download'])) {
    $filename = 'receipt_' . str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT) . '.doc';

    // Set headers for Word document download
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Generate Word document content (HTML formatted for Word)
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="utf-8"><title>Payment Receipt</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        .header { text-align: center; font-size: 24pt; font-weight: bold; color: #007bff; margin-bottom: 20pt; }
        .receipt-info { margin: 20pt 0; }
        .receipt-info table { width: 100%; border-collapse: collapse; }
        .receipt-info td { padding: 8pt; }
        .status-banner { margin: 30pt 0; padding: 15pt; border: 1pt solid; text-align: center; font-size: 14pt; font-weight: bold; }
        .status-verified { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .status-pending { background-color: #fff3cd; color: #856404; border-color: #ffeaa7; }
        .receipt-table { width: 100%; border-collapse: collapse; margin: 20pt 0; }
        .receipt-table th, .receipt-table td { border: 1pt solid #000; padding: 8pt; text-align: left; }
        .receipt-table th { background-color: #007bff; color: white; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .footer { margin-top: 40pt; text-align: center; font-style: italic; color: #666; padding-top: 20pt; border-top: 1pt solid #eee; }
    </style>';
    echo '</head><body>';

    echo '<div class="header">PAYMENT RECEIPT</div>';

    echo '<div class="receipt-info">';
    echo '<table>';
    echo '<tr>';
    echo '<td><strong>Receipt #:</strong> ' . str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT) . '</td>';
    echo '<td style="text-align: right;"><strong>Tenant:</strong> ' . htmlspecialchars($user['full_name']) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>Date:</strong> ' . date('F d, Y', strtotime($payment['created_at'])) . '</td>';
    echo '<td style="text-align: right;"><strong>Email:</strong> ' . htmlspecialchars($user['email']) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>Time:</strong> ' . date('h:i A', strtotime($payment['created_at'])) . '</td>';
    echo '<td></td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';

    if ($payment['payment_status'] === 'Verified') {
        echo '<div class="status-banner status-verified">';
        echo '<div>PAYMENT COMPLETED SUCCESSFULLY</div>';
        echo '<div style="font-size: 12pt; font-weight: normal; margin-top: 8pt;">This payment has been verified and recorded in our system.</div>';
        echo '</div>';
    } else {
        echo '<div class="status-banner status-pending">';
        echo '<div>PAYMENT AWAITING VERIFICATION</div>';
        echo '<div style="font-size: 12pt; font-weight: normal; margin-top: 8pt;">This payment is being processed and will be verified by the property owner.</div>';
        echo '</div>';
    }

    echo '<table class="receipt-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Description</th>';
    echo '<th>Details</th>';
    echo '<th>Amount</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    echo '<tr>';
    echo '<td>Property Rental</td>';
    echo '<td>' . htmlspecialchars($payment['property_title']) . '<br><span style="font-size: 10pt; color: #666;">' . htmlspecialchars($payment['location_name']) . '</span></td>';
    echo '<td style="text-align: right;">ETB ' . number_format($payment['amount'], 2) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Payment Period</td>';
    echo '<td>' . date('F Y', strtotime($payment['created_at'])) . '</td>';
    echo '<td></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Payment Method</td>';
    echo '<td>' . ucfirst($payment['payment_method']) . '</td>';
    echo '<td></td>';
    echo '</tr>';

    if (!empty($payment['transaction_id'])) {
        echo '<tr>';
        echo '<td>Transaction ID</td>';
        echo '<td>' . htmlspecialchars($payment['transaction_id']) . '</td>';
        echo '<td></td>';
        echo '</tr>';
    }

    echo '<tr class="total-row">';
    echo '<td colspan="2" style="text-align: right;"><strong>TOTAL PAID</strong></td>';
    echo '<td style="text-align: right;"><strong>ETB ' . number_format($payment['amount'], 2) . '</strong></td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<div class="footer">';
    echo '<p>Thank you for your payment. This is an official receipt from Aksum Rental System.</p>';
    echo '<p>For questions, please contact support@aksumrental.com</p>';
    echo '</div>';

    echo '</body></html>';
    exit;
}

include '../includes/header.php';
?>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #receipt-content, #receipt-content * {
        visibility: visible;
    }
    #receipt-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
}

.receipt-header {
    border-bottom: 2px solid #007bff;
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.receipt-title {
    font-size: 28px;
    font-weight: bold;
    color: #007bff;
    text-align: center;
    margin-bottom: 10px;
}

.receipt-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.receipt-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.receipt-table th,
.receipt-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

.receipt-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.receipt-footer {
    margin-top: 50px;
    text-align: center;
    font-style: italic;
    color: #666;
}

.total-row {
    font-weight: bold;
    background-color: #f8f9fa;
}

.watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 72px;
    color: rgba(0, 123, 255, 0.1);
    font-weight: bold;
    z-index: -1;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="no-print mb-3">
                <a href="payment-history.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Payment History
                </a>
                <button onclick="window.print()" class="btn btn-primary float-end">
                    <i class="fas fa-print me-2"></i>Print Receipt
                </button>
            </div>

            <div id="receipt-content" class="card">
                <div class="card-body p-5">
                    <div class="watermark">PAID</div>
                    
                    <!-- Receipt Header -->
                    <div class="receipt-header">
                        <div class="receipt-title">PAYMENT RECEIPT</div>
                        <div class="text-center text-muted">Official Payment Confirmation</div>
                    </div>

                    <!-- Receipt Information -->
                    <div class="receipt-info">
                        <div>
                            <strong>Receipt #:</strong> <?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?><br>
                            <strong>Date:</strong> <?php echo date('F d, Y', strtotime($payment['created_at'])); ?><br>
                            <strong>Time:</strong> <?php echo date('h:i A', strtotime($payment['created_at'])); ?>
                        </div>
                        <div class="text-end">
                            <strong>Tenant:</strong> <?php echo htmlspecialchars($user['full_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    </div>

                    <!-- Payment Details Table -->
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Details</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Property Rental</td>
                                <td>
                                    <?php echo htmlspecialchars($payment['property_title']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['location_name']); ?></small>
                                </td>
                                <td class="text-end">ETB <?php echo number_format($payment['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Payment Period</td>
                                <td>
                                    <?php 
                                    $payment_date = new DateTime($payment['created_at']);
                                    $month_year = $payment_date->format('F Y');
                                    echo $month_year;
                                    ?>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Payment Method</td>
                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                <td></td>
                            </tr>
                            <?php if ($payment['transaction_id']): ?>
                            <tr>
                                <td>Transaction ID</td>
                                <td><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                                <td></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="2" class="text-end"><strong>TOTAL PAID</strong></td>
                                <td class="text-end"><strong>ETB <?php echo number_format($payment['amount'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Property and Agreement Details -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6>Property Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Property:</strong></td>
                                    <td><?php echo htmlspecialchars($payment['property_title']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td><?php echo htmlspecialchars($payment['location_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Monthly Rent:</strong></td>
                                    <td>ETB <?php echo number_format($payment['monthly_rent'], 2); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Agreement Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Agreement ID:</strong></td>
                                    <td>#<?php echo str_pad($payment['agreement_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Period:</strong></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($payment['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($payment['end_date'])); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Owner:</strong></td>
                                    <td><?php echo htmlspecialchars($payment['owner_name']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <?php if ($payment['payment_status'] === 'Verified'): ?>
                        <div class="alert alert-success mt-4 text-center">
                            <h5 class="mb-2">
                                <i class="fas fa-check-circle me-2"></i>
                                PAYMENT COMPLETED SUCCESSFULLY
                            </h5>
                            <p class="mb-0">This payment has been verified and recorded in our system.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4 text-center">
                            <h5 class="mb-2">
                                <i class="fas fa-clock me-2"></i>
                                PAYMENT AWAITING VERIFICATION
                            </h5>
                            <p class="mb-0">This payment is being processed and will be verified by the property owner.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Receipt Footer -->
                    <div class="receipt-footer">
                        <p class="mb-1">Thank you for your payment!</p>
                        <p class="mb-1">This is an official receipt from Aksum Rental System</p>
                        <p class="mb-0">For questions, please contact support@aksumrental.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
