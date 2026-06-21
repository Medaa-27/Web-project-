<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Initialize session
$session = new SessionManager($db);

$title = "System News & Announcements";

// Get filter parameters
$filter_category = $_GET['category'] ?? 'all';
$filter_audience = $_GET['audience'] ?? 'all';

// Build where conditions
$where_conditions = ["sn.status = 'published'"];
$params = [];

// Check user role for audience filtering
if ($session->isLoggedIn()) {
    $user_role = $session->getUserRole();
    
    if ($filter_audience !== 'all') {
        if ($filter_audience === $user_role || $filter_audience === 'all') {
            $where_conditions[] = "sn.target_audience IN (?, 'all')";
            $params[] = $filter_audience;
        }
    } else {
        // Show news relevant to user's role
        if ($user_role === 'tenant') {
            $where_conditions[] = "sn.target_audience IN ('tenants', 'all')";
        } elseif ($user_role === 'owner') {
            $where_conditions[] = "sn.target_audience IN ('owners', 'all')";
        } elseif ($user_role === 'employee') {
            $where_conditions[] = "sn.target_audience IN ('employees', 'all')";
        }
    }
} else {
    // Non-logged-in users can only see 'all' audience news
    $where_conditions[] = "sn.target_audience = 'all'";
}

if ($filter_category !== 'all') {
    $where_conditions[] = "sn.category_id = ?";
    $params[] = $filter_category;
}

// Don't show expired news
$where_conditions[] = "(sn.expiry_date IS NULL OR sn.expiry_date > NOW())";

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get news articles - simplified query to avoid JOIN errors
$sql = "SELECT sn.* FROM system_news sn $where_clause
       ORDER BY sn.featured DESC, sn.priority DESC, sn.publication_date DESC";
$stmt = $db->prepare($sql);
$news_articles = $db->getMultiple($stmt, $params);

// Add default values and get additional info separately
foreach ($news_articles as &$article) {
    // Set default values
    $article = array_merge([
        'news_id' => 0,
        'title' => 'Untitled',
        'content' => '',
        'excerpt' => '',
        'target_audience' => 'all',
        'priority' => 'medium',
        'publication_date' => date('Y-m-d H:i:s'),
        'expiry_date' => null,
        'category_id' => null,
        'featured' => 0,
        'allow_comments' => 0,
        'meta_title' => '',
        'meta_description' => '',
        'publish_date' => date('Y-m-d'),
        'status' => 'draft',
        'created_by' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'notification_sent' => 0,
        'archived_at' => null,
        'view_count' => 0,
        'category_name' => null,
        'category_color' => '#6c757d',
        'author_name' => 'System Administrator'
    ], $article);
    
    // Try to get view count
    try {
        $sql = "SELECT COUNT(*) as count FROM news_views WHERE news_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $result = $db->getSingle($stmt, [$article['news_id']]);
            $article['view_count'] = $result['count'] ?? 0;
        }
    } catch (Exception $e) {
        $article['view_count'] = 0;
    }
    
    // Try to get category info
    try {
        if ($article['category_id']) {
            $sql = "SELECT category_name, color FROM news_categories WHERE category_id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $category = $db->getSingle($stmt, [$article['category_id']]);
                if ($category) {
                    $article['category_name'] = $category['category_name'];
                    $article['category_color'] = $category['color'];
                }
            }
        }
    } catch (Exception $e) {
        // Use defaults
    }
    
    // Try to get author info
    try {
        if ($article['created_by']) {
            $sql = "SELECT full_name FROM users WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $author = $db->getSingle($stmt, [$article['created_by']]);
                if ($author) {
                    $article['author_name'] = $author['full_name'];
                }
            }
        }
    } catch (Exception $e) {
        // Use defaults
    }
}
unset($article);

// Get categories for filter - simplified query
try {
    $sql = "SELECT * FROM news_categories ORDER BY category_name";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $categories = $db->getMultiple($stmt);
    } else {
        $categories = [];
    }
} catch (Exception $e) {
    $categories = [];
}

// Apply defaults to all news articles (already done in loop above)
// $news_articles = array_map('ensureNewsDefaults', $news_articles);

// Get featured news
$featured_news = array_filter($news_articles, function($article) {
    return !empty($article['featured']);
});

// Get recent news (non-featured)
$recent_news = array_filter($news_articles, function($article) {
    return empty($article['featured']);
});

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold mb-3">System News & Announcements</h1>
                <p class="lead text-muted">Stay updated with the latest news and important announcements</p>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-warning mt-3">
                        <?php
                        switch ($_GET['error']) {
                            case 'not_found':
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>The requested news article was not found.';
                                break;
                            case 'expired':
                                echo '<i class="fas fa-clock me-2"></i>The requested news article has expired.';
                                break;
                            case 'query_failed':
                                echo '<i class="fas fa-database me-2"></i>A database error occurred. Please try again later.';
                                break;
                            case 'news_access_denied':
                                echo '<i class="fas fa-lock me-2"></i>You do not have permission to view that news article.';
                                break;
                            default:
                                echo '<i class="fas fa-exclamation-circle me-2"></i>An error occurred while loading the news article.';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
    
    <!-- Filters -->
    <div class="card dashboard-card mb-4">
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
                
                <?php if ($session->isLoggedIn()): ?>
                    <div class="col-md-4">
                        <label class="form-label">Audience</label>
                        <select name="audience" class="form-select">
                            <option value="all" <?php echo $filter_audience === 'all' ? 'selected' : ''; ?>>All Relevant</option>
                            <option value="tenants" <?php echo $filter_audience === 'tenants' ? 'selected' : ''; ?>>Tenants</option>
                            <option value="owners" <?php echo $filter_audience === 'owners' ? 'selected' : ''; ?>>Property Owners</option>
                            <option value="employees" <?php echo $filter_audience === 'employees' ? 'selected' : ''; ?>>Employees</option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-<?php echo $session->isLoggedIn() ? '4' : '8'; ?>">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-filter me-1"></i>Filter
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
            <p class="text-muted">Check back later for new announcements.</p>
        </div>
    <?php else: ?>
        <!-- Featured News -->
        <?php if (!empty($featured_news)): ?>
            <section class="mb-5">
                <h2 class="h3 mb-4">
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
                                                <a href="news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
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
                                        
                                        <a href="news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
                                           class="btn btn-outline-primary btn-sm">
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
                <h2 class="h3 mb-4">
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
                                        <a href="news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
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
                                        
                                        <a href="news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
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
