<!-- Sidebar Navigation -->
<style>
.sidebar {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 24px;
    font-family: 'Inter', 'Roboto', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
.sidebar .card {
    border: none;
    border-radius: 24px;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 22px 45px rgba(15, 23, 42, 0.08);
}
.sidebar .card-header {
    background: #ffffff;
    border: none;
    padding: 1.3rem 1.4rem;
    color: #131314;
}
.sidebar .card-header h6 {
    margin-bottom: 0.25rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    font-size: 1rem;
}
.sidebar .card-header small {
    color: #7c8899;
    display: block;
    margin-top: 0.25rem;
    font-size: 0.85rem;
}
.sidebar .card-header.sidebar-header {
    background: #1954ca;
    color: #ffffff;
}
.sidebar .card-header.sidebar-header small {
    color: #cbd5e1;
}
.sidebar .quick-stats {
    background: #ffffff;
    border-radius: 18px;
    padding: 1rem;
    margin: 1rem 1rem 0;
    box-shadow: 0 18px 35px rgba(15, 23, 42, 0.05);
    border: 1px solid rgba(15, 23, 42, 0.05);
}
.sidebar .quick-stats .stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}
.sidebar .quick-stats .stat-item:last-child {
    border-bottom: none;
}
.sidebar .quick-stats .stat-label {
    color: #64748b;
    font-size: 0.82rem;
}
.sidebar .quick-stats .stat-value {
    font-weight: 700;
    font-size: 1.15rem;
    color: #0f172a;
}
.sidebar .list-group-item {
    border: none;
    background: transparent;
    color: #334155;
    padding: 0.95rem 1rem;
    transition: all 0.25s ease;
    position: relative;
    border-radius: 14px;
    margin: 0.15rem 0;
}
.sidebar .list-group-item:hover {
    background-color: #eff6ff;
    color: #1d4ed8;
    transform: translateX(1px);
}
.sidebar .list-group-item.active {
    background: #eef4ff;
    border-left: 4px solid #2563eb;
    font-weight: 600;
    color: #1d4ed8;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}
.sidebar .list-group-item i,
.sidebar .list-group-item.active i {
    width: 1.4rem;
    text-align: center;
}
.sidebar .badge {
    font-size: 0.72rem;
    padding: 0.35rem 0.55rem;
    background: rgba(59, 130, 246, 0.12);
    color: #1e40af;
    border-radius: 999px;
}
.sidebar .nav-section {
    padding: 0.85rem 1rem 0.45rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}
.sidebar .list-group-item .badge {
    margin-top: 0.15rem;
}

/* Hide mobile elements on desktop */
.sidebar-toggle,
.sidebar-overlay {
    display: none;
}

/* Mobile Overlay Styles */
@media (max-width: 768px) {
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.55);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        z-index: 1040;
        pointer-events: none;
    }
    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .sidebar {
        position: fixed;
        top: 0;
        left: -280px;
        width: 280px;
        height: 100%;
        transition: left 0.3s ease;
        z-index: 1050;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    .sidebar.active {
        left: 0;
    }
    .sidebar-toggle {
        display: block !important;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1060;
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        color: #0f172a;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sidebar-toggle .hamburger,
    .sidebar-toggle .hamburger::before,
    .sidebar-toggle .hamburger::after {
        display: block;
        width: 20px;
        height: 2px;
        background: currentColor;
        border-radius: 1px;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    .sidebar-toggle .hamburger {
        position: relative;
    }
    .sidebar-toggle .hamburger::before,
    .sidebar-toggle .hamburger::after {
        content: '';
        position: absolute;
    }
    .sidebar-toggle .hamburger::before {
        transform: translateY(-6px);
    }
    .sidebar-toggle .hamburger::after {
        transform: translateY(6px);
    }
    .sidebar-toggle.active .hamburger {
        background: transparent;
    }
    .sidebar-toggle.active .hamburger::before {
        transform: rotate(45deg);
    }
    .sidebar-toggle.active .hamburger::after {
        transform: rotate(-45deg);
    }
    .sidebar-toggle.active .hamburger::before,
    .sidebar-toggle.active .hamburger::after {
        transform-origin: center;
    }
}

/* Ensure main content takes full width on mobile */
@media (max-width: 768px) {
    .sidebar + .col-lg-9,
    .sidebar ~ .col-lg-9 {
        width: 100% !important;
        margin-left: 0 !important;
    }
}
</style>

<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
    <span class="hamburger"></span>
</button>

<div class="sidebar">
    <div class="card">
        <div class="card-header sidebar-header text-white">
            <h6 class="mb-0">
                <i class="fas fa-user me-2"></i>
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
            </h6>
            <?php
                $role_labels = [
                    'owner' => t('owner'),
                    'tenant' => t('tenant'),
                    'admin' => t('admin'),
                    'employee' => t('employee'),
                    'user' => t('user'),
                ];
            ?>
            <small class="text-muted sidebar-role-text">
                <?php echo htmlspecialchars($role_labels[$_SESSION['user_role'] ?? 'user'] ?? t('user')); ?>
            </small>
        </div>
        <?php if (isset($db)): ?>
            <div class="quick-stats">
                <?php
                    $stats_items = [];
                    $user_id = $_SESSION['user_id'] ?? 0;
                    if ($_SESSION['user_role'] === 'tenant') {
                        $sql = "SELECT COUNT(*) AS count FROM rental_requests WHERE tenant_id = ? AND status = 'pending'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$user_id]);
                        $stats_items[] = ['label' => t('pending_requests'), 'value' => $result['count'] ?? 0];

                        $sql = "SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$user_id]);
                        $stats_items[] = ['label' => t('unread_notifications'), 'value' => $result['count'] ?? 0];
                    } elseif ($_SESSION['user_role'] === 'owner') {
                        $sql = "SELECT COUNT(*) AS count FROM rental_requests rr JOIN properties p ON rr.property_id = p.property_id WHERE p.owner_id = ? AND rr.status = 'pending'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$user_id]);
                        $stats_items[] = ['label' => t('pending_requests'), 'value' => $result['count'] ?? 0];

                        $sql = "SELECT COUNT(*) AS count FROM maintenance_requests mr JOIN properties p ON mr.property_id = p.property_id WHERE p.owner_id = ? AND mr.status = 'pending'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$user_id]);
                        $stats_items[] = ['label' => t('maintenance'), 'value' => $result['count'] ?? 0];
                    } else {
                        $sql = "SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$user_id]);
                        $stats_items[] = ['label' => t('unread_alerts'), 'value' => $result['count'] ?? 0];

                        $sql = "SELECT COUNT(*) AS count FROM users";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, []);
                        $stats_items[] = ['label' => t('total_users'), 'value' => $result['count'] ?? 0];
                    }
                ?>
                <?php foreach ($stats_items as $item): ?>
                    <div class="stat-item">
                        <div class="stat-label"><?php echo htmlspecialchars($item['label']); ?></div>
                        <div class="stat-value"><?php echo htmlspecialchars($item['value']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="list-group list-group-flush">
            <?php if ($_SESSION['user_role'] === 'owner'): ?>
                <a href="../owner/dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                </a>
                <a href="../owner/properties.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'properties.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> <?php echo t('my_properties'); ?>
                </a>
                <a href="../owner/add-property.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'add-property.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle me-2"></i> <?php echo t('add_property'); ?>
                </a>
                <a href="../owner/requests.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox me-2"></i> <?php echo t('rental_requests'); ?>
                    <span class="badge bg-danger float-end" id="pending-count">0</span>
                </a>
                <a href="../owner/support.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'support.php' ? 'active' : ''; ?>">
                    <i class="fas fa-headset me-2"></i> <?php echo t('tenant_support_chats'); ?>
                </a>
                <a href="../owner/notifications.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell me-2"></i> <?php echo t('notifications'); ?>
                    <span class="badge bg-warning float-end" id="notification-count">0</span>
                </a>
                <a href="../owner/maintenance.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools me-2"></i> <?php echo t('maintenance'); ?>
                    <span class="badge bg-warning float-end" id="maintenance-count">0</span>
                </a>
                <a href="../owner/feedback.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star me-2"></i> <?php echo t('feedback'); ?>
                    <span class="badge bg-warning float-end" id="feedback-count">0</span>
                </a>
                <a href="../owner/news.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper me-2"></i> <?php echo t('news_announcements'); ?>
                </a>
                <a href="../owner/tenants.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'tenants.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> <?php echo t('my_tenants'); ?>
                </a>
                <a href="../owner/payments.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave me-2"></i> <?php echo t('payments'); ?>
                </a>
                <a href="../owner/withdrawals.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'withdrawals.php' ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding-usd me-2"></i> <?php echo t('withdrawals'); ?>
                </a>
                <a href="../owner/reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i> <?php echo t('reports'); ?>
                </a>
                <a href="../owner/profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle me-2"></i> <?php echo t('profile'); ?>
                </a>
            <?php elseif ($_SESSION['user_role'] === 'tenant'): ?>
                <div class="nav-section"><?php echo t('main_section'); ?></div>
                <a href="../tenant/dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                </a>
                <a href="../tenant/search.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search me-2"></i> <?php echo t('search_properties'); ?>
                </a>
                
                <div class="nav-section"><?php echo t('rental_management_section'); ?></div>
                <a href="../tenant/requests.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane me-2"></i> <?php echo t('my_requests'); ?>
                    <?php 
                    // Get pending requests count
                    $pending_count = 0;
                    if (isset($db)) {
                        $sql = "SELECT COUNT(*) as count FROM rental_requests WHERE tenant_id = ? AND status = 'pending'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$_SESSION['user_id']]);
                        $pending_count = $result['count'] ?? 0;
                    }
                    if ($pending_count > 0): ?>
                        <span class="badge bg-warning float-end"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../tenant/agreements.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'agreements.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-contract me-2"></i> <?php echo t('rental_agreements'); ?>
                </a>
                <a href="../tenant/payments.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card me-2"></i> <?php echo t('make_payment'); ?>
                    <?php 
                    // Get overdue payments count
                    $overdue_count = 0;
                    if (isset($db)) {
                        $sql = "SELECT COUNT(*) as count FROM rental_agreements ra 
                                LEFT JOIN payments p ON ra.agreement_id = p.agreement_id AND p.payment_status = 'Verified'
                                WHERE ra.tenant_id = ? AND ra.status = 'active' 
                                AND (p.payment_id IS NULL OR p.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$_SESSION['user_id']]);
                        $overdue_count = $result['count'] ?? 0;
                    }
                    if ($overdue_count > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $overdue_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../tenant/payment-history.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'payment-history.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history me-2"></i> <?php echo t('payment_history'); ?>
                </a>
                <a href="../tenant/maintenance-direct.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance-direct.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools me-2"></i> <?php echo t('maintenance'); ?>
                    <?php 
                    // Get pending maintenance count
                    $maintenance_count = 0;
                    if (isset($db)) {
                        $sql = "SELECT COUNT(*) as count FROM maintenance_requests WHERE tenant_id = ? AND status = 'pending'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$_SESSION['user_id']]);
                        $maintenance_count = $result['count'] ?? 0;
                    }
                    if ($maintenance_count > 0): ?>
                        <span class="badge bg-info float-end"><?php echo $maintenance_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../tenant/vacating-notice.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'vacating-notice.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo t('vacating_notice'); ?>
                </a>
                
                <div class="nav-section"><?php echo t('communication_section'); ?></div>
                <a href="../tenant/notifications.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell me-2"></i> <?php echo t('notifications'); ?>
                    <?php 
                    // Get unread notifications count
                    $unread_count = 0;
                    if (isset($db)) {
                        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt, [$_SESSION['user_id']]);
                        $unread_count = $result['count'] ?? 0;
                    }
                    if ($unread_count > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../tenant/support.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'support.php' ? 'active' : ''; ?>">
                    <i class="fas fa-headset me-2"></i> <?php echo t('support_center'); ?>
                </a>
                <a href="../tenant/feedback.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comment me-2"></i> <?php echo t('feedback'); ?>
                </a>
                <a href="../tenant/news.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper me-2"></i> <?php echo t('news_announcements'); ?>
                </a>
                
                <div class="nav-section"><?php echo t('account_section'); ?></div>
                <a href="../tenant/rental-history.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'rental-history.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i> <?php echo t('rental_history'); ?>
                </a>
                <a href="../tenant/profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user me-2"></i> <?php echo t('profile'); ?>
                </a>
                
                <div class="nav-section"><?php echo t('reports_section'); ?></div>
                <a href="../tenant/reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i> <?php echo t('my_reports'); ?>
                </a>
            <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                <a href="../admin/dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                </a>
                <a href="../admin/activities.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'activities.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history me-2"></i> <?php echo t('system_activities'); ?>
                </a>
                <a href="../admin/users.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> <?php echo t('users'); ?>
                </a>
                <a href="../admin/feedback.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comment-dots me-2"></i> <?php echo t('review_feedback'); ?>
                </a>
                <a href="../admin/reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i> <?php echo t('reports'); ?>
                </a>
                <div class="nav-section"><?php echo t('notifications_section'); ?></div>
                <a href="../admin/notifications.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell me-2"></i> <?php echo t('notifications'); ?>
                </a>
                <a href="../admin/notification-create.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'notification-create.php' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane me-2"></i> <?php echo t('create_notification'); ?>
                </a>
                <a href="../admin/payments.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave me-2"></i> <?php echo t('payments'); ?>
                </a>
                <a href="../admin/wallets.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'wallets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wallet me-2"></i> <?php echo t('system_wallets'); ?>
                </a>
                <a href="../admin/withdrawals.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'withdrawals.php' ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding-usd me-2"></i> <?php echo t('withdrawals'); ?>
                </a>
                <a href="../admin/settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog me-2"></i> <?php echo t('settings'); ?>
                </a>
                
                <div class="nav-section"><?php echo t('system_management_section'); ?></div>
                <a href="../admin/manage-news.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'manage-news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper me-2"></i> <?php echo t('manage_system_news'); ?>
                    <span class="badge bg-primary float-end">New</span>
                </a>

                <a href="../admin/profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle me-2"></i> <?php echo t('profile'); ?>
                </a>
            <?php elseif ($_SESSION['user_role'] === 'employee'): ?>
                <a href="../employee/dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                </a>
                
                <div class="nav-section"><?php echo t('property_management_section'); ?></div>
                <a href="../employee/property-review.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'property-review.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check me-2"></i> <?php echo t('property_review'); ?>
                </a>
                <a href="../employee/properties.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'properties.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> <?php echo t('property_management'); ?>
                </a>
                
                <div class="nav-section"><?php echo t('system_management_section'); ?></div>
                <a href="../employee/manage-news.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'manage-news.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper me-2"></i> <?php echo t('manage_system_news'); ?>
                    <span class="badge bg-primary float-end">New</span>
                </a>
                
                <div class="nav-section"><?php echo t('support_communication_section'); ?></div>
                <a href="../employee/support-inquiries.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'support-inquiries.php' ? 'active' : ''; ?>">
                    <i class="fas fa-headset me-2"></i> <?php echo t('support_inquiries'); ?>
                    <span class="badge bg-danger float-end" id="employee-tickets-count">0</span>
                </a>
                <a href="../employee/feedback.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comment me-2"></i> <?php echo t('user_feedback'); ?>
                    <span class="badge bg-info float-end" id="employee-feedback-count">0</span>
                </a>
                <a href="../employee/notifications.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell me-2"></i> <?php echo t('system_notifications'); ?>
                    <span class="badge bg-warning float-end" id="employee-notification-count">0</span>
                </a>
                
                <div class="nav-section"><?php echo t('reports_analytics_section'); ?></div>
                                <a href="../employee/reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i> <?php echo t('reports'); ?>
                </a>
                <a href="../employee/profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle me-2"></i> <?php echo t('profile'); ?>
                </a>
            <?php endif; ?>
            
            <div class="list-group-item text-danger">
                <a href="../logout.php" class="text-decoration-none text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> <?php echo t('logout'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0"><?php echo t('quick_stats'); ?></h6>
        </div>
        <div class="card-body">
            <div class="small text-muted">
                <?php if ($_SESSION['user_role'] === 'owner'): ?>
                    <div class="mb-2">
                        <i class="fas fa-home text-primary me-1"></i>
                        <span id="owner-properties-count">0</span> <?php echo t('properties'); ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-inbox text-warning me-1"></i>
                        <span id="owner-requests-count">0</span> <?php echo t('pending_requests'); ?>
                    </div>
                    <div>
                        <i class="fas fa-users text-success me-1"></i>
                        <span id="owner-tenants-count">0</span> <?php echo t('active_tenants'); ?>
                    </div>
                <?php elseif ($_SESSION['user_role'] === 'tenant'): ?>
                    <div class="mb-2">
                        <i class="fas fa-file-contract text-primary me-1"></i>
                        <span id="tenant-agreements-count">0</span> <?php echo t('active_rentals_label'); ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-paper-plane text-warning me-1"></i>
                        <span id="tenant-requests-count">0</span> <?php echo t('pending_requests'); ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-bell text-danger me-1"></i>
                        <span id="tenant-notifications-count">0</span> <?php echo t('unread_notifications'); ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-tools text-info me-1"></i>
                        <span id="tenant-maintenance-count">0</span> <?php echo t('maintenance_issues'); ?>
                    </div>
                    <div>
                        <i class="fas fa-credit-card text-success me-1"></i>
                        <span id="tenant-payments-count">0</span> <?php echo t('payments_made'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($_SESSION['user_role'] === 'tenant'): ?>
        <div id="news-widget-container" style="display: none;">
            <?php include '../includes/news-widget-tenant.php'; ?>
        </div>
    <?php elseif ($_SESSION['user_role'] === 'owner'): ?>
        <div id="news-widget-container" style="display: none;">
            <?php include '../includes/news-widget.php'; ?>
        </div>
    <?php endif; ?>
    
    </div>

<script>
// Toggle news widget function
function toggleNewsWidget() {
    const newsWidget = document.getElementById('news-widget-container');
    if (newsWidget) {
        const currentDisplay = window.getComputedStyle(newsWidget).display;
        console.log('Current display:', currentDisplay);
        
        if (currentDisplay === 'none') {
            newsWidget.style.display = 'block';
            console.log('Showing news widget');
        } else {
            newsWidget.style.display = 'none';
            console.log('Hiding news widget');
        }
    } else {
        console.log('News widget container not found');
    }
}

// Load quick stats based on user role
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($_SESSION['user_role'] === 'owner'): ?>
        // Load owner stats
        fetch('../api/owner-stats.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('owner-properties-count').textContent = data.total_properties || 0;
                document.getElementById('owner-requests-count').textContent = data.pending_requests || 0;
                document.getElementById('owner-tenants-count').textContent = data.active_tenants || 0;
                document.getElementById('pending-count').textContent = data.pending_requests || 0;
                document.getElementById('notification-count').textContent = data.unread_notifications || 0;
                document.getElementById('maintenance-count').textContent = data.pending_maintenance || 0;
                document.getElementById('feedback-count').textContent = data.pending_feedback || 0;
            })
            .catch(error => console.log('Stats loading error:', error));
    <?php elseif ($_SESSION['user_role'] === 'employee'): ?>
        // Load employee stats
        fetch('../api/employee-stats.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('employee-requests-count').textContent = data.pending_requests || 0;
                document.getElementById('employee-feedback-count').textContent = data.pending_feedback || 0;
                document.getElementById('employee-tickets-count').textContent = data.open_tickets || 0;
                document.getElementById('employee-notification-count').textContent = data.unread_notifications || 0;
            })
            .catch(error => console.log('Stats loading error:', error));
    <?php elseif ($_SESSION['user_role'] === 'tenant'): ?>
        // Load tenant stats
        fetch('../api/tenant-stats.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('tenant-requests-count').textContent = data.pending_requests || 0;
                document.getElementById('tenant-agreements-count').textContent = data.active_rentals || 0;
                document.getElementById('tenant-notifications-count').textContent = data.unread_notifications || 0;
                document.getElementById('tenant-maintenance-count').textContent = data.pending_maintenance || 0;
                document.getElementById('tenant-payments-count').textContent = data.total_payments || 0;
            })
            .catch(error => console.log('Stats loading error:', error));
    <?php endif; ?>

    // Mobile sidebar functionality
    if (window.matchMedia('(max-width: 768px)').matches) {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarLinks = sidebar.querySelectorAll('.list-group-item-action');

        function toggleSidebar() {
            const isActive = sidebar.classList.contains('active');
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            sidebarToggle.classList.toggle('active');
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            sidebarToggle.classList.remove('active');
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeSidebar();
                // Smooth scroll to top for same-page navigation
                if (link.getAttribute('href').includes(window.location.pathname.split('/').pop())) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    }
});
</script>
