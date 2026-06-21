<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Tenant Dashboard";

// Get user info
$user_id = $session->getUserId();
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$user = $db->getSingle($stmt, [$user_id]);

// Get dashboard statistics - enhanced with payment data
$stats = [
    'active_agreements' => 0,
    'pending_requests' => 0,
    'total_payments' => 0,
    'verified_payments' => 0,
    'pending_payments' => 0,
    'balance_remaining' => 0,
    'unread_notifications' => 0,
    'monthly_payments' => 0
];

try {
    // Active agreements
    $sql = "SELECT COUNT(*) as count FROM rental_agreements WHERE tenant_id = ? AND status IN ('active', 'partially_paid')";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $result = $db->getSingle($stmt, [$user_id]);
        $stats['active_agreements'] = $result['count'];
    }
} catch (Exception $e) {
    $stats['active_agreements'] = 0;
}

try {
    // Pending requests
    $sql = "SELECT COUNT(*) as count FROM rental_requests WHERE tenant_id = ? AND status = 'pending'";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $result = $db->getSingle($stmt, [$user_id]);
        $stats['pending_requests'] = $result['count'];
    }
} catch (Exception $e) {
    $stats['pending_requests'] = 0;
}

try {
    // Payment statistics
    $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(CASE WHEN payment_status = 'Verified' THEN 1 ELSE 0 END) as verified_payments,
                SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) as pending_payments,
                COALESCE(SUM(balance_remaining), 0) as balance_remaining
            FROM payments WHERE tenant_id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $result = $db->getSingle($stmt, [$user_id]);
        $stats['total_payments'] = $result['total_payments'] ?? 0;
        $stats['verified_payments'] = $result['verified_payments'] ?? 0;
        $stats['pending_payments'] = $result['pending_payments'] ?? 0;
        $stats['balance_remaining'] = $result['balance_remaining'] ?? 0;
    }
} catch (Exception $e) {
    $stats['total_payments'] = 0;
    $stats['verified_payments'] = 0;
    $stats['pending_payments'] = 0;
    $stats['balance_remaining'] = 0;
}

// This month's verified payment total
try {
    $sql = "SELECT COALESCE(SUM(amount),0) AS month_total
            FROM payments
            WHERE tenant_id = ?
              AND payment_status = 'Verified'
              AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $result = $db->getSingle($stmt, [$user_id]);
        $stats['monthly_payments'] = $result['month_total'] ?? 0;
    }
} catch (Exception $e) {
    $stats['monthly_payments'] = 0;
}

try {
    // Unread notifications
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $result = $db->getSingle($stmt, [$user_id]);
        $stats['unread_notifications'] = $result['count'];
    }
} catch (Exception $e) {
    $stats['unread_notifications'] = 0;
}

// Recent activity - simplified
$recent_activity = [];
try {
    $sql = "SELECT amount, created_at as activity_date, 'Payment' as description 
            FROM payments WHERE tenant_id = ? AND payment_status = 'Verified'
            ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $recent_activity = $db->getMultiple($stmt, [$user_id]);
    }
} catch (Exception $e) {
    $recent_activity = [];
}

// Active rentals - simplified
$active_rentals = [];
try {
    $sql = "SELECT ra.*, p.title, p.monthly_rent 
            FROM rental_agreements ra 
            JOIN properties p ON ra.property_id = p.property_id 
            WHERE ra.tenant_id = ? AND ra.status = 'active'
            ORDER BY ra.created_at DESC LIMIT 3";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $rentals = $db->getMultiple($stmt, [$user_id]);
        foreach ($rentals as $rental) {
            $rental['location_name'] = 'Addis Ababa';
            $active_rentals[] = $rental;
        }
    }
} catch (Exception $e) {
    $active_rentals = [];
}

// Recent properties - simplified
$recent_properties = [];
try {
    $sql = "SELECT p.*, 'Addis Ababa' as location_name 
            FROM properties p 
            WHERE p.status = 'available' 
            ORDER BY p.created_at DESC LIMIT 3";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $recent_properties = $db->getMultiple($stmt);
    }
} catch (Exception $e) {
    $recent_properties = [];
}

// Latest news for tenants
$latest_news = [];
try {
    $sql = "SELECT sn.*, nc.category_name, nc.color as category_color, u.full_name as author_name,
            COALESCE((SELECT COUNT(*) FROM news_views nv WHERE nv.news_id = sn.news_id), 0) as view_count
            FROM system_news sn
            LEFT JOIN news_categories nc ON sn.category_id = nc.category_id
            LEFT JOIN users u ON sn.created_by = u.user_id
            WHERE sn.status = 'published' 
            AND (sn.expiry_date IS NULL OR sn.expiry_date > NOW())
            AND sn.target_audience IN ('tenants', 'all')
            ORDER BY COALESCE(sn.featured, 0) DESC, 
                     CASE sn.priority 
                         WHEN 'urgent' THEN 4 
                         WHEN 'high' THEN 3 
                         WHEN 'medium' THEN 2 
                         WHEN 'low' THEN 1 
                         ELSE 0 
                     END DESC, 
                     sn.publication_date DESC
            LIMIT 3";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $news_data = $db->getMultiple($stmt);
        // Ensure all articles have required keys with default values
        foreach ($news_data as &$article) {
            $article = array_merge([
                'featured' => 0,
                'view_count' => 0,
                'content' => '',
                'expiry_date' => null,
                'allow_comments' => 0,
                'target_audience' => 'all',
                'created_at' => date('Y-m-d H:i:s'),
                'publication_date' => date('Y-m-d H:i:s'),
                'priority' => 'medium',
                'excerpt' => ''
            ], $article);
        }
        $latest_news = $news_data;
    }
} catch (Exception $e) {
    $latest_news = [];
}

include '../includes/header.php';
?>

<style>
.dashboard-gradient {
    background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
    color: white;
    overflow: visible;
    position: relative;
}
.dashboard-card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 15px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}
.stat-card {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}
.activity-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.3s ease;
}
.activity-item:hover {
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 0 -0.75rem;
    padding: 0.75rem;
}
.rental-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
}
.rental-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.rental-image {
    height: 150px;
    object-fit: cover;
}
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4757;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
}
.quick-action-btn {
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}
.dashboard-content {
    padding: 1rem;
}
@media (max-width: 768px) {
    .dashboard-gradient .row {
        row-gap: 1rem;
    }
    .dashboard-gradient .text-end {
        text-align: left !important;
    }
}

/* Hero stats chips to avoid hidden/oversized blocks */
.stat-row {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: stretch;
}
.stat-chip {
    display: inline-block;
    background: rgba(255, 255, 255, 0.18);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    min-width: 120px;
    text-align: center;
    color: #fff;
    backdrop-filter: blur(2px);
}
/* Payment Modal Styles */
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
</style>

<div class="main-content">
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="dashboard-content">
            <!-- Professional Welcome Banner -->
            <div class="dashboard-gradient rounded-4 p-4 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-white bg-opacity-20 rounded-circle p-1 me-3" style="width:64px; height:64px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="rounded-circle" style="width:56px; height:56px; object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-user fa-2x"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h1 class="h3 mb-1">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                                <p class="mb-0 opacity-90">Here's your rental dashboard overview</p>
                            </div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-chip">
                                <div class="h5 mb-0"><?php echo $stats['active_agreements']; ?></div>
                                <small>Active Rentals</small>
                            </div>
                            <div class="stat-chip">
                                <div class="h5 mb-0">ETB <?php echo number_format($stats['monthly_payments'] ?? 0, 0); ?></div>
                                <small>This Month</small>
                            </div>
                            <div class="stat-chip">
                                <div class="h5 mb-0"><?php echo $stats['unread_notifications']; ?></div>
                                <small>Notifications</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="search.php" class="btn btn-light btn-lg rounded-3">
                            <i class="fas fa-search me-2"></i>Find Property
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Quick Actions</h5>
                            <div class="row g-3">
                                <div class="col-md-3 col-6">
                                    <a href="my-rentals.php" class="quick-action-btn btn-primary w-100">
                                        <i class="fas fa-home"></i>
                                        <span>My Rentals</span>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="payments.php" class="quick-action-btn btn-success w-100">
                                        <i class="fas fa-credit-card"></i>
                                        <span>Make Payment</span>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="requests.php" class="quick-action-btn btn-warning w-100">
                                        <i class="fas fa-list"></i>
                                        <span>Requests</span>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="maintenance-direct.php" class="quick-action-btn btn-info w-100">
                                        <i class="fas fa-tools"></i>
                                        <span>Maintenance</span>
                                    </a>
                                </div>
                                
                            </div>
                        </div>
                </div>
            </div>
            </div>

            <!-- Payment Overview -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-credit-card me-2"></i>Payment Overview
                            </h5>
                            <div class="row g-3 mb-3">
                                <div class="col-md-3 col-6">
                                    <div class="text-center">
                                        <div class="h4 mb-0 text-primary"><?php echo $stats['total_payments']; ?></div>
                                        <small class="text-muted">Total Payments</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="text-center">
                                        <div class="h4 mb-0 text-success"><?php echo $stats['verified_payments']; ?></div>
                                        <small class="text-muted">Verified</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="text-center">
                                        <div class="h4 mb-0 text-warning"><?php echo $stats['pending_payments']; ?></div>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="text-center">
                                        <div class="h4 mb-0 text-info">ETB <?php echo number_format($stats['balance_remaining'], 0); ?></div>
                                        <small class="text-muted">Balance Due</small>
                                    </div>
                                </div>
                            </div>
                            <?php if ($stats['balance_remaining'] > 0): ?>
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Payment Due:</strong> You have ETB <?php echo number_format($stats['balance_remaining'], 0); ?> remaining to pay.
                                    <button type="button" class="alert-link btn btn-link p-0 quick-pay-btn" 
                                            data-agreement-id="<?php echo $active_agreements[0]['agreement_id'] ?? ''; ?>" 
                                            data-property-title="<?php echo htmlspecialchars($active_agreements[0]['title'] ?? ''); ?>" 
                                            data-amount="<?php echo $active_agreements[0]['monthly_rent'] ?? 0; ?>" 
                                            data-due-date="<?php echo $active_agreements[0]['end_date'] ?? date('Y-m-d'); ?>" 
                                            data-property-id="<?php echo $active_agreements[0]['property_id'] ?? ''; ?>">
                                        Make Payment
                                    </button>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-primary btn-sm quick-pay-btn" 
                                        data-agreement-id="<?php echo $active_agreements[0]['agreement_id'] ?? ''; ?>" 
                                        data-property-title="<?php echo htmlspecialchars($active_agreements[0]['title'] ?? ''); ?>" 
                                        data-amount="<?php echo $active_agreements[0]['monthly_rent'] ?? 0; ?>" 
                                        data-due-date="<?php echo $active_agreements[0]['end_date'] ?? date('Y-m-d'); ?>" 
                                        data-property-id="<?php echo $active_agreements[0]['property_id'] ?? ''; ?>">
                                    <i class="fas fa-credit-card me-2"></i>Make Payment
                                </button>
                                <a href="payment-history.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-history me-2"></i>View History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </h5>
                            <?php if ($stats['unread_notifications'] > 0): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You have <?php echo $stats['unread_notifications']; ?> unread notifications.
                                    <a href="notifications.php" class="alert-link">View All</a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                    <p class="text-muted small">No new notifications</p>
                                </div>
                            <?php endif; ?>
                            <div class="d-grid gap-2">
                                <a href="notifications.php" class="btn btn-outline-primary btn-sm">View Notifications</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <!-- Active Rentals -->
                <div class="col-lg-8 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-home me-2"></i>Active Rentals
                            </h5>
                            <?php if (empty($active_rentals)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No active rentals found</p>
                                    <a href="search.php" class="btn btn-primary">Find Properties</a>
                                </div>
                            <?php else: ?>
                                                <div class="row">
                                                    <?php foreach ($active_rentals as $rental): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <div class="rental-card h-100">
                                                                <div class="card-body">
                                                                    <h6 class="card-title"><?php echo htmlspecialchars($rental['title']); ?></h6>
                                                                    <p class="text-muted small mb-2">
                                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                                        <?php echo htmlspecialchars($rental['location_name']); ?>
                                                                    </p>
                                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                                        <span class="fw-bold text-primary">ETB <?php echo number_format($rental['monthly_rent'], 0); ?></span>
                                                                        <small class="text-muted">/month</small>
                                                                    </div>
                                                                    <button type="button" class="btn btn-primary btn-sm w-100 quick-pay-btn"
                                                                            data-agreement-id="<?php echo $rental['agreement_id']; ?>"
                                                                            data-property-title="<?php echo htmlspecialchars($rental['title']); ?>"
                                                                            data-amount="<?php echo $rental['monthly_rent']; ?>"
                                                                            data-due-date="<?php echo $rental['end_date']; ?>"
                                                                            data-property-id="<?php echo $rental['property_id']; ?>">
                                                                        <i class="fas fa-credit-card me-2"></i>Pay Now
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-lg-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-clock me-2"></i>Recent Activity
                            </h5>
                            <?php if (empty($recent_activity)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                    <p class="text-muted small">No recent activity</p>
                                </div>
                            <?php else: ?>
                                <div class="activity-list">
                                    <?php foreach ($recent_activity as $activity): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($activity['description']); ?></div>
                                                    <small class="text-muted">ETB <?php echo number_format($activity['amount'], 0); ?></small>
                                                </div>
                                                <small class="text-muted"><?php echo date('M d', strtotime($activity['activity_date'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Properties -->
            <div class="row">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-building me-2"></i>Recent Properties
                                </h5>
                                <a href="search.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <?php if (empty($recent_properties)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No properties available</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($recent_properties as $property): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="rental-card">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                    <p class="text-muted small mb-2">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($property['location_name']); ?>
                                                    </p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold text-primary">ETB <?php echo number_format($property['monthly_rent'], 0); ?></span>
                                                        <div class="d-flex gap-1">
                                                            <a href="search.php?details=<?php echo $property['property_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                                                <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" 
                                                                   target="_blank" class="btn btn-sm btn-outline-info" title="Show on Map">
                                                                    <i class="fas fa-map-marked-alt"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" 
                                                                   target="_blank" class="btn btn-sm btn-outline-info" title="Search on Map">
                                                                    <i class="fas fa-search-location"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest News & Announcements -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-newspaper me-2"></i>Latest News & Announcements
                                </h5>
                                <a href="../public/news.php" class="btn btn-sm btn-outline-primary">View All News</a>
                            </div>
                            <?php if (empty($latest_news)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No news articles available</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($latest_news as $news): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-0 bg-light">
                                                <div class="card-body">
                                                    <?php if ($news['category_name']): ?>
                                                        <span class="badge mb-2" style="background-color: <?php echo $news['category_color']; ?>; font-size: 0.7rem;">
                                                            <?php echo htmlspecialchars($news['category_name']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($news['featured']): ?>
                                                        <span class="badge bg-warning mb-2">
                                                            <i class="fas fa-star me-1"></i>Featured
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <h6 class="card-title mb-2">
                                                        <a href="../public/news_details.php?id=<?php echo $news['news_id']; ?>" 
                                                           class="text-decoration-none text-dark">
                                                            <?php echo htmlspecialchars($news['title']); ?>
                                                        </a>
                                                    </h6>
                                                    
                                                    <div class="text-muted small mb-2">
                                                        <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($news['author_name']); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($news['publication_date'])); ?>
                                                    </div>
                                                    
                                                    <?php if ($news['excerpt']): ?>
                                                        <p class="text-muted small mb-2">
                                                            <?php echo htmlspecialchars(substr($news['excerpt'], 0, 80)); ?>...
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="text-muted small mb-2">
                                                            <?php echo htmlspecialchars(substr($news['content'], 0, 80)); ?>...
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <?php
                                                        $priority_colors = [
                                                            'low' => 'secondary',
                                                            'medium' => 'primary',
                                                            'high' => 'warning',
                                                            'urgent' => 'danger'
                                                        ];
                                                        $color = $priority_colors[$news['priority']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $color; ?> me-1" style="font-size: 0.7rem;">
                                                            <?php echo ucfirst($news['priority']); ?>
                                                        </span>
                                                        
                                                        <a href="../public/news_details.php?id=<?php echo $news['news_id']; ?>" 
                                                           class="btn btn-outline-primary btn-sm">
                                                            Read More
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard payment page loaded');
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
    // Pay button click handlers for both quick actions and active rentals
    const payButtons = document.querySelectorAll('.pay-btn, .quick-pay-btn');
    payButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const agreementId = this.dataset.agreementId || this.getAttribute('data-agreement-id');
            const propertyTitle = this.dataset.propertyTitle || this.getAttribute('data-property-title');
            const amount = this.dataset.amount || this.getAttribute('data-amount');
            const dueDate = this.dataset.dueDate || this.getAttribute('data-due-date');
            console.log('Pay button clicked for agreement:', agreementId);
            document.getElementById('agreement_id').value = agreementId;
            document.getElementById('property_id').value = this.dataset.propertyId || this.getAttribute('data-property-id');
            document.getElementById('payment_property_title').textContent = propertyTitle;
            document.getElementById('payment_due_date').textContent = new Date(dueDate).toLocaleDateString();
            // Calculate payment amounts
            const monthlyRent = Number(amount);
            const fullAmount = monthlyRent * 6;
            const minimumAmount = Math.max(monthlyRent * 0.2, monthlyRent);
            // Update payment type cards
            document.getElementById('monthlyAmount').textContent = 'ETB ' + monthlyRent.toLocaleString();
            document.getElementById('minimumAmount').textContent = 'ETB ' + minimumAmount.toLocaleString();
            document.getElementById('fullAmount').textContent = 'ETB ' + fullAmount.toLocaleString();
            // Set default to monthly payment
            selectPaymentType('MONTHLY', monthlyRent, fullAmount, 0);
            showModal();
        });
    });

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
                    balance = fullAmount - amount;
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

            // Validate payment type selection
            if (!paymentType) {
                alert('Please select a payment type');
                return;
            }

            // Check wallet balance
            if (amount > walletBalance) {
                alert('Insufficient wallet balance. Please add money to your wallet first.');
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
                alert('An error occurred while submitting payment. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }

    // Close modal functionality (btn-close and Cancel buttons)
    document.addEventListener('click', function(event) {
        if (
            event.target.classList.contains('btn-close') ||
            (event.target.matches('#paymentModal [data-bs-dismiss=\"modal\"]')) ||
            (event.target.classList.contains('modal') && event.target.classList.contains('fade'))
        ) {
            let modal = event.target;
            if (!modal.classList.contains('modal')) {
                modal = event.target.closest('.modal');
            }
            hideModal();
        }
    });
});
</script>
