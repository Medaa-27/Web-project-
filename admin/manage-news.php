<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('admin');
$title = "Manage System News";

$admin_id = $session->getUserId();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit_news':
                $news_id = $_POST['news_id'] ?? 0;
                $title_text = $_POST['news_title'] ?? '';
                $content = $_POST['news_content'] ?? '';
                $excerpt = $_POST['news_excerpt'] ?? '';
                $target_audience = $_POST['target_audience'] ?? 'all';
                $priority = $_POST['priority'] ?? 'medium';
                $publication_date = $_POST['publication_date'] ?? date('Y-m-d H:i:s');
                $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $featured = isset($_POST['featured']) ? 1 : 0;
                $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
                $status = $_POST['status'] ?? 'draft';
                
                if ($news_id > 0 && !empty($title_text) && !empty($content)) {
                    $sql = "UPDATE system_news SET title = ?, content = ?, excerpt = ?, target_audience = ?, 
                           priority = ?, publication_date = ?, expiry_date = ?, category_id = ?, 
                           featured = ?, allow_comments = ?, status = ?, modified_by = ?
                           WHERE news_id = ?";
                    $stmt = $db->prepare($sql);
                    $result = $db->execute($stmt, [$title_text, $content, $excerpt, $target_audience, 
                                                   $priority, $publication_date, $expiry_date, $category_id, 
                                                   $featured, $allow_comments, $status, $admin_id, $news_id]);
                    
                    if ($result) {
                        $_SESSION['success'] = "News updated successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to update news: " . $db->getLastError();
                    }
                }
                header("Location: manage-news.php");
                exit;
                
            case 'create_news':
                $title_text = $_POST['news_title'] ?? '';
                $content = $_POST['news_content'] ?? '';
                $excerpt = $_POST['news_excerpt'] ?? '';
                $target_audience = $_POST['target_audience'] ?? 'all';
                $priority = $_POST['priority'] ?? 'medium';
                $publication_date = $_POST['publication_date'] ?? date('Y-m-d H:i:s');
                $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $featured = isset($_POST['featured']) ? 1 : 0;
                $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
                $status = $_POST['status'] ?? 'draft';
                
                if (!empty($title_text) && !empty($content)) {
                    $sql = "INSERT INTO system_news (title, content, excerpt, target_audience, priority, 
                           publication_date, expiry_date, created_by, category_id, featured, allow_comments, 
                           status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $result = $db->execute($stmt, [$title_text, $content, $excerpt, $target_audience, 
                                                   $priority, $publication_date, $expiry_date, $admin_id, 
                                                   $category_id, $featured, $allow_comments, $status]);
                    
                    if ($result) {
                        $_SESSION['success'] = "News created successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to create news: " . $db->getLastError();
                    }
                }
                header("Location: manage-news.php");
                exit;
                
            case 'delete_news':
                $news_id = $_POST['news_id'] ?? 0;
                if ($news_id > 0) {
                    $sql = "DELETE FROM system_news WHERE news_id = ?";
                    $stmt = $db->prepare($sql);
                    $result = $db->execute($stmt, [$news_id]);
                    
                    if ($result) {
                        $_SESSION['success'] = "News deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete news: " . $db->getLastError();
                    }
                }
                header("Location: manage-news.php");
                exit;
        }
    }
}

// Get news categories
$sql = "SELECT * FROM news_categories ORDER BY category_name";
$stmt = $db->prepare($sql);
$categories = $db->getMultiple($stmt);

// Get all news
$sql = "SELECT sn.*, nc.category_name, nc.color as category_color, u.full_name as author_name
       FROM system_news sn
       LEFT JOIN news_categories nc ON sn.category_id = nc.category_id
       LEFT JOIN users u ON sn.created_by = u.user_id
       ORDER BY sn.created_at DESC";
$stmt = $db->prepare($sql);
$all_news = $db->getMultiple($stmt);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Manage System News</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNewsModal">
                    <i class="fas fa-plus me-2"></i>Create News
                </button>
            </div>

            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Author</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_news)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No news articles found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_news as $news): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($news['title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($news['excerpt'], 0, 50)); ?>...</small>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $news['category_color'] ?? '#6c757d'; ?>;">
                                                    <?php echo htmlspecialchars($news['category_name'] ?? 'General'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($news['author_name'] ?? 'System'); ?></td>
                                            <td><span class="badge bg-info"><?php echo ucfirst($news['target_audience']); ?></span></td>
                                            <td>
                                                <span class="badge bg-<?php echo $news['status'] === 'published' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($news['status']); ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo date('M d, Y', strtotime($news['publication_date'])); ?></small></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary edit-news-btn" 
                                                            data-news='<?php echo json_encode($news); ?>'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this post?');">
                                                        <input type="hidden" name="action" value="delete_news">
                                                        <input type="hidden" name="news_id" value="<?php echo $news['news_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
                <input type="hidden" name="action" value="create_news">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="news_title" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Target Audience</label>
                            <select name="target_audience" class="form-select">
                                <option value="all">All Users</option>
                                <option value="tenants">Tenants Only</option>
                                <option value="owners">Owners Only</option>
                                <option value="employees">Employees Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Excerpt *</label>
                        <textarea name="news_excerpt" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Content *</label>
                        <textarea name="news_content" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Publication Date</label>
                            <input type="datetime-local" name="publication_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="featured" class="form-check-input" id="feat_check">
                                <label class="form-check-label" for="feat_check">Featured Article</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Post</button>
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
                <input type="hidden" name="action" value="edit_news">
                <input type="hidden" name="news_id" id="edit_news_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="news_title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="edit_category" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Target Audience</label>
                            <select name="target_audience" id="edit_audience" class="form-select">
                                <option value="all">All Users</option>
                                <option value="tenants">Tenants Only</option>
                                <option value="owners">Owners Only</option>
                                <option value="employees">Employees Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" id="edit_priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Excerpt *</label>
                        <textarea name="news_excerpt" id="edit_excerpt" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Content *</label>
                        <textarea name="news_content" id="edit_content" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Publication Date</label>
                            <input type="datetime-local" name="publication_date" id="edit_pub_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="featured" class="form-check-input" id="edit_feat">
                                <label class="form-check-label" for="edit_feat">Featured Article</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-news-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editNewsModal'));
    
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.news);
            document.getElementById('edit_news_id').value = data.news_id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_category').value = data.category_id || '';
            document.getElementById('edit_audience').value = data.target_audience;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_priority').value = data.priority;
            document.getElementById('edit_excerpt').value = data.excerpt;
            document.getElementById('edit_content').value = data.content;
            
            if (data.publication_date) {
                document.getElementById('edit_pub_date').value = data.publication_date.replace(' ', 'T').substring(0, 16);
            }
            
            document.getElementById('edit_feat').checked = data.featured == 1;
            
            editModal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
