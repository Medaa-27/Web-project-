<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('tenant');

$title = "News & Announcements";

// Get filter parameters
$filter_category = $_GET['category'] ?? 'all';
$filter_audience = $_GET['audience'] ?? 'tenants';

// Build where conditions
$where_conditions = ["sn.status = 'published'"];
$params = [];

// Show news relevant to tenants
if ($filter_audience !== 'all') {
    if ($filter_audience === 'tenants' || $filter_audience === 'all') {
        $where_conditions[] = "sn.target_audience IN (?, 'all')";
        $params[] = $filter_audience;
    }
} else {
    // Show news relevant to tenants by default
    $where_conditions[] = "sn.target_audience IN ('tenants', 'all')";
}

if ($filter_category !== 'all') {
    $where_conditions[] = "sn.category_id = ?";
    $params[] = $filter_category;
}

// Don't show expired news
$where_conditions[] = "(sn.expiry_date IS NULL OR sn.expiry_date > NOW())";

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get news articles
$sql = "SELECT sn.*, nc.category_name, nc.color as category_color, u.full_name as author_name,
       COALESCE((SELECT COUNT(*) FROM news_views nv WHERE nv.news_id = sn.news_id), 0) as view_count
       FROM system_news sn
       LEFT JOIN news_categories nc ON sn.category_id = nc.category_id
       LEFT JOIN users u ON sn.created_by = u.user_id
       $where_clause
       ORDER BY COALESCE(sn.featured, 0) DESC, 
                CASE sn.priority 
                    WHEN 'urgent' THEN 4 
                    WHEN 'high' THEN 3 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 1 
                    ELSE 0 
                END DESC, 
                sn.publication_date DESC";
$stmt = $db->prepare($sql);
$news_articles = $db->getMultiple($stmt, $params);

// Ensure all articles have required keys with default values
foreach ($news_articles as &$article) {
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
unset($article);

// Get categories for filter
$sql = "SELECT nc.*, COUNT(sn.news_id) as news_count
       FROM news_categories nc
       LEFT JOIN system_news sn ON nc.category_id = sn.category_id AND sn.status = 'published'
       GROUP BY nc.category_id
       ORDER BY nc.category_name";
$stmt = $db->prepare($sql);
$categories = $db->getMultiple($stmt);

// Get featured news
$featured_news = array_filter($news_articles, function($article) {
    return $article['featured'];
});

// Get recent news (non-featured)
$recent_news = array_filter($news_articles, function($article) {
    return !$article['featured'];
});

// Get tenant statistics for dashboard
$user_id = $session->getUserId();
$stats = [];

// Active agreements
$sql = "SELECT COUNT(*) as count FROM rental_agreements WHERE tenant_id = ? AND status = 'active'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt, [$user_id]);
$stats['active_agreements'] = $result ? $result['count'] : 0;

// Pending requests
$sql = "SELECT COUNT(*) as count FROM rental_requests WHERE tenant_id = ? AND status = 'pending'";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt, [$user_id]);
$stats['pending_requests'] = $result ? $result['count'] : 0;

// Unread notifications
$sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt, [$user_id]);
$stats['unread_notifications'] = $result ? $result['count'] : 0;

// Overdue payments
$sql = "SELECT COUNT(*) as count FROM rental_agreements ra 
        LEFT JOIN payments p ON ra.agreement_id = p.agreement_id AND p.status = 'completed'
        WHERE ra.tenant_id = ? AND ra.status = 'active' 
        AND (p.payment_id IS NULL OR p.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt, [$user_id]);
$stats['overdue_payments'] = $result ? $result['count'] : 0;

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="dashboard-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-newspaper me-2"></i>News & Announcements
                    </h1>
                    <p class="text-muted mb-0">Stay updated with the latest news and important announcements for tenants</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="refreshNews()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
    
    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['active_agreements']; ?></h4>
                            <small>Active Rentals</small>
                        </div>
                        <i class="fas fa-home fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['pending_requests']; ?></h4>
                            <small>Pending Requests</small>
                        </div>
                        <i class="fas fa-paper-plane fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['overdue_payments']; ?></h4>
                            <small>Overdue Payments</small>
                        </div>
                        <i class="fas fa-credit-card fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['unread_notifications']; ?></h4>
                            <small>Unread Notifications</small>
                        </div>
                        <i class="fas fa-bell fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card dashboard-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filter News
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" 
                                    <?php echo $filter_category == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                                <?php if ($category['news_count'] > 0): ?>
                                    (<?php echo $category['news_count']; ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Audience</label>
                    <select name="audience" class="form-select">
                        <option value="all" <?php echo $filter_audience === 'all' ? 'selected' : ''; ?>>All Relevant</option>
                        <option value="tenants" <?php echo $filter_audience === 'tenants' ? 'selected' : ''; ?>>Tenants</option>
                        <option value="owners" <?php echo $filter_audience === 'owners' ? 'selected' : ''; ?>>Property Owners</option>
                        <option value="employees" <?php echo $filter_audience === 'employees' ? 'selected' : ''; ?>>Employees</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-filter me-1"></i>Apply Filters
                        </button>
                        <a href="news.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (empty($news_articles)): ?>
        <div class="text-center py-5">
            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
            <h3 class="text-muted">No news articles found</h3>
            <p class="text-muted">Check back later for new announcements relevant to tenants.</p>
        </div>
    <?php else: ?>
        <!-- Featured News -->
        <?php if (!empty($featured_news)): ?>
            <section class="mb-5">
                <h2 class="h4 mb-4">
                    <i class="fas fa-star text-warning me-2"></i>Featured News
                </h2>
                <div class="row">
                    <?php foreach ($featured_news as $news): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card dashboard-card h-100 featured-news-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <?php if ($news['category_name']): ?>
                                                <span class="badge mb-2" style="background-color: <?php echo $news['category_color']; ?>;">
                                                    <?php echo htmlspecialchars($news['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <h3 class="h5">
                                                <a href="<?php echo SITE_URL; ?>public/news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo htmlspecialchars($news['title']); ?>
                                                </a>
                                            </h3>
                                            
                                            <div class="text-muted small mb-2">
                                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($news['author_name']); ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($news['publication_date'])); ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-eye me-1"></i> <?php echo number_format($news['view_count']); ?> views
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <?php
                                            $priority_colors = [
                                                'low' => 'secondary',
                                                'medium' => 'primary',
                                                'high' => 'warning',
                                                'urgent' => 'danger'
                                            ];
                                            $color = $priority_colors[$news['priority']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>">
                                                <?php echo ucfirst($news['priority']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($news['excerpt']): ?>
                                        <p class="text-muted mb-3">
                                            <?php echo htmlspecialchars($news['excerpt']); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted mb-3">
                                            <?php echo htmlspecialchars(substr($news['content'], 0, 150)); ?>...
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-info">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo ucfirst($news['target_audience']); ?>
                                        </span>
                                        
                                        <a href="<?php echo SITE_URL; ?>public/news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
                                           class="btn btn-primary btn-sm">
                                            Read More <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Recent News -->
        <?php if (!empty($recent_news)): ?>
            <section>
                <h2 class="h4 mb-4">
                    <i class="fas fa-newspaper me-2"></i>Recent News
                </h2>
                <div class="row">
                    <?php foreach ($recent_news as $news): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card dashboard-card h-100">
                                <div class="card-body">
                                    <?php if ($news['category_name']): ?>
                                        <span class="badge mb-2" style="background-color: <?php echo $news['category_color']; ?>;">
                                            <?php echo htmlspecialchars($news['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <h5 class="mb-2">
                                        <a href="<?php echo SITE_URL; ?>public/news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($news['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="text-muted small mb-3">
                                        <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($news['author_name']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($news['publication_date'])); ?>
                                    </div>
                                    
                                    <?php if ($news['excerpt']): ?>
                                        <p class="text-muted small mb-3">
                                            <?php echo htmlspecialchars(substr($news['excerpt'], 0, 100)); ?>...
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted small mb-3">
                                            <?php echo htmlspecialchars(substr($news['content'], 0, 100)); ?>...
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php
                                            $priority_colors = [
                                                'low' => 'secondary',
                                                'medium' => 'primary',
                                                'high' => 'warning',
                                                'urgent' => 'danger'
                                            ];
                                            $color = $priority_colors[$news['priority']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?> me-1">
                                                <?php echo ucfirst($news['priority']); ?>
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-eye me-1"></i><?php echo $news['view_count']; ?>
                                            </span>
                                        </div>
                                        
                                        <a href="<?php echo SITE_URL; ?>public/news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            Read More
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function refreshNews() {
    location.reload();
}
</script>

<style>
.featured-news-card {
    border-left: 4px solid #ffc107;
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
}

.featured-news-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.card.dashboard-card {
    transition: all 0.3s ease;
}

.card.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.badge {
    font-size: 0.75rem;
}
</style>

<?php include '../includes/footer.php'; ?>
