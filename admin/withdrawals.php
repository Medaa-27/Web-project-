<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

$title = "Withdrawal Management - Admin Dashboard";

// Require admin login
$session->requireRole('admin');

$admin_id = $session->getUserId();
$admin_balance = getWalletBalance($admin_id);

// Ensure visibility columns exist
if (!$db->columnExists('wallet_transactions', 'is_visible_admin')) {
    $conn->exec("ALTER TABLE wallet_transactions ADD COLUMN is_visible_admin TINYINT(1) DEFAULT 1");
}
if (!$db->columnExists('wallet_transactions', 'is_visible_user')) {
    $conn->exec("ALTER TABLE wallet_transactions ADD COLUMN is_visible_user TINYINT(1) DEFAULT 1");
}

// Handle deletion
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Admin delete only hides from admin view
    $stmt = $db->prepare("UPDATE wallet_transactions SET is_visible_admin = 0 WHERE transaction_id = ? AND transaction_type = 'withdrawal'");
    $db->execute($stmt, [$del_id]);
    header("Location: withdrawals.php?msg=Record hidden from admin view");
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = ["wt.transaction_type = 'withdrawal'", "wt.is_visible_admin = 1"];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "wt.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR wt.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM wallet_transactions wt
              JOIN wallets w ON wt.wallet_id = w.wallet_id
              JOIN users u ON w.user_id = u.user_id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$total_result = $db->getSingle($stmt, $params);
$total_withdrawals = $total_result['total'];
$total_pages = ceil($total_withdrawals / $limit);

// Get withdrawals with pagination
$sql = "SELECT wt.*, u.full_name as user_name, u.email as user_email, u.user_id
        FROM wallet_transactions wt
        JOIN wallets w ON wt.wallet_id = w.wallet_id
        JOIN users u ON w.user_id = u.user_id 
        $where_clause
        ORDER BY wt.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$withdrawals = $db->getMultiple($stmt, $params);

// Get statistics
$stats_sql = "SELECT 
                 COUNT(*) as total,
                 COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                 COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                 COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                 SUM(CASE WHEN status = 'completed' THEN ABS(amount) ELSE 0 END) as total_withdrawn,
                 SUM(CASE WHEN status = 'pending' THEN ABS(amount) ELSE 0 END) as pending_amount
              FROM wallet_transactions 
              WHERE transaction_type = 'withdrawal' AND is_visible_admin = 1";
$stmt = $db->prepare($stats_sql);
$stats = $db->getSingle($stmt);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Withdrawal Management</h1>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAdminHistory()">
                        <i class="fas fa-trash-sweep me-1"></i>Clear Admin History
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="withdrawMoneyBtn">
                        <i class="fas fa-hand-holding-usd me-1"></i>Request My Withdrawal
                    </button>
                </div>
            </div>
            
            <!-- Withdrawal Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-hand-holding-usd fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                    <p class="mb-0">Total Requests</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h3 class="mb-0"><?php echo number_format($stats['total_withdrawn'] ?? 0, 2); ?></h3>
                                    <p class="mb-0">Total Processed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h3 class="mb-0"><?php echo $stats['failed']; ?></h3>
                                    <p class="mb-0">Failed/Cancelled</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filter & Search Withdrawals
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="User name, email, description..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <a href="withdrawals.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Withdrawals Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Withdrawal Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($withdrawals)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No withdrawal requests found</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Fee</th>
                                        <th>Net</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                <tbody>
                                    <?php foreach ($withdrawals as $withdrawal): ?>
                                        <tr>
                                            <td>#<?php echo $withdrawal['transaction_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($withdrawal['user_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($withdrawal['user_email']); ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-danger"><?php echo number_format(abs($withdrawal['amount']), 2); ?> ETB</strong>
                                            </td>
                                            <td>
                                                <strong>ETB <?php echo number_format($withdrawal['fee'] ?? 0.00, 2); ?></strong>
                                            </td>
                                            <td>
                                                <strong>ETB <?php echo number_format($withdrawal['net_amount'] ?? abs($withdrawal['amount']), 2); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($withdrawal['description']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    $status_colors = [
                                                        'completed' => 'success',
                                                        'cancelled' => 'secondary',
                                                        'failed' => 'danger'
                                                    ];
                                                    echo $status_colors[$withdrawal['status']] ?? 'info';
                                                ?>">
                                                    <?php echo ucfirst($withdrawal['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="if(confirm('Are you sure you want to delete this record?')) window.location.href='?delete=<?php echo $withdrawal['transaction_id']; ?>'">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
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
                    <i class="fas fa-arrow-up me-2"></i> Request My Withdrawal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm small">
                        <i class="fas fa-info-circle me-1"></i>
                        Available balance: <strong>ETB <?php echo number_format(max(0, $admin_balance), 2); ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Withdraw (ETB)</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0 bg-white">ETB</span>
                            <input type="number" class="form-control border-start-0" name="amount" 
                                   max="<?php echo $admin_balance; ?>" min="10" step="0.01" required>
                        </div>
                        <small class="text-muted">Minimum withdrawal: 10 ETB</small>
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
                        <label class="form-label fw-bold">Authorization Password</label>
                        <input type="password" class="form-control" name="account_password" 
                               placeholder="Enter your system password" required>
                        <small class="text-muted">Required to authorize this withdrawal</small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear History Modal -->
<div class="modal fade" id="clearHistoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i> Clear Withdrawal History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger border-0 shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                
                <p>This will permanently delete all withdrawal records from the system. This includes:</p>
                <ul class="mb-3">
                    <li>All pending withdrawal requests</li>
                    <li>All completed withdrawal records</li>
                    <li>All cancelled and failed withdrawal records</li>
                </ul>
                
                <p><strong>This action does not affect:</strong></p>
                <ul class="mb-3">
                    <li>User wallet balances</li>
                    <li>Deposit records</li>
                    <li>Payment records</li>
                </ul>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmClear">
                    <label class="form-check-label fw-bold" for="confirmClear">
                        I understand this action cannot be undone
                    </label>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Type "CLEAR" to confirm:</label>
                    <input type="text" class="form-control" id="clearConfirmation" placeholder="Type CLEAR here">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="clearHistoryBtn" disabled>
                    <i class="fas fa-trash me-2"></i>Clear All History
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    window.clearAdminHistory = function() {
        if (confirm('Are you sure you want to clear all withdrawal history from YOUR view? This will NOT affect user records.')) {
            fetch('../api/clear-withdrawal-history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || data.error));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An error occurred while clearing history.');
            });
        }
    };

    // Handle Request Withdrawal Button
    const withdrawBtn = document.getElementById('withdrawMoneyBtn');
    if (withdrawBtn) {
        withdrawBtn.addEventListener('click', function() {
            const withdrawModal = new bootstrap.Modal(document.getElementById('withdrawModal'));
            withdrawModal.show();
        });
    }

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

function processWithdrawal(transactionId, status) {
    let action = 'process';
    if (status === 'completed') action = 'approve';
    else if (status === 'cancelled') action = 'cancel';
    else if (status === 'failed') action = 'fail';
    
    if (!confirm(`Are you sure you want to ${action} this withdrawal request?`)) {
        return;
    }
    
    fetch('../api/admin-process-withdrawal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            transaction_id: transactionId,
            status: status,
            csrf_token: '<?php echo generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error || 'Failed to process withdrawal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Clear History functionality
document.addEventListener('DOMContentLoaded', function() {
    const clearHistoryBtn = document.getElementById('clearHistoryBtn');
    const confirmClear = document.getElementById('confirmClear');
    const clearConfirmation = document.getElementById('clearConfirmation');
    
    function updateClearButton() {
        const checkboxChecked = confirmClear && confirmClear.checked;
        const textCorrect = clearConfirmation && clearConfirmation.value.toUpperCase() === 'CLEAR';
        const isConfirmed = checkboxChecked && textCorrect;
        
        if (clearHistoryBtn) {
            clearHistoryBtn.disabled = !isConfirmed;
        }
    }
    
    if (confirmClear) {
        confirmClear.addEventListener('change', updateClearButton);
    }
    
    if (clearConfirmation) {
        clearConfirmation.addEventListener('input', updateClearButton);
        clearConfirmation.addEventListener('keyup', updateClearButton);
        clearConfirmation.addEventListener('change', updateClearButton);
    }
    
    // Also check when modal is shown
    const clearHistoryModal = document.getElementById('clearHistoryModal');
    if (clearHistoryModal) {
        clearHistoryModal.addEventListener('shown.bs.modal', function() {
            // Reset form when modal opens
            if (confirmClear) confirmClear.checked = false;
            if (clearConfirmation) clearConfirmation.value = '';
            updateClearButton();
        });
    }
    
    if (clearHistoryBtn) {
        clearHistoryBtn.addEventListener('click', function() {
            if (!confirm('Are you absolutely sure you want to delete all withdrawal history? This cannot be undone!')) {
                return;
            }
            
            clearHistoryBtn.disabled = true;
            clearHistoryBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Clearing...';
            
            fetch('../api/clear-withdrawal-history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                return response.text().then(text => {
                    console.log('Response text:', text);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${text}`);
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Parsed data:', data);
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.error || 'Failed to clear withdrawal history');
                    clearHistoryBtn.disabled = false;
                    clearHistoryBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Clear All History';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
                clearHistoryBtn.disabled = false;
                clearHistoryBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Clear All History';
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
