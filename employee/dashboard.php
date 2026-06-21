<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Employee Dashboard";

$employee_id = $session->getUserId();

// Get employee info
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$employee = $db->getSingle($stmt, [$employee_id]);
if (!$employee) {
    $employee = ['full_name' => 'Employee'];
}

// Dashboard statistics for employee
$stats = [];

// Pending rental requests
$sql = "SELECT COUNT(*) as count FROM rental_requests WHERE status = 'pending'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$stats['pending_requests'] = $result['count'] ?? 0;

// Active tenants
$sql = "SELECT COUNT(*) as count FROM rental_agreements WHERE status = 'active'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$stats['active_tenants'] = $result['count'] ?? 0;

// Pending feedback
$sql = "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$stats['pending_feedback'] = $result['count'] ?? 0;

// Support tickets
$sql = "SELECT COUNT(*) as count FROM support_tickets WHERE status = 'open'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$stats['open_tickets'] = $result['count'] ?? 0;

// Property review statistics
$sql = "SELECT COUNT(*) as count FROM properties WHERE review_status = 'pending'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$stats['pending_properties'] = $result['count'] ?? 0;

$sql = "SELECT COUNT(*) as count FROM properties WHERE review_status = 'approved'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt);
$stats['approved_properties'] = $result['count'] ?? 0;

// Recent rental requests
$sql = "SELECT r.*, p.title, p.address, u.full_name as tenant_name 
        FROM rental_requests r 
        JOIN properties p ON r.property_id = p.property_id 
        JOIN users u ON r.tenant_id = u.user_id 
        WHERE r.status = 'pending' 
        ORDER BY r.created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$recent_requests = $db->getMultiple($stmt);

// Recent feedback
$sql = "SELECT f.*, p.title as property_title, u.full_name as user_name
        FROM feedback f
        JOIN properties p ON f.property_id = p.property_id
        LEFT JOIN users u ON f.user_id = u.user_id
        WHERE f.status = 'pending'
        ORDER BY f.created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$recent_feedback = $db->getMultiple($stmt);

// Recent support tickets
$sql = "SELECT st.*, u.full_name as user_name
        FROM support_tickets st
        LEFT JOIN users u ON st.tenant_id = u.user_id
        WHERE st.status = 'open'
        ORDER BY st.created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$recent_tickets = $db->getMultiple($stmt);

// Pending property reviews
$sql = "SELECT p.*, u.full_name as owner_name, l.location_name,
               (SELECT COUNT(*) FROM property_images pi WHERE pi.property_id = p.property_id) as image_count
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        WHERE p.review_status = 'pending'
        ORDER BY p.created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$pending_properties = $db->getMultiple($stmt);

// Get latest news and announcements
$sql = "SELECT sn.*, nc.category_name, nc.color as category_color, u.full_name as author_name,
               (SELECT COUNT(*) FROM news_views nv WHERE nv.news_id = sn.news_id) as view_count
        FROM system_news sn
        LEFT JOIN news_categories nc ON sn.category_id = nc.category_id
        LEFT JOIN users u ON sn.created_by = u.user_id
        WHERE sn.status = 'published' 
        AND (sn.target_audience = 'all' OR sn.target_audience = 'employees')
        ORDER BY sn.published_at DESC, sn.created_at DESC 
        LIMIT 5";
$stmt = $db->prepare($sql);
$latest_news = $db->getMultiple($stmt);

include '../includes/header.php';
?>

<div class="container-fluid py-4 main-content">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Welcome Banner -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">Employee Dashboard</h1>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($employee['full_name']); ?>!</p>
                            <p class="text-muted mb-0">You are logged in as: <span class="badge bg-info">Employee</span></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="reports.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-chart-bar me-2"></i>Generate Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-primary bg-opacity-10 text-primary mx-auto">
                                <i class="fas fa-inbox fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['pending_requests']; ?></h3>
                            <p class="text-muted mb-0">Pending Requests</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['active_tenants']; ?></h3>
                            <p class="text-muted mb-0">Active Tenants</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['pending_feedback']; ?></h3>
                            <p class="text-muted mb-0">Pending Feedback</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">
                                <i class="fas fa-home fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['pending_properties']; ?></h3>
                            <p class="text-muted mb-0">Pending Properties</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Main Content Area -->
                <div class="col-lg-8">
                    <!-- Pending Property Reviews -->
                    <div class="card dashboard-card h-100 mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pending Property Reviews</h5>
                            <a href="property-review.php" class="btn btn-sm btn-outline-primary">Review All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($pending_properties)): ?>
                                    <div class="list-group-item text-center py-4 text-muted">
                                        No pending property reviews
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($pending_properties as $property): ?>
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                    <p class="small text-muted mb-1">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($property['location_name'] ?? 'Unknown Location'); ?>
                                                    </p>
                                                    <p class="small text-muted mb-1">
                                                        <i class="fas fa-user me-1"></i>
                                                        Owner: <?php echo htmlspecialchars($property['owner_name']); ?>
                                                    </p>
                                                    <p class="small text-muted mb-1">
                                                        <i class="fas fa-images me-1"></i>
                                                        <?php echo $property['image_count']; ?> images | 
                                                        <i class="fas fa-bed me-1"></i><?php echo $property['bedrooms']; ?> beds | 
                                                        <i class="fas fa-bath me-1"></i><?php echo $property['bathrooms']; ?> baths
                                                    </p>
                                                    <small class="text-muted">
                                                        Submitted: <?php echo date('M d, Y', strtotime($property['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group">
                                                    <a href="property-review.php" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-eye"></i> Review
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Rental Requests -->
                    <div class="card dashboard-card h-100 mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pending Rental Requests</h5>
                            <a href="requests.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($recent_requests)): ?>
                                    <div class="list-group-item text-center py-4 text-muted">
                                        No pending requests
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_requests as $request): ?>
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['title'] ?? 'Property'); ?></h6>
                                                    <p class="small text-muted mb-1">
                                                        Address: <?php echo htmlspecialchars($request['address'] ?? 'N/A'); ?>
                                                    </p>
                                                    <p class="small text-muted mb-1">
                                                        Tenant: <?php echo htmlspecialchars($request['tenant_name']); ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        Requested: <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group">
                                                    <a href="view-request.php?id=<?php echo $request['request_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Sidebar - News & Announcements -->
                <div class="col-lg-4">
                    <!-- News & Announcements -->
                    <div class="card dashboard-card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-newspaper me-1"></i>News & Announcements
                            </h6>
                            <a href="manage-news.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($latest_news)): ?>
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-newspaper fa-lg mb-1"></i>
                                    <p class="small mb-0">No news announcements</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($latest_news as $news): ?>
                                        <div class="list-group-item list-group-item-action px-2 py-2">
                                            <h6 class="mb-1 fw-bold small">
                                                <?php if ($news['featured']): ?>
                                                    <i class="fas fa-star text-warning me-1" title="Featured"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars(substr($news['title'], 0, 40)); ?>...
                                            </h6>
                                            <?php if ($news['category_name']): ?>
                                                <span class="badge mb-1" style="background-color: <?php echo $news['category_color']; ?>; font-size: 0.6rem;">
                                                    <?php echo htmlspecialchars($news['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <p class="small text-muted mb-2">
                                                <?php echo htmlspecialchars(substr($news['excerpt'] ?? strip_tags($news['content']), 0, 80)); ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($news['author_name']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-eye me-1"></i><?php echo $news['view_count']; ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('M d, Y', strtotime($news['published_at'] ?? $news['created_at'])); ?>
                                            </small>
                                            <div class="mt-2">
                                                <a href="../public/news_details.php?id=<?php echo $news['news_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-external-link-alt me-1"></i>Read More
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-center p-2">
                            <a href="manage-news.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-cog me-1"></i>Manage News
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="card dashboard-card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line me-1"></i>Quick Stats
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="border-end">
                                        <h4 class="text-primary mb-1"><?php echo $stats['pending_requests']; ?></h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <h4 class="text-success mb-1"><?php echo $stats['active_tenants']; ?></h4>
                                    <small class="text-muted">Active</small>
                                </div>
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-warning mb-1"><?php echo $stats['pending_feedback']; ?></h4>
                                        <small class="text-muted">Feedback</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-info mb-1"><?php echo $stats['pending_properties']; ?></h4>
                                    <small class="text-muted">Properties</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-server me-1"></i>System Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small">Database</span>
                                <span class="badge bg-success">Online</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small">File Upload</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small">Email Service</span>
                                <span class="badge bg-warning">Check</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">Last Backup</span>
                                <span class="small text-muted">Today</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Pending Feedback -->
                <div class="col-lg-6 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pending Feedback/Complaints</h5>
                            <a href="feedback.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($recent_feedback)): ?>
                                    <div class="list-group-item text-center py-4 text-muted">
                                        No pending feedback
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_feedback as $feedback): ?>
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($feedback['user_name']); ?></h6>
                                                    <p class="small text-muted mb-1">
                                                        <?php echo substr(htmlspecialchars($feedback['comment']), 0, 50); ?>...
                                                    </p>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group">
                                                    <a href="view-feedback.php?id=<?php echo $feedback['feedback_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Support Tickets -->
                <div class="col-12 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Open Support Tickets</h5>
                            <a href="support.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>From</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_tickets)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    No open support tickets
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_tickets as $ticket): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo str_pad($ticket['ticket_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo ucfirst($ticket['category']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $priority_colors = [
                                                            'low' => 'secondary',
                                                            'normal' => 'primary', 
                                                            'high' => 'warning',
                                                            'urgent' => 'danger'
                                                        ];
                                                        $color = $priority_colors[$ticket['priority']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($ticket['priority']); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($ticket['user_name'] ?? 'Unknown'); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                                    <td>
                                                        <a href="support-ticket.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
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
            
            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="manage-news.php" class="btn btn-outline-primary w-100 py-3">
                                        <i class="fas fa-newspaper fa-2x mb-2"></i><br>
                                        Manage News
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="property-review.php" class="btn btn-outline-warning w-100 py-3">
                                        <i class="fas fa-home fa-2x mb-2"></i><br>
                                        Review Properties
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="feedback.php" class="btn btn-outline-warning w-100 py-3">
                                        <i class="fas fa-comments fa-2x mb-2"></i><br>
                                        View Feedback
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="support.php" class="btn btn-outline-info w-100 py-3">
                                        <i class="fas fa-headset fa-2x mb-2"></i><br>
                                        Support Tickets
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- News & Announcements -->
            <div class="card dashboard-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-newspaper me-1"></i>News
                    </h6>
                    <a href="manage-news.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($latest_news)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-newspaper fa-lg mb-1"></i>
                            <p class="small mb-0">No news</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($latest_news as $news): ?>
                                <div class="list-group-item list-group-item-action px-2 py-2">
                                    <h6 class="mb-1 fw-bold small">
                                        <?php if ($news['featured']): ?>
                                            <i class="fas fa-star text-warning me-1" title="Featured"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars(substr($news['title'], 0, 30)); ?>...
                                    </h6>
                                    <?php if ($news['category_name']): ?>
                                        <span class="badge mb-1" style="background-color: <?php echo $news['category_color']; ?>; font-size: 0.6rem;">
                                            <?php echo htmlspecialchars($news['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?php echo date('M d', strtotime($news['published_at'] ?? $news['created_at'])); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-eye me-1"></i><?php echo $news['view_count']; ?>
                                        </small>
                                    </div>
                                    <div class="mt-1">
                                        <a href="../public/news_details.php?id=<?php echo $news['news_id']; ?>" 
                                           class="btn btn-xs btn-outline-primary" target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i>Read
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>