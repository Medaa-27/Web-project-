<?php

require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

$session->requireRole('tenant');

$title = "Make Payment - Aksum House Rental System";



$user_id = $session->getUserId();



// Get active rental agreements
$sql = "SELECT ra.*, p.title, p.monthly_rent, l.location_name, u.full_name as owner_name,
               ra.end_date, ra.security_deposit,
               (SELECT COUNT(*) FROM payments WHERE agreement_id = ra.agreement_id AND payment_for = 'rent' AND status = 'completed') as rent_payment_count
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE ra.tenant_id = ? AND ra.status IN ('active', 'partially_paid')
        ORDER BY ra.end_date ASC";
$stmt = $db->prepare($sql);
$active_agreements = $db->getMultiple($stmt, [$user_id]);



// Get payment history

$sql = "SELECT p.*, prop.title as property_title, l.location_name, 
               p.payment_method, p.payment_date, p.payment_status
        FROM payments p
        JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
        JOIN properties prop ON ra.property_id = prop.property_id
        LEFT JOIN locations l ON prop.location_id = l.location_id
        WHERE p.tenant_id = ?
        ORDER BY p.created_at DESC LIMIT 10";

$stmt = $db->prepare($sql);

$payment_history = $db->getMultiple($stmt, [$user_id]);



// Get pending payments

$sql = "SELECT ra.*, p.title, p.monthly_rent, l.location_name

        FROM rental_agreements ra

        JOIN properties p ON ra.property_id = p.property_id

        LEFT JOIN locations l ON p.location_id = l.location_id

        WHERE ra.tenant_id = ? AND ra.status = 'active'

        AND ra.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)

        AND ra.end_date >= CURDATE()

        ORDER BY ra.end_date ASC";

$stmt = $db->prepare($sql);

$pending_payments = $db->getMultiple($stmt, [$user_id]);

// Get wallet balance
$wallet_balance = getWalletBalance($user_id);

// Handle deletion
if (isset($_GET['delete_tx'])) {
    $del_id = (int)$_GET['delete_tx'];
    // Tenant delete only hides from tenant view
    $stmt = $db->prepare("UPDATE wallet_transactions wt 
                         JOIN wallets w ON wt.wallet_id = w.wallet_id
                         SET wt.is_visible_user = 0 
                         WHERE wt.transaction_id = ? AND w.user_id = ?");
    $db->execute($stmt, [$del_id, $user_id]);
    header("Location: payments.php?msg=Transaction hidden from view");
    exit;
}

// Get wallet transaction history
$sql = "SELECT wt.*, p.payment_method
        FROM wallet_transactions wt
        JOIN wallets w ON wt.wallet_id = w.wallet_id
        LEFT JOIN payments p ON wt.reference_table = 'payments' AND wt.reference_id = p.payment_id
        WHERE w.user_id = ? AND wt.is_visible_user = 1
        ORDER BY wt.created_at DESC LIMIT 20";
$stmt = $db->prepare($sql);
$wallet_history = $db->getMultiple($stmt, [$user_id]);

include '../includes/header.php';

// Check for deposit callback alerts
$depositAlert = '';
if (isset($_GET['deposit'])) {
    if ($_GET['deposit'] === 'success') {
        $depositAlert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> Money deposited successfully! Your wallet balance has been updated.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                         </div>';
    } elseif ($_GET['deposit'] === 'failed') {
        $depositAlert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-times-circle me-2"></i> Deposit failed or was cancelled. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                         </div>';
    }
}
?>

<style>
/* Professional Payment Page Styles */
.payment-page {
    background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
    padding: 0;
}

.payment-container {
    background: #f8f9fa;
    padding: 2rem 0;
}

.payment-header-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: none;
    margin-bottom: 2rem;
}

.payment-stats-card {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,123,255,0.2);
}

.rental-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: none;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.rental-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.payment-action-btn {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}

.payment-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,123,255,0.4);
}

.payment-history-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: none;
}

.payment-alert {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 15px rgba(255,193,7,0.2);
}

    .scrollable-content {
        padding-right: 10px;
    }

    .scrollable-content::-webkit-scrollbar {
        width: 6px;
    }

    .scrollable-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .scrollable-content::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 10px;
    }

    .scrollable-content::-webkit-scrollbar-thumb:hover {
        background: #0056b3;
    }

    @media (max-width: 768px) {
        .payment-container {
            padding: 1rem 0;
        }

        .payment-stats-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .rental-card {
            margin-bottom: 0.75rem;
        }
    }

    /* Payment Type Cards */
    .payment-type-card {
        border-radius: 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-bottom: 0.5rem;
    }

    .payment-type-card:hover {
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,123,255,0.15);
    }

    .payment-type-card.selected {
        border-color: #007bff;
        background-color: #f8f9ff;
        box-shadow: 0 10px 25px rgba(0,123,255,0.2);
    }

    .payment-icon {
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .payment-type-card:hover .payment-icon,
    .payment-type-card.selected .payment-icon {
        color: #007bff;
        transform: scale(1.1);
    }

    /* Modal Improvements */
    .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-header {
        border-radius: 20px 20px 0 0;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
    }

    .modal-header .btn-close {
        filter: invert(1);
    }

    .modal-body {
        padding: 2rem;
    }

    /* Loading Animation */
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Deposit Method Cards */
    .deposit-method-card {
        border-radius: 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }

    .deposit-method-card:hover {
        border-color: #28a745;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(40, 167, 69, 0.15);
    }

    .deposit-method-card.selected {
        border-color: #28a745;
        background-color: #f8fff9;
        box-shadow: 0 10px 25px rgba(40, 167, 69, 0.2);
    }

    .deposit-method-card .payment-icon {
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .deposit-method-card:hover .payment-icon,
    .deposit-method-card.selected .payment-icon {
        color: #28a745;
        transform: scale(1.1);
    }

    .deposit-method-card .form-check {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    .deposit-method-card .form-check-input {
        cursor: pointer;
    }

    .deposit-method-card .form-check-label {
        font-size: 0.75rem;
        cursor: pointer;
    }
</style>

<div class="main-content">
<div class="payment-page">
    <div class="payment-container">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3">
                    <?php include '../includes/sidebar.php'; ?>
                </div>

                <div class="col-lg-9">
                    <div class="scrollable-content">
                        <?php echo $depositAlert; ?>
                        <!-- Wallet Balance Card -->
                        <div class="payment-header-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="text-muted mb-1">Wallet Balance</h5>
                                        <h2 class="mb-0 text-primary"><?php echo number_format(max(0, $wallet_balance), 2); ?> ETB</h2>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button class="btn btn-success me-2" id="addMoneyBtn">
                                            <i class="fas fa-plus me-2"></i>Deposit
                                        </button>
                                        <button class="btn btn-outline-danger" id="withdrawMoneyBtn">
                                    <i class="fas fa-arrow-up me-2"></i>Withdraw
                                </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Payments Alert -->
                        <?php if (!empty($pending_payments)): ?>
                            <div class="alert payment-alert alert-warning alert-dismissible fade show mb-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="alert-heading mb-1">Payment Due Soon</h6>
                                        <p class="mb-0">You have <?php echo count($pending_payments); ?> payment(s) due in the next 7 days</p>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Pay Rent Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="payment-history-card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Pay Rent</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Pay Rent: Select any approved property to pay rent.
                                        </div>
                                        
                                        <?php if (empty($active_agreements)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">No Active Rentals</h6>
                                                <p class="text-muted">You don't have any active rental agreements.</p>
                                                <a href="search.php" class="btn payment-action-btn">Find Properties</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-3">
                                                <label class="form-label">Select Property to Pay Rent</label>
                                                <select class="form-select" id="propertySelect">
                                                    <?php foreach ($active_agreements as $agreement): ?>
                                                        <option value="<?php echo $agreement['agreement_id']; ?>"
                                                                data-amount="<?php echo $agreement['monthly_rent']; ?>"
                                                                data-property-id="<?php echo $agreement['property_id']; ?>"
                                                                data-title="<?php echo htmlspecialchars($agreement['title']); ?>"
                                                                data-due-date="<?php echo $agreement['end_date']; ?>"
                                                                data-payment-count="<?php echo $agreement['rent_payment_count']; ?>"
                                                                data-status="<?php echo $agreement['status']; ?>">
                                                            <?php echo htmlspecialchars($agreement['title']); ?> - <?php echo number_format($agreement['monthly_rent'], 0); ?> ETB/month (<?php echo ucfirst($agreement['status']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">All approved properties are available for payment</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Payment Period</label>
                                                <input type="month" class="form-control" id="paymentPeriod" value="<?php echo date('Y-m'); ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Amount (ETB)</label>
                                                <input type="number" class="form-control" id="paymentAmount" readonly>
                                            </div>
                                            
                                            <button type="button" class="btn btn-primary w-100" id="payRentBtn">
                                                <i class="fas fa-credit-card me-2"></i>Pay Rent
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recent Transactions -->
                            <div class="col-md-6">
                                <div class="payment-history-card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Wallet History</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($wallet_history)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">No History</h6>
                                                <p class="text-muted">Your wallet history will appear here.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="transaction-list">
                                                <?php foreach (array_slice($wallet_history, 0, 5) as $tx): ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                        <div>
                                                            <div class="fw-bold">
                                                                <?php echo htmlspecialchars($tx['description']); ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                <?php echo date('M d, Y h:i A', strtotime($tx['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold <?php echo $tx['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                                <?php echo ($tx['amount'] > 0 ? '+' : '') . number_format($tx['amount'], 2); ?> ETB
                                                            </div>
                                                            <?php
                                                            $status = strtolower($tx['status']);
                                                            $status_color = 'text-muted';
                                                            if ($status === 'pending') $status_color = 'text-warning';
                                                            elseif ($status === 'completed') $status_color = 'text-success';
                                                            elseif ($status === 'failed' || $status === 'cancelled') $status_color = 'text-danger';
                                                            ?>
                                                            <small class="fw-bold <?php echo $status_color; ?>"><?php echo ucfirst($tx['status']); ?></small>
                                                            <button class="btn btn-link btn-sm text-danger p-0 ms-2" 
                                                                    onclick="if(confirm('Delete from your history?')) window.location.href='?delete_tx=<?php echo $tx['transaction_id']; ?>'"
                                                                    title="Remove from view">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="payment-history-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Payment History</h5>
                                <a href="payment-history.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($payment_history)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No Payment History</h6>
                                        <p class="text-muted">Your payment history will appear here once you make payments.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Property</th>
                                                    <th>Location</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Status</th>
                                                    <th>Receipt</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payment_history as $payment): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($payment['property_title']); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($payment['location_name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo ucfirst($payment['payment_type'] ?? 'Rent'); ?></span>
                                                        </td>
                                                        <td>
                                                            <strong class="text-success">ETB <?php echo number_format($payment['amount'], 0); ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?php echo ucfirst($payment['payment_method']); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                                $status = $payment['payment_status'] ?? 'Pending';
                                                                $statusColors = [
                                                                    'Verified' => 'success',
                                                                    'Pending' => 'warning',
                                                                    'Failed' => 'danger',
                                                                    'Cancelled' => 'danger'
                                                                ];
                                                                $color = $statusColors[$status] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars($status); ?></span>
                                                        </td>
                                                        <td>
                                                            <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>&download=1"
                                                               class="btn btn-sm btn-outline-primary"
                                                               title="Download Receipt">
                                                                <i class="fas fa-download"></i>
                                                            </a>
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
        </div>
    </div>
</div>
</div>

<!-- Add Money Modal -->
<div class="modal fade" id="addMoneyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-wallet me-2"></i>Add Money to Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMoneyForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body">
                    <!-- Deposit Amount -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Deposit Amount (ETB)</label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="number" class="form-control" name="deposit_amount" 
                                   placeholder="Enter amount" min="10" step="0.01" required>
                        </div>
                        <small class="text-muted">Minimum deposit: 10 ETB</small>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Payment Method</label>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card deposit-method-card h-100" data-method="cbe">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-university fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="card-title">CBE Account</h6>
                                        <p class="card-text small text-muted">Commercial Bank of Ethiopia</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="deposit_method" 
                                                   value="cbe" id="cbe_check">
                                            <label class="form-check-label" for="cbe_check">
                                                Select CBE
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card deposit-method-card h-100" data-method="telebirr">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-mobile-alt fa-2x text-success"></i>
                                        </div>
                                        <h6 class="card-title">Telebirr</h6>
                                        <p class="card-text small text-muted">Mobile Money Service</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="deposit_method" 
                                                   value="telebirr" id="telebirr_check">
                                            <label class="form-check-label" for="telebirr_check">
                                                Select Telebirr
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card deposit-method-card h-100" data-method="mpesa">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                                        </div>
                                        <h6 class="card-title">MPESA</h6>
                                        <p class="card-text small text-muted">Mobile Money Transfer</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="deposit_method" 
                                                   value="mpesa" id="mpesa_check">
                                            <label class="form-check-label" for="mpesa_check">
                                                Select MPESA
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card deposit-method-card h-100" data-method="wegagen">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-building fa-2x text-info"></i>
                                        </div>
                                        <h6 class="card-title">Wegagen Bank</h6>
                                        <p class="card-text small text-muted">Bank Transfer Service</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="deposit_method" 
                                                   value="wegagen" id="wegagen_check">
                                            <label class="form-check-label" for="wegagen_check">
                                                Select Wegagen
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card deposit-method-card h-100" data-method="boa">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-landmark fa-2x text-danger"></i>
                                        </div>
                                        <h6 class="card-title">Bank of Abyssinia</h6>
                                        <p class="card-text small text-muted">Bank Transfer Service</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="deposit_method" 
                                                   value="boa" id="boa_check">
                                            <label class="form-check-label" for="boa_check">
                                                Select BOA
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card deposit-method-card h-100" data-method="dashen">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-piggy-bank fa-2x text-secondary"></i>
                                        </div>
                                        <h6 class="card-title">Dashen Bank</h6>
                                        <p class="card-text small text-muted">Bank Transfer Service</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="deposit_method" 
                                                   value="dashen" id="dashen_check">
                                            <label class="form-check-label" for="dashen_check">
                                                Select Dashen
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction ID -->
                    <div class="mb-3">
                        <label class="form-label fw-bold" id="transactionIdLabel">Account Number / Mobile Number</label>
                        <input type="text" class="form-control" name="transaction_id" id="transactionIdInput"
                               placeholder="Enter your valid account or mobile number" required 
                               inputmode="numeric">
                        <div class="invalid-feedback" id="transactionFeedback"></div>
                        <small class="text-muted" id="transactionIdHelp">Required to identify the source of deposit</small>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="2" 
                                  placeholder="Any additional information about the deposit..."></textarea>
                    </div>

                    <!-- Deposit Summary -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Deposit Summary</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Deposit Amount:</strong> <span class="text-success fw-bold" id="deposit_amount_display">ETB 0</span></p>
                                    <p class="mb-1"><strong>Payment Method:</strong> <span id="deposit_method_display">Not Selected</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Processing Fee:</strong> <span class="text-muted">ETB 0</span></p>
                                    <p class="mb-1"><strong>Total Amount:</strong> <span class="text-primary fw-bold" id="total_amount_display">ETB 0</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Secure Payment:</strong><br>
                        • You will be redirected to a secure payment gateway<br>
                        • Minimum deposit amount: 10 ETB
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-wallet me-2"></i>Deposit Money
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-arrow-up me-2"></i> Withdrawal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Available balance: <strong><?php echo number_format(max(0, $wallet_balance), 2); ?> ETB</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Withdraw (ETB)</label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="number" class="form-control" name="amount" id="withdrawAmount"
                                   max="<?php echo $wallet_balance; ?>" min="10" step="0.01" required>
                        </div>
                        <div id="withdrawalFeeInfo" class="mt-2 small d-none">
                            <div class="d-flex justify-content-between text-muted">
                                <span>Requested Amount:</span>
                                <span id="displayRequested">ETB 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>Withdrawal Fee:</span>
                                <span id="displayFee">ETB 0.00</span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fw-bold text-danger">
                                <span>Total Deduction:</span>
                                <span id="displayTotal">ETB 0.00</span>
                            </div>
                        </div>
                        <small class="text-muted">Min: 10 ETB. Fee: 2% (up to 1k) or 3% (over 1k).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Bank Name / Payment Method</label>
                        <select class="form-select" name="bank_name" id="bankSelect" required>
                            <option value="">Select Bank / Method</option>
                            <option value="CBE">Commercial Bank of Ethiopia (CBE)</option>
                            <option value="Telebirr">Telebirr</option>
                            <option value="M-PESA">M-PESA</option>
                            <option value="Abyssinia">Bank of Abyssinia (BoA)</option>
                            <option value="Dashen">Dashen Bank</option>
                            <option value="Wegagen">Wegagen Bank</option>
                            <option value="Awash">Awash Bank</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" id="accountLabel">Account Number / Mobile Number</label>
                        <input type="text" class="form-control" name="account_number" id="accountNumberInput" required 
                               inputmode="numeric" placeholder="Enter numbers only">
                        <div class="invalid-feedback" id="accountFeedback"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Account Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-danger text-white"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control border-danger" name="account_password" 
                                   placeholder="Enter your system login password" required>
                        </div>
                        <small class="text-danger">Enter your system login password to authorize this withdrawal.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Make Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body">
                    <input type="hidden" id="agreement_id" name="agreement_id">
                    <!-- Payment Type Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Payment Type</label>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <div class="card payment-type-card h-100" data-payment-type="MONTHLY">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="card-title">Monthly Payment</h6>
                                        <p class="card-text small text-muted">Pay this month's rent</p>
                                        <div class="payment-amount">
                                            <strong class="text-primary" id="monthlyAmount">ETB 0</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="card payment-type-card h-100" data-payment-type="MINIMUM">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-percentage fa-2x text-warning"></i>
                                        </div>
                                        <h6 class="card-title">Minimum Payment</h6>
                                        <p class="card-text small text-muted">20% reservation or 1st month</p>
                                        <div class="payment-amount">
                                            <strong class="text-warning" id="minimumAmount">ETB 0</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="card payment-type-card h-100" data-payment-type="FULL">
                                    <div class="card-body text-center">
                                        <div class="payment-icon mb-2">
                                            <i class="fas fa-check-circle fa-2x text-success"></i>
                                        </div>
                                        <h6 class="card-title">Full Payment</h6>
                                        <p class="card-text small text-muted">Pay 6 months in advance</p>
                                        <div class="payment-amount">
                                            <strong class="text-success" id="fullAmount">ETB 0</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="payment_type" name="payment_type" value="MONTHLY">
                        <input type="hidden" id="property_id" name="property_id">
                    </div>
                    <!-- Payment Summary -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Payment Summary</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Property:</strong> <span id="payment_property_title"></span></p>
                                    <p class="mb-1"><strong>Due Date:</strong> <span id="payment_due_date"></span></p>
                                    <p class="mb-1"><strong>Payment Type:</strong> <span id="selected_payment_type" class="badge bg-primary">Monthly</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Amount Due:</strong> <span class="text-success fw-bold" id="payment_amount">ETB 0</span></p>
                                    <p class="mb-1"><strong>Total Agreement:</strong> <span id="total_agreement_amount">ETB 0</span></p>
                                    <p class="mb-1"><strong>Balance Remaining:</strong> <span id="balance_remaining" class="text-warning">ETB 0</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Payment Method (Hidden for Rent, set to wallet) -->
                    <input type="hidden" name="payment_method" value="wallet">
                    
                    <!-- Transaction ID (Removed for wallet payments) -->
                    <div id="transactionSection" class="mb-3" style="display: none;">
                        <label class="form-label">Transaction ID / Reference</label>
                        <input type="text" name="transaction_id" class="form-control" 
                               placeholder="Enter transaction ID or reference number">
                        <small class="text-muted">Required for manual payment methods</small>
                    </div>
                    <!-- Payment Date -->
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Any additional information about the payment..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Wallet Payment:</strong><br>
                        • The amount will be deducted from your wallet balance<br>
                        • Ensure you have sufficient funds before submitting<br>
                        • Payment is processed immediately<br>
                        • Receipt will be generated instantly
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-credit-card me-2"></i>Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment page loaded');

    // Safety: show JS errors in an alert so user doesn't get stuck with a non-responsive UI.
    window.addEventListener('error', function(event) {
        console.error('Unhandled JS error:', event.error || event.message);
        alert('A JavaScript error occurred: ' + (event.error?.message || event.message));
    });

    function getPaymentModalInstance() {
        const modalEl = document.getElementById('paymentModal');
        if (!modalEl) return null;
        if (window.bootstrap && bootstrap.Modal) {
            return bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return null;
    }
    function showModal() {
        const inst = getPaymentModalInstance();
        const modal = document.getElementById('paymentModal');
        if (inst) {
            inst.show();
        } else if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }
    function hideModal() {
        const inst = getPaymentModalInstance();
        const modal = document.getElementById('paymentModal');
        if (inst) {
            inst.hide();
        } else if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
    }
    // Property selection change
    const propertySelect = document.getElementById('propertySelect');
    const paymentAmount = document.getElementById('paymentAmount');
    const payRentBtn = document.getElementById('payRentBtn');
    const withdrawMoneyBtn = document.getElementById('withdrawMoneyBtn');
    const withdrawForm = document.getElementById('withdrawForm');
    const bankSelect = document.getElementById('bankSelect');
    const accountLabel = document.getElementById('accountLabel');
    const accountInput = document.getElementById('accountNumberInput');
    const accountFeedback = document.getElementById('accountFeedback');

    // Update label and placeholder based on bank selection
    if (bankSelect) {
        bankSelect.addEventListener('change', function() {
            const bank = this.value;
            if (bank === 'Telebirr' || bank === 'M-PESA') {
                accountLabel.textContent = 'Mobile Number';
                accountInput.placeholder = bank === 'Telebirr' ? 'e.g. 09XXXXXXXX' : 'e.g. 07XXXXXXXX';
            } else {
                accountLabel.textContent = 'Account Number';
                accountInput.placeholder = 'Enter bank account number';
            }
            validateAccountNumber();
        });
    }

    // Numeric only input restriction
    if (accountInput) {
        accountInput.addEventListener('keypress', function(e) {
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
            }
        });
        
        accountInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            validateAccountNumber();
        });
    }

    function validateAccountNumber() {
        const bank = bankSelect.value;
        const account = accountInput.value;
        let isValid = true;
        let message = '';

        if (!bank) return true;
        if (!account) {
            isValid = false;
            message = 'Account/Mobile number is required';
        } else {
            switch(bank) {
                case 'CBE':
                    if (account.length !== 13) {
                        isValid = false;
                        message = 'CBE account number must be exactly 13 digits';
                    }
                    break;
                case 'Telebirr':
                    if (!/^09\d{8}$/.test(account)) {
                        isValid = false;
                        message = 'Telebirr number must be 10 digits starting with 09';
                    }
                    break;
                case 'M-PESA':
                    if (!/^07\d{8}$/.test(account)) {
                        isValid = false;
                        message = 'M-PESA number must be 10 digits starting with 07';
                    }
                    break;
                case 'Dashen':
                    if (account.length < 10 || account.length > 13) {
                        isValid = false;
                        message = 'Dashen Bank account should be 10-13 digits';
                    }
                    break;
                case 'Abyssinia':
                    if (account.length < 13 || account.length > 15) {
                        isValid = false;
                        message = 'BoA account should be 13-15 digits';
                    }
                    break;
                case 'Wegagen':
                    if (account.length < 12 || account.length > 15) {
                        isValid = false;
                        message = 'Wegagen account should be 12-15 digits';
                    }
                    break;
            }
        }

        if (isValid) {
            accountInput.classList.remove('is-invalid');
            accountInput.classList.add('is-valid');
        } else {
            accountInput.classList.remove('is-valid');
            accountInput.classList.add('is-invalid');
            accountFeedback.textContent = message;
        }
        return isValid;
    }
    
    // Withdrawal Amount Fee Calculation
    const withdrawAmountInput = document.getElementById('withdrawAmount');
    const withdrawalFeeInfo = document.getElementById('withdrawalFeeInfo');
    const displayRequested = document.getElementById('displayRequested');
    const displayFee = document.getElementById('displayFee');
    const displayTotal = document.getElementById('displayTotal');

    if (withdrawAmountInput) {
        withdrawAmountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            if (amount >= 10) {
                const fee = amount <= 1000 ? amount * 0.02 : amount * 0.03;
                const total = amount + fee;
                
                displayRequested.textContent = 'ETB ' + amount.toFixed(2);
                displayFee.textContent = 'ETB ' + fee.toFixed(2);
                displayTotal.textContent = 'ETB ' + total.toFixed(2);
                withdrawalFeeInfo.classList.remove('d-none');
            } else {
                withdrawalFeeInfo.classList.add('d-none');
            }
        });
    }

    // Withdrawal Button
    if (withdrawMoneyBtn) {
        withdrawMoneyBtn.addEventListener('click', function() {
            const walletBalance = <?php echo (float)$wallet_balance; ?>;
            if (walletBalance <= 0) {
                alert('Your wallet balance is 0.00 ETB. You need funds in your wallet to request a withdrawal.');
                return;
            }
            const modalEl = document.getElementById('withdrawModal');
            if (modalEl && window.bootstrap) {
                const withdrawModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                withdrawModal.show();
            } else {
                console.error('Bootstrap or withdrawModal not found');
            }
        });
    }

    // Withdrawal Form Submission
    if (withdrawForm) {
        withdrawForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!validateAccountNumber()) {
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            
            const formData = new FormData(this);
            
            fetch('../api/request-withdrawal.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || data.error || 'Withdrawal request failed');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }

    if (propertySelect) {
        propertySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const amount = selectedOption.dataset.amount;
            paymentAmount.value = amount;
        });
        
        // Set initial amount
        if (propertySelect.options.length > 0) {
            const firstOption = propertySelect.options[0];
            paymentAmount.value = firstOption.dataset.amount;
        }
    }
    
    // Pay rent button click
    if (payRentBtn) {
        payRentBtn.addEventListener('click', function() {
            if (!propertySelect || !propertySelect.value) {
                alert('Please select a property to pay rent');
                return;
            }
            
            const selectedOption = propertySelect.options[propertySelect.selectedIndex];
            const agreementId = propertySelect.value;
            const propertyTitle = selectedOption.dataset.title;
            const amount = selectedOption.dataset.amount;
            const dueDate = selectedOption.dataset.dueDate;
            const propertyId = selectedOption.dataset.propertyId;
            
            console.log('Pay rent clicked for agreement:', agreementId);
            document.getElementById('agreement_id').value = agreementId;
            document.getElementById('property_id').value = propertyId;
            document.getElementById('payment_property_title').textContent = propertyTitle;
            document.getElementById('payment_due_date').textContent = new Date(dueDate).toLocaleDateString();
            
            // Check if first month (payment count == 0)
            const paymentCount = parseInt(selectedOption.dataset.paymentCount || 0);
            const minimumCard = document.querySelector('[data-payment-type="MINIMUM"]');
            
            if (paymentCount > 0) {
                minimumCard.classList.add('opacity-50');
                minimumCard.style.pointerEvents = 'none';
                minimumCard.title = "Only available for the first month's rent.";
                // Ensure MONTHLY is selected if MINIMUM was somehow active
                selectPaymentType('MONTHLY', Number(amount), Number(amount) * 6, 0);
            } else {
                minimumCard.classList.remove('opacity-50');
                minimumCard.style.pointerEvents = 'auto';
                minimumCard.title = "";
            }

            // Calculate payment amounts
            const monthlyRent = Number(amount);
            const fullAmount = monthlyRent * 6;
            const minimumAmount = parseFloat((monthlyRent * 0.2).toFixed(2));
            
            // Update payment type cards
            document.getElementById('monthlyAmount').textContent = 'ETB ' + monthlyRent.toLocaleString();
            document.getElementById('minimumAmount').textContent = 'ETB ' + minimumAmount.toLocaleString();
            document.getElementById('fullAmount').textContent = 'ETB ' + fullAmount.toLocaleString();
            
            // Set default to monthly payment
            selectPaymentType('MONTHLY', monthlyRent, fullAmount, 0);
            showModal();
        });
    }

    // Add Money button click
    const addMoneyBtn = document.getElementById('addMoneyBtn');
    const addMoneyModal = document.getElementById('addMoneyModal');
    const addMoneyForm = document.getElementById('addMoneyForm');
    const depositMethodCards = document.querySelectorAll('.deposit-method-card');
    const depositAmountInput = document.querySelector('input[name="deposit_amount"]');
    const depositAmountDisplay = document.getElementById('deposit_amount_display');
    const totalAmountDisplay = document.getElementById('total_amount_display');
    const depositMethodDisplay = document.getElementById('deposit_method_display');

    function showAddMoneyModal() {
        const modalEl = document.getElementById('addMoneyModal');
        if (window.bootstrap && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }

    function hideAddMoneyModal() {
        const modalEl = document.getElementById('addMoneyModal');
        if (window.bootstrap && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        } else {
            modalEl.style.display = 'none';
            modalEl.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
    }

    if (addMoneyBtn) {
        addMoneyBtn.addEventListener('click', function() {
            console.log('Add Money button clicked');
            showAddMoneyModal();
        });
    }

    // Deposit method selection
    depositMethodCards.forEach(function(card) {
        card.addEventListener('click', function() {
            const method = this.dataset.method;
            const radioInput = this.querySelector('input[type="radio"]');
            
            // Update visual selection
            depositMethodCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            
            // Update radio button
            document.querySelectorAll('input[name="deposit_method"]').forEach(radio => {
                radio.checked = false;
            });
            radioInput.checked = true;
            
            // Update display
            const methodNames = {
                'cbe': 'CBE Account',
                'telebirr': 'Telebirr',
                'mpesa': 'MPESA',
                'wegagen': 'Wegagen Bank',
                'boa': 'Bank of Abyssinia',
                'dashen': 'Dashen Bank'
            };
            depositMethodDisplay.textContent = methodNames[method] || method;

            // Update Transaction label based on selected method
            const transactionLabel = document.getElementById('transactionIdLabel');
            const transactionInput = document.getElementById('transactionIdInput');
            const transactionHelp = document.getElementById('transactionIdHelp');
            
            if (transactionLabel && transactionInput && transactionHelp) {
                if (['telebirr', 'mpesa'].includes(method)) {
                    transactionLabel.textContent = 'Valid Mobile Number';
                    transactionInput.placeholder = method === 'telebirr' ? 'e.g. 09XXXXXXXX' : 'e.g. 07XXXXXXXX';
                    transactionHelp.textContent = 'Enter the mobile number used for this transaction';
                } else {
                    transactionLabel.textContent = 'Valid Bank Account Number';
                    transactionInput.placeholder = 'Enter bank account number';
                    transactionHelp.textContent = 'Enter the account number used for this transaction';
                }
            }
            validateTransactionId();
        });
    });

    // Numeric only and validation for Transaction ID
    const transactionInput = document.getElementById('transactionIdInput');
    const transactionFeedback = document.getElementById('transactionFeedback');
    
    if (transactionInput) {
        transactionInput.addEventListener('keypress', function(e) {
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
            }
        });
        
        transactionInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            validateTransactionId();
        });
    }

    function validateTransactionId() {
        const methodRadio = document.querySelector('input[name="deposit_method"]:checked');
        const method = methodRadio ? methodRadio.value : '';
        const value = transactionInput.value;
        let isValid = true;
        let message = '';

        if (!method) return true;
        if (!value) {
            isValid = false;
            message = 'Account/Mobile number is required';
        } else {
            switch(method) {
                case 'cbe':
                    if (value.length !== 13) {
                        isValid = false;
                        message = 'CBE account number must be exactly 13 digits';
                    }
                    break;
                case 'telebirr':
                    if (!/^09\d{8}$/.test(value)) {
                        isValid = false;
                        message = 'Telebirr number must be 10 digits starting with 09';
                    }
                    break;
                case 'mpesa':
                    if (!/^07\d{8}$/.test(value)) {
                        isValid = false;
                        message = 'M-PESA number must be 10 digits starting with 07';
                    }
                    break;
                case 'dashen':
                    if (value.length < 10 || value.length > 13) {
                        isValid = false;
                        message = 'Dashen Bank account should be 10-13 digits';
                    }
                    break;
                case 'boa':
                    if (value.length < 13 || value.length > 15) {
                        isValid = false;
                        message = 'BoA account should be 13-15 digits';
                    }
                    break;
                case 'wegagen':
                    if (value.length < 12 || value.length > 15) {
                        isValid = false;
                        message = 'Wegagen account should be 12-15 digits';
                    }
                    break;
            }
        }

        if (isValid) {
            transactionInput.classList.remove('is-invalid');
            transactionInput.classList.add('is-valid');
        } else {
            transactionInput.classList.remove('is-valid');
            transactionInput.classList.add('is-invalid');
            transactionFeedback.textContent = message;
        }
        return isValid;
    }

    // Update deposit amount display
    if (depositAmountInput) {
        depositAmountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            depositAmountDisplay.textContent = 'ETB ' + amount.toLocaleString();
            totalAmountDisplay.textContent = 'ETB ' + amount.toLocaleString();
        });
    }

    // Submit add money form
    if (addMoneyForm) {
        addMoneyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const depositAmount = formData.get('deposit_amount');
            const depositMethod = formData.get('deposit_method');
            const transactionId = formData.get('transaction_id');
            
            // Validation
            if (!depositAmount || parseFloat(depositAmount) < 10) {
                alert('Minimum deposit amount is 10 ETB');
                return;
            }
            
            if (!depositMethod) {
                alert('Please select a payment method');
                return;
            }
            
            if (!transactionId) {
                alert('Please enter your valid account or mobile number');
                return;
            }

            if (!validateTransactionId()) {
                return;
            }
            
            // Submit to server
            fetch('../api/add-money.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                console.log('Add money response status:', response.status, response.statusText);
                console.log('Add money response text:', text);

                if (response.status === 401) {
                    throw new Error('You are not logged in. Please log in again.');
                }

                if (!response.ok) {
                    throw new Error('Server error ' + response.status + ': ' + (text || response.statusText));
                }

                if (!text || text.trim() === '') {
                    throw new Error('Empty server response (no JSON).');
                }

                try {
                    return JSON.parse(text);
                } catch (parseErr) {
                    throw new Error('Invalid server response (non-JSON): ' + text);
                }
            })
            .then(data => {
                console.log('Add money response:', data);
                if (data.success) {
                    if (data.redirect_url) {
                        // Redirect to the payment gateway / simulator
                        window.location.href = data.redirect_url;
                    } else {
                        hideAddMoneyModal();
                        alert(data.message || 'Money deposited successfully! Your wallet balance has been updated immediately.');
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Add money error:', error);
                alert('An error occurred while submitting deposit request. Please try again.\n\nDetails: ' + (error.message || error));
            });
        });
    }
    // Payment type selection
    const paymentTypeCards = document.querySelectorAll('.payment-type-card');
    paymentTypeCards.forEach(function(card) {
        card.addEventListener('click', function() {
            const paymentType = this.dataset.paymentType;
            const monthlyRent = Number(document.getElementById('monthlyAmount').textContent.replace(/[^0-9]/g, ''));
            const fullAmount = Number(document.getElementById('fullAmount').textContent.replace(/[^0-9]/g, ''));
            let amount, balance;
            switch(paymentType) {
                case 'MONTHLY':
                    amount = monthlyRent;
                    balance = 0;
                    break;
                case 'MINIMUM':
                    amount = Number(document.getElementById('minimumAmount').textContent.replace(/[^0-9]/g, ''));
                    balance = monthlyRent - amount; // Fixed: calculate balance from monthly rent instead of 6 months
                    totalAmount = monthlyRent; // Update total for summary display
                    break;
                case 'FULL':
                    amount = fullAmount;
                    balance = 0;
                    break;
            }
            selectPaymentType(paymentType, amount, fullAmount, balance);
        });
    });
    function selectPaymentType(type, amount, totalAmount, balance) {
        // Update card selection
        paymentTypeCards.forEach(card => {
            card.classList.remove('border-primary', 'bg-light');
        });
        document.querySelector(`[data-payment-type="${type}"]`).classList.add('border-primary', 'bg-light');
        // Update hidden input
        document.getElementById('payment_type').value = type;
        // Update summary
        const typeLabels = {
            'MONTHLY': 'Monthly Payment',
            'MINIMUM': 'Minimum Payment',
            'FULL': 'Full Payment'
        };
        const typeColors = {
            'MONTHLY': 'bg-primary',
            'MINIMUM': 'bg-warning',
            'FULL': 'bg-success'
        };
        document.getElementById('selected_payment_type').textContent = typeLabels[type];
        document.getElementById('selected_payment_type').className = `badge ${typeColors[type]}`;
        document.getElementById('payment_amount').textContent = 'ETB ' + amount.toLocaleString();
        document.getElementById('total_agreement_amount').textContent = 'ETB ' + totalAmount.toLocaleString();
        document.getElementById('balance_remaining').textContent = 'ETB ' + balance.toLocaleString();
    }
    // Payment method change handler
    const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
    const virtualBankingInfo = document.getElementById('virtualBankingInfo');
    const transactionSection = document.getElementById('transactionSection');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            const method = this.value;
            console.log('Payment method changed to:', method);
            if (method === 'virtual_bank') {
                virtualBankingInfo.style.display = 'block';
                transactionSection.style.display = 'none';
                document.querySelector('input[name="transaction_id"]').removeAttribute('required');
            } else {
                virtualBankingInfo.style.display = 'none';
                transactionSection.style.display = 'block';
                document.querySelector('input[name="transaction_id"]').setAttribute('required', 'required');
            }
        });
    }
    // Submit payment form
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Payment form submitted');
            const formData = new FormData(this);
            const paymentMethod = formData.get('payment_method');
            const paymentType = formData.get('payment_type');
            const amountStr = document.getElementById('payment_amount').textContent.replace(/[^0-9.]/g, '');
            const amount = parseFloat(amountStr);
            const walletBalance = <?php echo (float)$wallet_balance; ?>;
            const totalPayable = parseFloat((amount * 1.03).toFixed(2));

            // Validate payment type selection
            if (!paymentType) {
                alert('Please select a payment type');
                return;
            }

            // Check wallet balance based on total payable amount with fee
            if (paymentMethod === 'wallet' && totalPayable > walletBalance) {
                alert('Insufficient wallet balance. Total payable with fees is ETB ' + totalPayable.toLocaleString() + '. Please add money to your wallet first.');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            fetch('../api/process-payment.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                if (response.status === 401) {
                    throw new Error('You are not logged in. Please log in again.');
                }
                if (!response.ok) {
                    throw new Error('Server error ' + response.status + ': ' + (text || response.statusText));
                }
                try {
                    return JSON.parse(text);
                } catch (parseErr) {
                    throw new Error('Invalid server response (non-JSON): ' + text);
                }
            })
            .then(data => {
                if (data.success) {
                    hideModal();
                    alert('Payment successful! Amount deducted from your wallet.');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                alert('An error occurred while submitting payment. Please try again.\n\nDetails: ' + (error.message || error));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }
    // Close modal functionality (btn-close and Cancel buttons)
    document.addEventListener('click', function(event) {
        if (
            event.target.classList.contains('btn-close') ||
            (event.target.matches('#paymentModal [data-bs-dismiss="modal"]')) ||
            (event.target.matches('#addMoneyModal [data-bs-dismiss="modal"]')) ||
            (event.target.classList.contains('modal') && event.target.classList.contains('fade'))
        ) {
            let modal = event.target;
            if (!modal.classList.contains('modal')) {
                modal = event.target.closest('.modal');
            }
            if (modal && modal.id === 'paymentModal') {
                hideModal();
            } else if (modal && modal.id === 'addMoneyModal') {
                hideAddMoneyModal();
            }
        }
    });
});
</script>
<?php include '../includes/footer.php'; ?>