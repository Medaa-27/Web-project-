<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Manage System News";

$employee_id = $session->getUserId();

// Get employee info
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
if ($stmt === false) {
    die("Database error: " . $db->getLastError());
}
$employee = $db->getSingle($stmt, [$employee_id]);
if (!$employee) {
    $employee = ['full_name' => 'Employee'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit_news':
                $news_id = $_POST['news_id'] ?? 0;
                $title = $_POST['news_title'] ?? '';
                $content = $_POST['news_content'] ?? '';
                $excerpt = $_POST['news_excerpt'] ?? '';
                $target_audience = $_POST['target_audience'] ?? 'all';
                $priority = $_POST['priority'] ?? 'medium';
                $publication_date = $_POST['publication_date'] ?? date('Y-m-d H:i:s');
                $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $featured = isset($_POST['featured']) ? 1 : 0;
                $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
                $meta_title = $_POST['meta_title'] ?? '';
                $meta_description = $_POST['meta_description'] ?? '';
                
                if ($news_id > 0 && !empty($title) && !empty($content)) {
                    // Try to update with modified_by first, fallback without it
                    $sql_with_modified = "UPDATE system_news SET title = ?, content = ?, excerpt = ?, target_audience = ?, 
                           priority = ?, publication_date = ?, expiry_date = ?, category_id = ?, 
                           featured = ?, allow_comments = ?, meta_title = ?, meta_description = ?,
                           modified_by = ?
                           WHERE news_id = ? AND created_by = ?";
                    $stmt = $db->prepare($sql_with_modified);
                    
                    if ($stmt === false) {
                        // Fallback: update without modified_by and updated_at
                        $sql = "UPDATE system_news SET title = ?, content = ?, excerpt = ?, target_audience = ?, 
                               priority = ?, publication_date = ?, expiry_date = ?, category_id = ?, 
                               featured = ?, allow_comments = ?, meta_title = ?, meta_description = ?
                               WHERE news_id = ? AND created_by = ?";
                        $stmt = $db->prepare($sql);
                        if ($stmt === false) {
                            $error_message = "Failed to prepare edit query: " . $db->getLastError();
                        } else {
                            $result = $db->execute($stmt, [$title, $content, $excerpt, $target_audience, 
                                                           $priority, $publication_date, $expiry_date, $category_id, 
                                                           $featured, $allow_comments, $meta_title, $meta_description,
                                                           $news_id, $employee_id]);
                            
                            if ($result) {
                                $success_message = "News updated successfully!";
                            } else {
                                $error_message = "Failed to update news: " . $db->getLastError();
                            }
                        }
                    } else {
                        // Use the query with modified_by
                        $result = $db->execute($stmt, [$title, $content, $excerpt, $target_audience, 
                                                       $priority, $publication_date, $expiry_date, $category_id, 
                                                       $featured, $allow_comments, $meta_title, $meta_description,
                                                       $employee_id, $news_id, $employee_id]);
                        
                        if ($result) {
                            $success_message = "News updated successfully!";
                        } else {
                            $error_message = "Failed to update news: " . $db->getLastError();
                        }
                    }
                } else {
                    $error_message = "News ID, title, and content are required fields.";
                }
                break;
                
            case 'create_news':
                $title = $_POST['news_title'] ?? '';
                $content = $_POST['news_content'] ?? '';
                $excerpt = $_POST['news_excerpt'] ?? '';
                $target_audience = $_POST['target_audience'] ?? 'all';
                $priority = $_POST['priority'] ?? 'medium';
                $publication_date = $_POST['publication_date'] ?? date('Y-m-d H:i:s');
                $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $featured = isset($_POST['featured']) ? 1 : 0;
                $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
                $meta_title = $_POST['meta_title'] ?? '';
                $meta_description = $_POST['meta_description'] ?? '';
                
                if (!empty($title) && !empty($content)) {
                    // Debug: Show what's being saved
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                        echo "<div class='alert alert-info'>";
                        echo "<strong>Debug - Saving News:</strong><br>";
                        echo "Title: " . htmlspecialchars($title) . " (" . strlen($title) . " chars)<br>";
                        echo "Content: " . htmlspecialchars(substr($content, 0, 200)) . "... (" . strlen($content) . " chars)<br>";
                        echo "Excerpt: " . htmlspecialchars($excerpt) . " (" . strlen($excerpt) . " chars)<br>";
                        echo "</div>";
                    }
                    
                    $sql = "INSERT INTO system_news (title, content, excerpt, target_audience, priority, 
                           publication_date, expiry_date, created_by, category_id, featured, allow_comments, 
                           meta_title, meta_description, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
                    
                    // Debug: Check if table exists first
                    $check_sql = "SHOW TABLES LIKE 'system_news'";
                    $check_stmt = $db->prepare($check_sql);
                    if ($check_stmt === false) {
                        $error_message = "Database prepare error: " . $db->getLastError();
                    } else {
                        $table_exists = $db->getSingle($check_stmt);
                        if (!$table_exists) {
                            $error_message = "system_news table does not exist. Please run the database setup script.";
                        } else {
                            $stmt = $db->prepare($sql);
                            if ($stmt === false) {
                                $error_message = "SQL prepare error: " . $db->getLastError() . " SQL: " . $sql;
                            } else {
                                $result = $db->execute($stmt, [$title, $content, $excerpt, $target_audience, 
                                                               $priority, $publication_date, $expiry_date, $employee_id, 
                                                               $category_id, $featured, $allow_comments, $meta_title, $meta_description]);
                                
                                if ($result) {
                                    $success_message = "News created successfully! You can now publish it.";
                                } else {
                                    $error_message = "Failed to create news: " . $db->getLastError();
                                }
                            }
                        }
                    }
                } else {
                    $error_message = "Title and content are required fields.";
                }
                break;
                
            case 'publish_news':
                $news_id = $_POST['news_id'] ?? 0;
                if ($news_id > 0) {
                    // Try to update with published_at first, fallback without it
                    $sql_with_timestamp = "UPDATE system_news SET status = 'published', published_at = NOW() 
                                          WHERE news_id = ? AND created_by = ?";
                    $stmt = $db->prepare($sql_with_timestamp);
                    
                    if ($stmt === false) {
                        // Fallback: update without published_at column
                        $sql = "UPDATE system_news SET status = 'published' 
                               WHERE news_id = ? AND created_by = ?";
                        $stmt = $db->prepare($sql);
                        if ($stmt === false) {
                            $error_message = "Failed to prepare publish query: " . $db->getLastError();
                        } else {
                            $result = $db->execute($stmt, [$news_id, $employee_id]);
                            
                            if ($result) {
                                // Send notifications to target audience
                                $notification_result = sendNewsNotifications($news_id, $db);
                                if ($notification_result) {
                                    $success_message = "News published successfully and notifications sent to users!";
                                } else {
                                    $success_message = "News published successfully, but there may have been issues sending notifications. Check error logs for details.";
                                }
                            } else {
                                $error_message = "Failed to publish news: " . $db->getLastError();
                            }
                        }
                    } else {
                        // Use the query with timestamp
                        $result = $db->execute($stmt, [$news_id, $employee_id]);
                        
                        if ($result) {
                            // Send notifications to target audience
                            $notification_result = sendNewsNotifications($news_id, $db);
                            if ($notification_result) {
                                $success_message = "News published successfully and notifications sent to users!";
                            } else {
                                $success_message = "News published successfully, but there may have been issues sending notifications. Check error logs for details.";
                            }
                        } else {
                            $error_message = "Failed to publish news: " . $db->getLastError();
                        }
                    }
                }
                break;
                
            case 'archive_news':
                $news_id = $_POST['news_id'] ?? 0;
                if ($news_id > 0) {
                    // Try to update with archived_at first, fallback without it
                    $sql_with_timestamp = "UPDATE system_news SET status = 'archived', archived_at = NOW() 
                                          WHERE news_id = ? AND created_by = ?";
                    $stmt = $db->prepare($sql_with_timestamp);
                    
                    if ($stmt === false) {
                        // Fallback: update without archived_at column
                        $sql = "UPDATE system_news SET status = 'archived' 
                               WHERE news_id = ? AND created_by = ?";
                        $stmt = $db->prepare($sql);
                        if ($stmt === false) {
                            $error_message = "Failed to prepare archive query: " . $db->getLastError();
                        } else {
                            $result = $db->execute($stmt, [$news_id, $employee_id]);
                            
                            if ($result) {
                                $success_message = "News archived successfully!";
                            } else {
                                $error_message = "Failed to archive news: " . $db->getLastError();
                            }
                        }
                    } else {
                        // Use the query with timestamp
                        $result = $db->execute($stmt, [$news_id, $employee_id]);
                        
                        if ($result) {
                            $success_message = "News archived successfully!";
                        } else {
                            $error_message = "Failed to archive news: " . $db->getLastError();
                        }
                    }
                }
                break;
                
            case 'delete_news':
                $news_id = $_POST['news_id'] ?? 0;
                if ($news_id > 0) {
                    $sql = "DELETE FROM system_news WHERE news_id = ? AND created_by = ?";
                    $stmt = $db->prepare($sql);
                    if ($stmt === false) {
                        $error_message = "Failed to prepare delete query: " . $db->getLastError();
                    } else {
                        $result = $db->execute($stmt, [$news_id, $employee_id]);
                        
                        if ($result) {
                            $success_message = "News deleted successfully!";
                        } else {
                            $error_message = "Failed to delete news: " . $db->getLastError();
                        }
                    }
                }
                break;
        }
    }
}

// Get news categories
$sql = "SELECT * FROM news_categories ORDER BY category_name";
$stmt = $db->prepare($sql);
if ($stmt === false) {
    die("Database error preparing categories query: " . $db->getLastError());
}
$categories = $db->getMultiple($stmt);

// Get all news with filtering
$filter_status = $_GET['filter_status'] ?? 'all';
$filter_audience = $_GET['filter_audience'] ?? 'all';
$filter_category = $_GET['filter_category'] ?? 'all';

$where_conditions = ["sn.created_by = ?"];
$params = [$employee_id];

if ($filter_status !== 'all') {
    $where_conditions[] = "sn.status = ?";
    $params[] = $filter_status;
}

if ($filter_audience !== 'all') {
    $where_conditions[] = "sn.target_audience = ?";
    $params[] = $filter_audience;
}

if ($filter_category !== 'all') {
    $where_conditions[] = "sn.category_id = ?";
    $params[] = $filter_category;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$sql = "SELECT sn.*, nc.category_name, nc.color as category_color, u.full_name as author_name,
       (SELECT COUNT(*) FROM news_views nv WHERE nv.news_id = sn.news_id) as view_count
       FROM system_news sn
       LEFT JOIN news_categories nc ON sn.category_id = nc.category_id
       LEFT JOIN users u ON sn.created_by = u.user_id
       $where_clause
       ORDER BY sn.created_at DESC";
$stmt = $db->prepare($sql);
if ($stmt === false) {
    die("Database error preparing news query: " . $db->getLastError());
}
$all_news = $db->getMultiple($stmt, $params);

// Get statistics
$stats = [];
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
    0 as total_views
    FROM system_news WHERE created_by = ?";
$stmt = $db->prepare($sql);
if ($stmt === false) {
    die("Database error preparing stats query: " . $db->getLastError());
}
$stats_result = $db->getSingle($stmt, [$employee_id]);

// Calculate total views separately to avoid column dependency
$view_sql = "SELECT COUNT(*) as total_views FROM news_views nv 
             WHERE nv.news_id IN (SELECT news_id FROM system_news WHERE created_by = ?)";
$view_stmt = $db->prepare($view_sql);
if ($view_stmt !== false) {
    $view_result = $db->getSingle($view_stmt, [$employee_id]);
    $total_views = $view_result['total_views'] ?? 0;
} else {
    $total_views = 0;
}

$stats = $stats_result ?? ['total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0];
$stats['total_views'] = $total_views;

function sendNewsNotifications($news_id, $db) {
    try {
        // Get news details
        $sql = "SELECT * FROM system_news WHERE news_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            error_log("News notification failed: Could not prepare news query - " . $db->getLastError());
            return false;
        }
        $news = $db->getSingle($stmt, [$news_id]);
        
        if (!$news) {
            error_log("News notification failed: News with ID $news_id not found");
            return false;
        }
        
        // Determine target users based on audience
        $where_condition = "";
        switch ($news['target_audience']) {
            case 'tenants':
                $where_condition = "WHERE role = 'tenant'";
                break;
            case 'owners':
                $where_condition = "WHERE role = 'owner'";
                break;
            case 'employees':
                $where_condition = "WHERE role = 'employee'";
                break;
            case 'all':
            default:
                $where_condition = "WHERE role IN ('tenant', 'owner', 'employee')";
                break;
        }
        
        // Get target users
        $sql = "SELECT user_id FROM users $where_condition AND is_active = 1";
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            error_log("News notification failed: Could not prepare users query - " . $db->getLastError());
            return false;
        }
        $users = $db->getMultiple($stmt);
        
        if (!$users || empty($users)) {
            error_log("News notification failed: No active users found for audience '{$news['target_audience']}'");
            return false;
        }
        
        $notifications_created = 0;
        $errors = [];
        
        // Create notifications for each user
        foreach ($users as $user) {
            try {
                $notification_sql = "INSERT INTO notifications (user_id, type, title, message, link, is_read) 
                                   VALUES (?, 'info', ?, ?, ?, 0)";
                $notification_stmt = $db->prepare($notification_sql);
                
                if ($notification_stmt === false) {
                    $errors[] = "Failed to prepare notification statement for user {$user['user_id']}: " . $db->getLastError();
                    continue;
                }
                
                $title = "New System Announcement";
                $message = substr($news['title'], 0, 100) . "...";
                $link = "public/news_details.php?id=" . $news_id;
                
                $result = $db->execute($notification_stmt, [$user['user_id'], $title, $message, $link]);
                
                if ($result) {
                    $notifications_created++;
                } else {
                    $errors[] = "Failed to create notification for user {$user['user_id']}: " . $db->getLastError();
                }
            } catch (Exception $e) {
                $errors[] = "Exception for user {$user['user_id']}: " . $e->getMessage();
            }
        }
        
        // Mark notification as sent if at least one notification was created
        if ($notifications_created > 0) {
            $update_sql = "UPDATE system_news SET notification_sent = TRUE WHERE news_id = ?";
            $update_stmt = $db->prepare($update_sql);
            if ($update_stmt !== false) {
                $db->execute($update_stmt, [$news_id]);
            }
            
            error_log("News notifications sent: $notifications_created notifications created for news ID $news_id");
            return true;
        } else {
            error_log("News notification failed: No notifications created. Errors: " . implode('; ', $errors));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("News notification exception: " . $e->getMessage());
        return false;
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">Manage System News</h1>
                            <p class="text-muted mb-0">Create and manage official system announcements</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createNewsModal">
                                <i class="fas fa-plus me-2"></i>Create News
                            </button>
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
                                <i class="fas fa-newspaper fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['total']; ?></h3>
                            <p class="text-muted mb-0">Total News</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">
                                <i class="fas fa-eye fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['published']; ?></h3>
                            <p class="text-muted mb-0">Published</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">
                                <i class="fas fa-edit fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['draft']; ?></h3>
                            <p class="text-muted mb-0">Drafts</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">
                                <i class="fas fa-chart-bar fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo number_format($stats['total_views']); ?></h3>
                            <p class="text-muted mb-0">Total Views</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="filter_status" class="form-select">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Target Audience</label>
                            <select name="filter_audience" class="form-select">
                                <option value="all" <?php echo $filter_audience === 'all' ? 'selected' : ''; ?>>All Users</option>
                                <option value="tenants" <?php echo $filter_audience === 'tenants' ? 'selected' : ''; ?>>Tenants</option>
                                <option value="owners" <?php echo $filter_audience === 'owners' ? 'selected' : ''; ?>>Property Owners</option>
                                <option value="employees" <?php echo $filter_audience === 'employees' ? 'selected' : ''; ?>>Employees</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="filter_category" class="form-select">
                                <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo $filter_category == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary flex-fill">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <a href="manage-news.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- News List -->
            <div class="card dashboard-card">
                <div class="card-header">
                    <h5 class="mb-0">News Articles</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($all_news)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No news articles found</h5>
                            <p class="text-muted">Create your first news article to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Audience</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Views</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_news as $news): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($news['featured']): ?>
                                                        <i class="fas fa-star text-warning me-2" title="Featured"></i>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($news['title']); ?></strong>
                                                        <?php if (!empty($news['excerpt'])): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($news['excerpt'], 0, 80)); ?>...</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($news['category_name']): ?>
                                                    <span class="badge" style="background-color: <?php echo $news['category_color']; ?>;">
                                                        <?php echo htmlspecialchars($news['category_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($news['target_audience']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'draft' => 'secondary',
                                                    'published' => 'success',
                                                    'archived' => 'warning'
                                                ];
                                                $color = $status_colors[$news['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo ucfirst($news['status']); ?>
                                                </span>
                                            </td>
                                            <td>
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
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-eye me-1"></i><?php echo $news['view_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('M d, Y', strtotime($news['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editNews(<?php echo $news['news_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($news['status'] === 'draft'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="publish_news">
                                                            <input type="hidden" name="news_id" value="<?php echo $news['news_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success" 
                                                                    onclick="return confirm('Publish this news article?')">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        </form>
                                                    <?php elseif ($news['status'] === 'published'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="archive_news">
                                                            <input type="hidden" name="news_id" value="<?php echo $news['news_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-warning" 
                                                                    onclick="return confirm('Archive this news article?')">
                                                                <i class="fas fa-archive"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_news">
                                                        <input type="hidden" name="news_id" value="<?php echo $news['news_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Delete this news article? This cannot be undone.')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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

<!-- Create News Modal -->
<div class="modal fade" id="createNewsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create News Article</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_news">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="news_title" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <select name="target_audience" class="form-select">
                                    <option value="all" selected>All Users</option>
                                    <option value="tenants">Tenants Only</option>
                                    <option value="owners">Property Owners Only</option>
                                    <option value="employees">Employees Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="news_excerpt" class="form-control" rows="2" 
                                  placeholder="Brief summary of the news article"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Content *</label>
                        <textarea name="news_content" class="form-control" rows="12" required 
                                  placeholder="Enter the complete news content here. This will be displayed in full when users click 'Read More'."></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Enter the full news content. All text will be displayed when users view the article.
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Publication Date</label>
                                <input type="datetime-local" name="publication_date" class="form-control" 
                                       value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expiry Date (Optional)</label>
                                <input type="datetime-local" name="expiry_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" 
                                       placeholder="SEO title (optional)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <input type="text" name="meta_description" class="form-control" 
                                       placeholder="SEO description (optional)">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="featured" class="form-check-input" id="featured">
                                <label class="form-check-label" for="featured">
                                    Featured Article
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="allow_comments" class="form-check-input" id="allow_comments" checked>
                                <label class="form-check-label" for="allow_comments">
                                    Allow Comments
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create News</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit News Modal -->
<div class="modal fade" id="editNewsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit News Article</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_news">
                    <input type="hidden" name="news_id" id="edit_news_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="news_title" id="edit_news_title" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" id="edit_priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <select name="target_audience" id="edit_target_audience" class="form-select">
                                    <option value="all">All Users</option>
                                    <option value="tenants">Tenants Only</option>
                                    <option value="owners">Property Owners Only</option>
                                    <option value="employees">Employees Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" id="edit_category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="news_excerpt" id="edit_news_excerpt" class="form-control" rows="2" 
                                  placeholder="Brief summary of the news article"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Content *</label>
                        <textarea name="news_content" id="edit_news_content" class="form-control" rows="12" required 
                                  placeholder="Enter the complete news content here. This will be displayed in full when users click 'Read More'."></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Enter the full news content. All text will be displayed when users view the article.
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Publication Date</label>
                                <input type="datetime-local" name="publication_date" id="edit_publication_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expiry Date (Optional)</label>
                                <input type="datetime-local" name="expiry_date" id="edit_expiry_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" id="edit_meta_title" class="form-control" 
                                       placeholder="SEO title (optional)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <input type="text" name="meta_description" id="edit_meta_description" class="form-control" 
                                       placeholder="SEO description (optional)">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="featured" id="edit_featured" class="form-check-input">
                                <label class="form-check-label" for="edit_featured">
                                    Featured Article
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="allow_comments" id="edit_allow_comments" class="form-check-input">
                                <label class="form-check-label" for="edit_allow_comments">
                                    Allow Comments
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update News</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// News form validation and enhancement
document.addEventListener('DOMContentLoaded', function() {
    const newsForm = document.querySelector('form[action="manage-news.php"]');
    const contentTextarea = document.querySelector('textarea[name="news_content"]');
    const titleInput = document.querySelector('input[name="news_title"]');
    
    if (newsForm && contentTextarea) {
        // Add character counter
        const counterDiv = document.createElement('div');
        counterDiv.className = 'form-text';
        counterDiv.innerHTML = '<i class="fas fa-keyboard me-1"></i>Character count: <span id="charCount">0</span>';
        contentTextarea.parentNode.appendChild(counterDiv);
        
        const charCountSpan = document.getElementById('charCount');
        
        // Update character count
        function updateCharCount() {
            const count = contentTextarea.value.length;
            charCountSpan.textContent = count;
            
            if (count < 50) {
                counterDiv.style.color = '#dc3545';
                charCountSpan.textContent = count + ' (Too short - minimum 50 characters)';
            } else if (count > 10000) {
                counterDiv.style.color = '#dc3545';
                charCountSpan.textContent = count + ' (Too long - maximum 10,000 characters)';
            } else {
                counterDiv.style.color = '#6c757d';
                charCountSpan.textContent = count;
            }
        }
        
        contentTextarea.addEventListener('input', updateCharCount);
        updateCharCount();
        
        // Form validation
        newsForm.addEventListener('submit', function(e) {
            const title = titleInput.value.trim();
            const content = contentTextarea.value.trim();
            
            if (title.length < 5) {
                e.preventDefault();
                alert('Title must be at least 5 characters long.');
                titleInput.focus();
                return;
            }
            
            if (content.length < 50) {
                e.preventDefault();
                alert('Content must be at least 50 characters long.');
                contentTextarea.focus();
                return;
            }
            
            if (content.length > 10000) {
                e.preventDefault();
                alert('Content must not exceed 10,000 characters.');
                contentTextarea.focus();
                return;
            }
            
            // Show loading state
            const submitBtn = newsForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
            }
        });
    }
});
</script>

<!-- Success/Error Messages -->
<?php if (isset($success_message)): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
        <div class="toast show" role="alert">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
        <div class="toast show" role="alert">
            <div class="toast-header bg-danger text-white">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function editNews(newsId) {
    // Fetch news data first
    fetch(`../api/get-news.php?id=${newsId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const news = data.data;
                
                // Populate form fields
                document.getElementById('edit_news_id').value = news.news_id;
                document.getElementById('edit_news_title').value = news.title;
                document.getElementById('edit_news_excerpt').value = news.excerpt || '';
                document.getElementById('edit_news_content').value = news.content;
                document.getElementById('edit_target_audience').value = news.target_audience;
                document.getElementById('edit_priority').value = news.priority;
                document.getElementById('edit_category_id').value = news.category_id || '';
                document.getElementById('edit_meta_title').value = news.meta_title || '';
                document.getElementById('edit_meta_description').value = news.meta_description || '';
                document.getElementById('edit_featured').checked = news.featured;
                document.getElementById('edit_allow_comments').checked = news.allow_comments;
                
                // Format dates for datetime-local input
                if (news.publication_date) {
                    const pubDate = new Date(news.publication_date);
                    document.getElementById('edit_publication_date').value = pubDate.toISOString().slice(0, 16);
                }
                
                if (news.expiry_date) {
                    const expDate = new Date(news.expiry_date);
                    document.getElementById('edit_expiry_date').value = expDate.toISOString().slice(0, 16);
                }
                
                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editNewsModal'));
                editModal.show();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching news:', error);
            alert('Failed to load news data. Please try again.');
        });
}

// Auto-hide success/error messages after 5 seconds
setTimeout(function() {
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        const bsToast = new bootstrap.Toast(toast);
        bsToast.hide();
    });
}, 5000);
</script>

<?php include '../includes/footer.php'; ?>
