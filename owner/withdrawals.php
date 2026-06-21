<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

$session->requireRole('owner');

$title = "Withdrawals - Owner Dashboard";
$owner_id = $session->getUserId();

// Handle deletion
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Owner delete only hides from owner view
    $stmt = $db->prepare("UPDATE wallet_transactions wt 
                         JOIN wallets w ON wt.wallet_id = w.wallet_id
                         SET wt.is_visible_user = 0 
                         WHERE wt.transaction_id = ? AND w.user_id = ?");
    $db->execute($stmt, [$del_id, $owner_id]);
    header("Location: withdrawals.php?msg=Record hidden from view");
    exit;
}

// Get wallet balance
$wallet_balance = getWalletBalance($owner_id);

// Get wallet transaction history
$sql = "SELECT wt.*, p.payment_method
        FROM wallet_transactions wt
        JOIN wallets w ON wt.wallet_id = w.wallet_id
        LEFT JOIN payments p ON wt.reference_table = 'payments' AND wt.reference_id = p.payment_id
        WHERE w.user_id = ? AND wt.is_visible_user = 1
        ORDER BY wt.created_at DESC LIMIT 50";
$stmt = $db->prepare($sql);
$wallet_history = $db->getMultiple($stmt, [$owner_id]);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Withdrawals & Wallet</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Withdrawals</li>
                    </ol>
                </nav>
            </div>

            <!-- Wallet Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-uppercase mb-2 opacity-75">Current Balance</h6>
                            <h2 class="display-6 fw-bold mb-0">ETB <?php echo number_format(max(0, $wallet_balance), 2); ?></h2>
                        </div>
                        <div class="card-footer bg-transparent border-top border-white border-opacity-10 py-3">
                            <button class="btn btn-light w-100 fw-bold text-primary" id="withdrawMoneyBtn" <?php echo $wallet_balance <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-arrow-up me-2"></i>Request Withdrawal
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Withdrawal Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-info-circle text-primary me-2"></i>
                                            Minimum withdrawal: <strong>10.00 ETB</strong>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            Processing time: <strong>24-48 hours</strong>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-shield-alt text-primary me-2"></i>
                                            Secure processing
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-university text-primary me-2"></i>
                                            Direct bank transfer
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet History -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Wallet History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($wallet_history)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-4x text-muted mb-3 opacity-25"></i>
                            <h5 class="text-muted">No transaction history found</h5>
                            <p class="text-muted">Your earnings and withdrawals will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Date</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th class="pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wallet_history as $tx): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold"><?php echo date('M d, Y', strtotime($tx['created_at'])); ?></div>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($tx['created_at'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($tx['description']); ?></td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?php echo $tx['transaction_type'] === 'deposit' ? 'success' : 'danger'; ?> bg-opacity-10 text-<?php echo $tx['transaction_type'] === 'deposit' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($tx['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="<?php echo $tx['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo ($tx['amount'] > 0 ? '+' : '') . number_format($tx['amount'], 2); ?> ETB
                                                </strong>
                                            </td>
                                            <td>
                                                <?php
                                                $status_classes = [
                                                    'pending' => 'warning',
                                                    'completed' => 'success',
                                                    'cancelled' => 'secondary',
                                                    'failed' => 'danger'
                                                ];
                                                $status_class = $status_classes[$tx['status']] ?? 'info';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($tx['status']); ?></span>
                                            </td>
                                            <td class="pe-4">
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="if(confirm('Are you sure you want to delete this record from your history?')) window.location.href='?delete=<?php echo $tx['transaction_id']; ?>'">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title">
                    <i class="fas fa-arrow-up me-2"></i> Request Withdrawal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm small">
                        <i class="fas fa-info-circle me-1"></i>
                        Available balance: <strong>ETB <?php echo number_format(max(0, $wallet_balance), 2); ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Withdraw (ETB)</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0 bg-white">ETB</span>
                            <input type="number" class="form-control border-start-0" name="amount" id="withdrawAmount"
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
                        <label class="form-label fw-bold text-danger">Authorize with Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-danger text-white border-danger"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control border-danger" name="account_password" 
                                   placeholder="Enter your login password" required>
                        </div>
                        <small class="text-danger">Enter your system login password to authorize this withdrawal.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const withdrawMoneyBtn = document.getElementById('withdrawMoneyBtn');
    const withdrawForm = document.getElementById('withdrawForm');
    const bankSelect = document.getElementById('bankSelect');
    const accountLabel = document.getElementById('accountLabel');
    const accountInput = document.getElementById('accountNumberInput');
    const accountFeedback = document.getElementById('accountFeedback');

    // Withdrawal Button
    if (withdrawMoneyBtn) {
        withdrawMoneyBtn.addEventListener('click', function() {
            const modalEl = document.getElementById('withdrawModal');
            if (modalEl && window.bootstrap) {
                const withdrawModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                withdrawModal.show();
            }
        });
    }

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
});
</script>

<?php include '../includes/footer.php'; ?>
