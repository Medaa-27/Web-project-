<?php
// Employee Sidebar Navigation

// Get employee info if not already loaded
if (!isset($employee)) {
    require_once '../../includes/config.php';
    require_once '../../includes/db_connect.php';
    
    if (isset($_SESSION['user_id'])) {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $employee = $db->getSingle($stmt, [$_SESSION['user_id']]);
    }
}

// Get stats if not loaded
if (!isset($stats)) {
    // Default stats
    $stats = [
        'properties_to_verify' => 0,
        'pending_requests' => 0,
        'active_tenants' => 0,
        'pending_feedback' => 0
    ];
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="text-center mb-4">
        <div class="avatar-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
            <i class="fas fa-user-tie fa-2x"></i>
        </div>
        <?php if (isset($employee)): ?>
            <h6 class="mb-1"><?php echo htmlspecialchars($employee['full_name']); ?></h6>
            <p class="small text-muted"><?php echo t('role_label'); ?> <?php echo t('employee'); ?></p>
        <?php else: ?>
            <h6 class="mb-1"><?php echo t('employee'); ?></h6>
        <?php endif; ?>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'properties.php' ? 'active' : ''; ?>" href="properties.php">
                    <i class="fas fa-home me-2"></i> <?php echo t('properties'); ?>
                    <?php if (isset($stats['properties_to_verify']) && $stats['properties_to_verify'] > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $stats['properties_to_verify']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>" href="feedback.php">
                    <i class="fas fa-comments me-2"></i> <?php echo t('feedback'); ?>
                    <?php if (isset($stats['pending_feedback']) && $stats['pending_feedback'] > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $stats['pending_feedback']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'support.php' ? 'active' : ''; ?>" href="support.php">
                    <i class="fas fa-headset me-2"></i> <?php echo t('support_tickets'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i> <?php echo t('reports'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'operational-reports.php' ? 'active' : ''; ?>" href="operational-reports.php">
                    <i class="fas fa-chart-line me-2"></i> <?php echo t('operational_reports'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'news.php' ? 'active' : ''; ?>" href="news.php">
                    <i class="fas fa-bullhorn me-2"></i> <?php echo t('system_news'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user-cog me-2"></i> <?php echo t('profile'); ?>
                </a>
            </li>
        </ul>
        
        <hr class="my-3">
        
        <div class="d-grid">
            <a href="../logout.php" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-2"></i> <?php echo t('logout'); ?>
            </a>
        </div>
    </nav>
</div>