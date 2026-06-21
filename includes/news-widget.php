<?php
// News Sidebar Widget Component
// Get latest news for sidebar display

// Build where conditions for relevant news
$where_conditions = ["sn.status = 'published'"];
$params = [];

// Show news relevant to owners
$where_conditions[] = "sn.target_audience IN ('owners', 'all')";

// Don't show expired news
$where_conditions[] = "(sn.expiry_date IS NULL OR sn.expiry_date > NOW())";

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get latest news articles (limit to 3 for sidebar)
$sql = "SELECT sn.news_id, sn.title, sn.excerpt, sn.publication_date, sn.priority,
       nc.category_name, nc.color as category_color, u.full_name as author_name
       FROM system_news sn
       LEFT JOIN news_categories nc ON sn.category_id = nc.category_id
       LEFT JOIN users u ON sn.created_by = u.user_id
       $where_clause
       ORDER BY sn.featured DESC, sn.priority DESC, sn.publication_date DESC
       LIMIT 3";
$stmt = $db->prepare($sql);
$news_articles = $db->getMultiple($stmt, $params);

// Only show widget if there are news articles
if (!empty($news_articles)):
?>

<!-- News & Announcements Widget -->
<div class="card dashboard-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-newspaper me-2"></i>News & Announcements
        </h6>
        <a href="news.php" class="btn btn-outline-primary btn-sm">
            View All <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <?php foreach ($news_articles as $index => $news): ?>
            <div class="news-item <?php echo $index > 0 ? 'border-top' : ''; ?> p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <?php if ($news['category_name']): ?>
                            <span class="badge mb-1" style="background-color: <?php echo $news['category_color']; ?>; font-size: 0.7rem;">
                                <?php echo htmlspecialchars($news['category_name']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <h6 class="mb-1">
                            <a href="<?php echo SITE_URL; ?>public/news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
                               class="text-decoration-none fw-bold" 
                               title="<?php echo htmlspecialchars($news['title']); ?>">
                                <?php echo htmlspecialchars(strlen($news['title']) > 50 ? substr($news['title'], 0, 50) . '...' : $news['title']); ?>
                            </a>
                        </h6>
                        
                        <div class="text-muted small">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($news['author_name']); ?>
                            <span class="mx-1">•</span>
                            <i class="fas fa-calendar me-1"></i><?php echo date('M d', strtotime($news['publication_date'])); ?>
                        </div>
                    </div>
                    
                    <div class="ms-2">
                        <?php
                        $priority_colors = [
                            'low' => 'secondary',
                            'medium' => 'primary',
                            'high' => 'warning',
                            'urgent' => 'danger'
                        ];
                        $color = $priority_colors[$news['priority']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?>" style="font-size: 0.65rem;">
                            <?php echo ucfirst($news['priority']); ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($news['excerpt']): ?>
                    <p class="text-muted small mb-0">
                        <?php echo htmlspecialchars(substr($news['excerpt'], 0, 80)); ?>...
                    </p>
                <?php endif; ?>
                <div class="mt-2">
                    <a href="<?php echo SITE_URL; ?>public/news_details.php?id=<?php echo urlencode($news['news_id']); ?>" 
                       class="btn btn-xs btn-outline-primary">
                        Read More
                    </a>
                </div>
            </div>
<?php endforeach; ?>
        
        <div class="text-center p-2 border-top">
            <a href="news.php" class="btn btn-primary btn-sm">
                <i class="fas fa-newspaper me-1"></i>View All News
            </a>
        </div>
    </div>
</div>

<style>
.news-item {
    transition: all 0.3s ease;
}

.news-item:hover {
    background-color: #f8f9fa;
}

.news-item h6 a {
    color: #495057;
    transition: color 0.3s ease;
}

.news-item h6 a:hover {
    color: #007bff;
}

.card.dashboard-card {
    transition: all 0.3s ease;
}

.card.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}
</style>

<?php endif; ?>
