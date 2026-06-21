<?php
require_once '../includes/config.php';
$title = t('admin_dashboard_title');

require_once '../includes/wallet-functions.php';

// Require admin login
$session->requireRole('admin');

$admin_id = $session->getUserId();
$wallet_balance = getWalletBalance($admin_id);

// Get dashboard statistics
$stats = getDashboardStats('admin', $session->getUserId());

// Get pending activations count
$pending_activations = 0;
$sql = "SELECT COUNT(*) as count FROM users WHERE role IN ('employee', 'owner', 'tenant')";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$pending_activations = $result['count'] ?? 0;

// Get pending feedback count
$pending_feedback = 0;
$sql = "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$pending_feedback = $result['count'] ?? 0;

// Get recent system activities
$recent_activities = [];
$sql = "SELECT al.*, u.full_name, u.role as user_role 
        FROM audit_log al 
        LEFT JOIN users u ON al.user_id = u.user_id 
        ORDER BY al.created_at DESC LIMIT 8";
$stmt = $db->prepare($sql);
$recent_activities = $db->getMultiple($stmt);

// Get pending user registrations
$pending_registrations = [];
$sql = "SELECT user_id, full_name, email, role, created_at 
        FROM users 
        WHERE role IN ('employee', 'owner', 'tenant')
        ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$pending_registrations = $db->getMultiple($stmt);

include '../includes/header.php';
?>

<!-- Admin Dashboard Content -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?php echo t('admin_dashboard_title'); ?></h1>
                <div>
                    <span class="badge bg-success"><?php echo t('admin'); ?></span>
                    <span class="text-muted"><?php echo t('welcome_message', htmlspecialchars($_SESSION['user_name'] ?? 'Admin')); ?></span>
                </div>
            </div>
            
            <!-- Admin Core Tasks Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-white bg-opacity-25">
                                    <i class="fas fa-wallet fa-2x text-white"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0">ETB <?php echo number_format(max(0, $wallet_balance), 2); ?></h3>
                                    <p class="mb-0"><?php echo t('dashboard_wallet_balance'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 d-flex gap-2">
                            <a href="withdrawals.php" class="btn btn-sm btn-outline-light flex-grow-1">
                                <i class="fas fa-history me-1"></i><?php echo t('view_history'); ?>
                            </a>
                            <button class="btn btn-sm btn-light text-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                                <i class="fas fa-arrow-up me-1"></i><?php echo t('withdraw'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-white bg-opacity-25">
                                    <i class="fas fa-home fa-2x text-white"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0"><?php echo $stats['total_properties'] ?? 0; ?></h3>
                                    <p class="mb-0"><?php echo t('total_properties'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="properties.php" class="btn btn-sm btn-outline-light w-100">
                                <i class="fas fa-arrow-right me-1"></i><?php echo t('manage_properties'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-white bg-opacity-25">
                                    <i class="fas fa-comment-dots fa-2x text-white"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0"><?php echo $pending_feedback; ?></h3>
                                    <p class="mb-0"><?php echo t('pending_feedback'); ?></p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-white-50"><?php echo t('needs_review'); ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="feedback.php" class="btn btn-sm btn-outline-light w-100">
                                <i class="fas fa-arrow-right me-1"></i><?php echo t('review_now'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-white bg-opacity-25">
                                    <i class="fas fa-chart-bar fa-2x text-white"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0"><?php echo $stats['active_rentals'] ?? 0; ?></h3>
                                    <p class="mb-0"><?php echo t('active_rentals_label'); ?></p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-white-50"><?php echo t('system_overview'); ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="reports.php" class="btn btn-sm btn-outline-light w-100">
                                <i class="fas fa-arrow-right me-1"></i><?php echo t('view_reports'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tasks me-2"></i><?php echo t('admin_quick_actions'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <div class="d-grid">
                                        <a href="activities.php" class="btn btn-info btn-lg">
                                            <i class="fas fa-history me-2"></i>
                                            <span><?php echo t('system_activities'); ?></span>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="d-grid">
                                        <a href="feedback.php" class="btn btn-warning btn-lg">
                                            <i class="fas fa-comment-dots me-2"></i>
                                            <span><?php echo t('review_feedback'); ?></span>
                                            <?php if ($pending_feedback > 0): ?>
                                                <span class="badge bg-danger ms-2"><?php echo $pending_feedback; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="d-grid">
                                        <a href="reports.php" class="btn btn-success btn-lg">
                                            <i class="fas fa-chart-line me-2"></i>
                                            <span><?php echo t('generate_reports'); ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Dashboard Content -->
            <div class="row">
                <!-- Recent System Activities -->
                <div class="col-lg-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i><?php echo t('recent_activities'); ?>
                            </h5>
                            <a href="activities.php" class="btn btn-sm btn-outline-light"><?php echo t('view_all'); ?></a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_activities)): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-history fa-3x mb-3"></i>
                                    <p><?php echo t('no_recent_activities'); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="activity-timeline">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="d-flex mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="activity-icon bg-info bg-opacity-10 text-info rounded-circle p-2">
                                                    <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?> fa-sm"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    <?php if ($activity['full_name']): ?>
                                                        by <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                                        <span class="badge bg-secondary ms-1"><?php echo ucfirst($activity['user_role'] ?? 'Unknown'); ?></span>
                                                    <?php endif; ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i><?php echo formatDate($activity['created_at'], 'M d, H:i'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Admin Tasks Row -->
            <div class="row">
                <!-- System Status -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-server me-2"></i><?php echo t('system_status'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-database me-2"></i><?php echo t('system_status_database'); ?></span>
                                    <span class="badge bg-success"><?php echo t('connected'); ?></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-upload me-2"></i><?php echo t('system_status_file_uploads'); ?></span>
                                    <span class="badge bg-success"><?php echo t('working'); ?></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-envelope me-2"></i><?php echo t('system_status_email_service'); ?></span>
                                    <span class="badge bg-warning"><?php echo t('not_configured'); ?></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-backup me-2"></i><?php echo t('system_status_last_backup'); ?></span>
                                    <span class="badge bg-secondary"><?php echo t('never'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Maintenance -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools me-2"></i>System Maintenance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearTenantWalletModal">
                                    <i class="fas fa-wallet me-2"></i>Reset Tenant Wallets
                                </button>
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearHistoryModal">
                                    <i class="fas fa-trash me-2"></i>Clear Withdrawal History
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                These actions cannot be undone
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i><?php echo t('system_overview'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3 class="text-primary mb-1"><?php echo $stats['total_users'] ?? 0; ?></h3>
                                        <small class="text-muted"><?php echo t('total_users'); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3 class="text-success mb-1"><?php echo $stats['total_properties'] ?? 0; ?></h3>
                                        <small class="text-muted"><?php echo t('total_properties'); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3 class="text-warning mb-1"><?php echo $stats['active_rentals'] ?? 0; ?></h3>
                                        <small class="text-muted"><?php echo t('active_rentals_label'); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <h3 class="text-info mb-1"><?php echo $stats['pending_requests'] ?? 0; ?></h3>
                                        <small class="text-muted"><?php echo t('pending_label'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, ($stats['active_rentals'] ?? 0) * 10); ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo t('system_activity_level'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Helper function for activity icons
function getActivityIcon($action) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'register' => 'user-plus',
        'create' => 'plus',
        'update' => 'edit',
        'delete' => 'trash',
        'payment' => 'credit-card',
        'request' => 'paper-plane',
        'approval' => 'check',
        'rejection' => 'times',
        'maintenance' => 'tools',
        'feedback' => 'comment',
        'agreement' => 'file-contract',
        'property' => 'home',
        'user' => 'user'
    ];
    
    foreach ($icons as $key => $icon) {
        if (stripos($action, $key) !== false) {
            return $icon;
        }
    }
    
    return 'circle';
}
?>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title">
                    <i class="fas fa-arrow-up me-2"></i> <?php echo t('request_withdrawal'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm small">
                        <i class="fas fa-info-circle me-1"></i>
                        <?php echo t('available_balance'); ?> <strong>ETB <?php echo number_format(max(0, $wallet_balance), 2); ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('amount_to_withdraw'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0 bg-white">ETB</span>
                            <input type="number" class="form-control border-start-0" name="amount" 
                                   max="<?php echo $wallet_balance; ?>" min="10" step="0.01" required>
                        </div>
                        <small class="text-muted"><?php echo sprintf(t('minimum_withdrawal'), 10); ?></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('bank_name'); ?></label>
                        <select class="form-select" name="bank_name" required>
                            <option value=""><?php echo t('select_bank'); ?></option>
                            <option value="CBE">Commercial Bank of Ethiopia (CBE)</option>
                            <option value="Telebirr">Telebirr</option>
                            <option value="Abyssinia">Bank of Abyssinia</option>
                            <option value="Awash">Awash Bank</option>
                            <option value="Dashen">Dashen Bank</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('account_number'); ?></label>
                        <input type="text" class="form-control" name="account_number" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('authorization_password'); ?></label>
                        <input type="password" class="form-control" name="account_password" 
                               placeholder="<?php echo t('enter_system_password'); ?>" required>
                        <small class="text-muted"><?php echo t('required_to_authorize_withdrawal'); ?></small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn btn-danger px-4"><?php echo t('submit_request'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear Tenant Wallet Modal -->
<div class="modal fade" id="clearTenantWalletModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Reset Tenant Wallets
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger border-0 shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>

                <p>This will permanently:</p>
                <ul class="mb-3">
                    <li>Delete all wallet transaction history for selected tenants</li>
                    <li>Reset selected tenant wallet balances to 0.00 ETB</li>
                    <li>Clear all payment and deposit records for selected tenants</li>
                </ul>

                <p><strong>This action does not affect:</strong></p>
                <ul class="mb-3">
                    <li>Other tenant wallets</li>
                    <li>Owner and admin wallet balances</li>
                    <li>Employee wallet balances</li>
                    <li>Property rental agreements</li>
                    <li>Payment due amounts</li>
                </ul>

                <!-- Tenant Selection -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Tenants to Reset:</label>
                    <select class="form-select" id="tenantSelector" multiple size="6">
                        <?php
                        $stmt = $db->prepare("SELECT user_id, full_name, email FROM users WHERE role = 'tenant' ORDER BY full_name");
                        $tenants = $db->getMultiple($stmt);
                        foreach ($tenants as $tenant) {
                            echo "<option value='" . $tenant['user_id'] . "'>" . htmlspecialchars($tenant['full_name']) . " (" . htmlspecialchars($tenant['email']) . ")</option>";
                        }
                        ?>
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple tenants</small>
                </div>

                <div id="selectedTenants" class="mb-3" style="display: none;">
                    <label class="form-label fw-bold">Selected Tenants:</label>
                    <div id="selectedTenantsList" class="border rounded p-2 bg-light"></div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmTenantWalletClear">
                    <label class="form-check-label fw-bold" for="confirmTenantWalletClear">
                        I understand this action cannot be undone and will reset selected tenant wallets
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Type "RESET" to confirm:</label>
                    <input type="text" class="form-control" id="tenantWalletConfirmation" placeholder="Type RESET here">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="clearTenantWalletBtn" disabled>
                    <i class="fas fa-wallet me-2"></i>Reset Selected Tenant Wallets
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Clear History Modal (existing) -->
<div class="modal fade" id="clearHistoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Clear Withdrawal History
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
    if (withdrawForm) {
        withdrawForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?php echo addslashes(t('processing')); ?>';
            
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
                    alert(data.message || data.error || '<?php echo addslashes(t('withdrawal_request_failed')); ?>');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo addslashes(t('an_error_occurred_please_try_again')); ?>');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }
});

function activateUser(userId) {
    if (confirm('<?php echo addslashes(t('confirm_activate_user')); ?>')) {
        fetch('../api/activate-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                action: 'activate'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('<?php echo addslashes(t('user_account_activated')); ?>');
                location.reload();
            } else {
                alert('<?php echo addslashes(t('error_prefix')); ?>' + ' ' + (data.message || ''));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo addslashes(t('an_error_occurred_please_try_again')); ?>');
        });
    }
}

function rejectUser(userId) {
    if (confirm('<?php echo addslashes(t('confirm_reject_user')); ?>')) {
        fetch('../api/activate-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                action: 'reject'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('<?php echo addslashes(t('user_account_rejected')); ?>');
                location.reload();
            } else {
                alert('<?php echo addslashes(t('error_prefix')); ?>' + ' ' + (data.message || ''));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo addslashes(t('an_error_occurred_please_try_again')); ?>');
        });
    }
}

// Tenant Wallet Reset functionality
document.addEventListener('DOMContentLoaded', function() {
    const clearTenantWalletModal = document.getElementById('clearTenantWalletModal');
    const clearTenantWalletBtn = document.getElementById('clearTenantWalletBtn');
    const confirmTenantWalletClear = document.getElementById('confirmTenantWalletClear');
    const tenantWalletConfirmation = document.getElementById('tenantWalletConfirmation');
    const tenantSelector = document.getElementById('tenantSelector');
    const selectedTenants = document.getElementById('selectedTenants');
    const selectedTenantsList = document.getElementById('selectedTenantsList');
    
    function updateSelectedTenants() {
        const selectedOptions = Array.from(tenantSelector.selectedOptions);
        const selectedNames = selectedOptions.map(option => option.text);
        
        if (selectedNames.length > 0) {
            selectedTenants.style.display = 'block';
            selectedTenantsList.innerHTML = selectedNames.map(name => 
                `<span class="badge bg-danger me-1 mb-1">${name}</span>`
            ).join('');
        } else {
            selectedTenants.style.display = 'none';
            selectedTenantsList.innerHTML = '';
        }
        
        updateTenantWalletButton();
    }
    
    function updateTenantWalletButton() {
        const tenantsSelected = tenantSelector.selectedOptions.length > 0;
        const checkboxChecked = confirmTenantWalletClear && confirmTenantWalletClear.checked;
        const textCorrect = tenantWalletConfirmation && tenantWalletConfirmation.value.toUpperCase() === 'RESET';
        const isConfirmed = tenantsSelected && checkboxChecked && textCorrect;
        
        if (clearTenantWalletBtn) {
            clearTenantWalletBtn.disabled = !isConfirmed;
        }
    }
    
    if (tenantSelector) {
        tenantSelector.addEventListener('change', updateSelectedTenants);
    }
    
    if (confirmTenantWalletClear) {
        confirmTenantWalletClear.addEventListener('change', updateTenantWalletButton);
    }
    
    if (tenantWalletConfirmation) {
        tenantWalletConfirmation.addEventListener('input', updateTenantWalletButton);
        tenantWalletConfirmation.addEventListener('keyup', updateTenantWalletButton);
        tenantWalletConfirmation.addEventListener('change', updateTenantWalletButton);
    }
    
    // Also check when modal is shown
    if (clearTenantWalletModal) {
        clearTenantWalletModal.addEventListener('shown.bs.modal', function() {
            // Reset form when modal opens
            if (tenantSelector) tenantSelector.selectedIndex = -1;
            if (confirmTenantWalletClear) confirmTenantWalletClear.checked = false;
            if (tenantWalletConfirmation) tenantWalletConfirmation.value = '';
            updateSelectedTenants();
            updateTenantWalletButton();
        });
    }
    
    if (clearTenantWalletBtn) {
        clearTenantWalletBtn.addEventListener('click', function() {
            const selectedTenantIds = Array.from(tenantSelector.selectedOptions).map(option => option.value);
            
            if (selectedTenantIds.length === 0) {
                alert('Please select at least one tenant to reset.');
                return;
            }
            
            const selectedNames = Array.from(tenantSelector.selectedOptions).map(option => option.text).join(', ');
            if (!confirm(`Are you absolutely sure you want to reset wallets for: ${selectedNames}? This will delete all transaction history and set balances to zero!`)) {
                return;
            }
            
            clearTenantWalletBtn.disabled = true;
            clearTenantWalletBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Resetting...';
            
            fetch('../api/clear-tenant-wallet-history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tenant_ids: selectedTenantIds,
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
                    alert(data.error || 'Failed to reset tenant wallets');
                    clearTenantWalletBtn.disabled = false;
                    clearTenantWalletBtn.innerHTML = '<i class="fas fa-wallet me-2"></i>Reset Selected Tenant Wallets';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
                clearTenantWalletBtn.disabled = false;
                clearTenantWalletBtn.innerHTML = '<i class="fas fa-wallet me-2"></i>Reset Selected Tenant Wallets';
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>