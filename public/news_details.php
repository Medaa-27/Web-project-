<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Initialize session
$session = new SessionManager($db);

// Allow both logged-in and public access to view news
$title = "News Details";

$news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($news_id <= 0) {
    header('Location: news.php?error=invalid_id');
    exit;
}

// Get news article details - Fetch only the selected post with author info
$sql = "SELECT sn.*, u.full_name as author_name, nc.category_name, nc.color as category_color
       FROM system_news sn
       LEFT JOIN users u ON sn.created_by = u.user_id
       LEFT JOIN news_categories nc ON sn.category_id = nc.category_id
       WHERE sn.news_id = ? AND sn.status = 'published'
       LIMIT 1";
$stmt = $db->prepare($sql);
if (!$stmt) {
    header('Location: news.php?error=query_failed');
    exit;
}
$news = $db->getSingle($stmt, [$news_id]);

// If news not found, redirect with error
if (!$news) {
    header('Location: news.php?error=not_found');
    exit;
}

// Check if news has expired
if ($news['expiry_date'] && strtotime($news['expiry_date']) < time()) {
    header('Location: news.php?error=expired');
    exit;
}

// Check if user is in target audience (normalize values and support synonyms)
$can_view = false;
$aud = strtolower(trim($news['target_audience'] ?? 'all'));
$aud = str_replace(' ', '_', $aud);
if (in_array($aud, ['owner', 'property_owner', 'property_owners'])) $aud = 'owners';
if (in_array($aud, ['tenant'])) $aud = 'tenants';

if ($aud === 'all') {
    $can_view = true;
} elseif ($session->isLoggedIn()) {
    $user_role = strtolower($session->getUserRole() ?? '');
    // Admins can see everything
    if ($user_role === 'admin') {
        $can_view = true;
    } elseif (($aud === 'tenants' && $user_role === 'tenant') ||
              ($aud === 'owners' && $user_role === 'owner') ||
              ($aud === 'employees' && $user_role === 'employee')) {
        $can_view = true;
    }
} else {
    // Non-logged-in users can only view 'all' audience news
    $can_view = ($aud === 'all');
}

if (!$can_view) {
    if ($session->isLoggedIn()) {
        // User is logged in but doesn't have permission - show error page
        header('Location: ../tenant/dashboard.php?error=news_access_denied');
    } else {
        // User not logged in - redirect to login
        header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    exit;
}

// Record view if user is logged in
if ($session->isLoggedIn()) {
    $user_id = $session->getUserId();
    $sql = "INSERT IGNORE INTO news_views (news_id, user_id) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $db->execute($stmt, [$news_id, $user_id]);
    }
    
    // Update view count
    $sql = "UPDATE system_news SET view_count = view_count + 1 WHERE news_id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $db->execute($stmt, [$news_id]);
    }
}

// Get related news (same category or same audience) - simplified query
$sql = "SELECT sn.news_id, sn.title, sn.excerpt, sn.created_at
       FROM system_news sn
       WHERE sn.news_id != ? AND sn.status = 'published'
       ORDER BY sn.created_at DESC
       LIMIT 5";
$stmt = $db->prepare($sql);
if ($stmt) {
    $related_news_raw = $db->getMultiple($stmt, [$news_id]);
    // Ensure all related news items have proper defaults
    $related_news = [];
    foreach ($related_news_raw as $item) {
        $related_news[] = [
            'news_id' => $item['news_id'] ?? 0,
            'title' => $item['title'] ?? 'Untitled',
            'excerpt' => $item['excerpt'] ?? '',
            'created_at' => $item['created_at'] ?? date('Y-m-d H:i:s'),
            'category_name' => null,
            'category_color' => '#6c757d'
        ];
    }
} else {
    $related_news = [];
}

// Get comments if comments are allowed - simplified query
$comments = [];
if ($news['allow_comments']) {
    try {
        $sql = "SELECT comment_id, comment, user_id, created_at, status
               FROM news_comments
               WHERE news_id = ? AND status = 'approved'
               ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $comments_raw = $db->getMultiple($stmt, [$news_id]);
            // Ensure all comment items have proper defaults
            $comments = [];
            foreach ($comments_raw as $item) {
                $comments[] = [
                    'comment_id' => $item['comment_id'] ?? 0,
                    'comment' => $item['comment'] ?? '',
                    'full_name' => 'User ' . ($item['user_id'] ?? 'Unknown'),
                    'profile_image' => null,
                    'created_at' => $item['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
        }
    } catch (Exception $e) {
        // Comments table doesn't exist or query failed
        $comments = [];
    }
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
        <!-- News Article -->
        <article class="card dashboard-card mb-4">
            <div class="card-body p-4">
                <!-- Article Header -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <?php if (!empty($news['category_name'])): ?>
                                <span class="badge mb-2" style="background-color: <?php echo $news['category_color']; ?>;">
                                    <?php echo htmlspecialchars($news['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($news['featured'])): ?>
                                <span class="badge bg-warning mb-2">
                                    <i class="fas fa-star me-1"></i>Featured
                                </span>
                            <?php endif; ?>
                            
                            <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($news['title'] ?? __('untitled')); ?></h1>
                            
                            <div class="d-flex align-items-center text-muted mb-3">
                                <div class="me-4">
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($news['author_name'] ?? __('system_administrator')); ?>
                                </div>
                                <div class="me-4">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('F d, Y', strtotime($news['publication_date'] ?? ($news['created_at'] ?? date('Y-m-d H:i:s')))); ?>
                                </div>
                                <div class="me-4">
                                    <i class="fas fa-eye me-1"></i>
                                    <?php echo number_format(($news['view_count'] ?? 0) + 1); ?> <?php echo __('views'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <?php
                            $priority_colors = [
                                'low' => 'secondary',
                                'medium' => 'primary',
                                'high' => 'warning',
                                'urgent' => 'danger'
                            ];
                            $color = $priority_colors[$news['priority']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?> fs-6">
                                <?php echo ucfirst($news['priority']); ?> <?php echo __('priority'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php 
                    $excerpt = trim((string)($news['excerpt'] ?? ''));
                    $content = trim((string)($news['content'] ?? ''));
                    
                    // Logic to avoid displaying the same text twice
                    $show_excerpt_as_lead = !empty($excerpt) && ($excerpt !== $content);
                    ?>
                    
                    <?php if ($show_excerpt_as_lead): ?>
                        <div class="lead text-muted mb-4">
                            <?php echo htmlspecialchars($excerpt); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Article Image -->
                <div class="mb-4">
                    <img src="../assets/images/ax.jpg" alt="News Image" class="img-fluid rounded shadow-sm w-100" style="max-height: 400px; object-fit: cover;">
                </div>
                
                <!-- Article Content -->
                <div class="article-content">
                    <?php 
                    // If content is empty or same as excerpt, use content (which might be the same as excerpt)
                    // but we only showed the lead if it was DIFFERENT.
                    $toRender = !empty($content) ? $content : $excerpt;
                    
                    if (empty(trim((string)$toRender))) {
                        echo '<div class="alert alert-info">' . __('no_additional_details') . '</div>';
                    } else {
                        // If it doesn't look like HTML, use nl2br
                        $hasHtml = (bool)preg_match('/<\s*\w+/i', (string)$toRender);
                        $formatted = $hasHtml ? $toRender : nl2br(htmlspecialchars((string)$toRender));
                        echo '<div class="content-full">' . $formatted . '</div>';
                    }
                    ?>
                </div>
                
                <!-- Article Footer -->
                <div class="mt-4 pt-4 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <?php 
                            // Get like count
                            $sql = "SELECT COUNT(*) as count FROM news_likes WHERE news_id = ?";
                            $stmt = $db->prepare($sql);
                            $like_res = $db->getSingle($stmt, [$news_id]);
                            $like_count = $like_res['count'] ?? 0;
                            
                            $is_liked = false;
                            if ($session->isLoggedIn()) {
                                $sql = "SELECT like_id FROM news_likes WHERE news_id = ? AND user_id = ?";
                                $stmt = $db->prepare($sql);
                                $is_liked = (bool)$db->getSingle($stmt, [$news_id, $session->getUserId()]);
                            }
                            ?>
                            <button class="btn <?php echo $is_liked ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm me-3" id="likeBtn" onclick="toggleLike(<?php echo $news_id; ?>)">
                                <i class="fas fa-thumbs-up me-1"></i>
                                <span id="likeText"><?php echo $is_liked ? 'Liked' : 'Like'; ?></span>
                                (<span id="likeCount"><?php echo $like_count; ?></span>)
                            </button>
                            
                            <span class="text-muted small">
                                <i class="fas fa-comment me-1"></i>
                                <span id="totalCommentCount"><?php echo count($comments); ?></span> Comments
                            </span>
                        </div>
                        
                        <?php if ($session->isLoggedIn()): ?>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary btn-sm" onclick="shareNews()">
                                    <i class="fas fa-share-alt me-1"></i><?php echo __('share_article'); ?>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="printArticle()">
                                    <i class="fas fa-print me-1"></i><?php echo __('print_article'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-muted">
                        <small>
                            <?php echo __('published_on'); ?> <?php echo date('F d, Y \a\t g:i A', strtotime($news['publication_date'] ?? ($news['created_at'] ?? date('Y-m-d H:i:s')))); ?>
                            <?php if (!empty($news['expiry_date'])): ?>
                                • <?php echo __('expires_on'); ?> <?php echo date('F d, Y', strtotime($news['expiry_date'] ?? '')); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </article>

        <!-- Comments Section -->
        <?php if ($news['allow_comments']): ?>
        <section class="card dashboard-card mb-4" id="commentsSection">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0"><i class="fas fa-comments me-2 text-primary"></i>Comments</h5>
            </div>
            <div class="card-body">
                <?php if ($session->isLoggedIn()): ?>
                    <!-- Add Comment Form -->
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <img src="<?php echo $_SESSION['profile_image'] ?? '../assets/images/default-avatar.svg'; ?>" class="rounded-circle" width="40" height="40" alt="Avatar">
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <form id="commentForm">
                                <input type="hidden" name="news_id" value="<?php echo $news_id; ?>">
                                <input type="hidden" name="parent_id" id="parent_id" value="">
                                <div class="input-group">
                                    <textarea name="comment" class="form-control" rows="1" placeholder="Write a comment..." required style="resize: none;"></textarea>
                                    <button type="submit" class="btn btn-primary">Post</button>
                                </div>
                                <div id="replyingTo" class="small text-muted mt-1 d-none">
                                    Replying to <span id="replyAuthor"></span> 
                                    <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="cancelReply()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        Please <a href="../login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="fw-bold">Login</a> to join the conversation.
                    </div>
                <?php endif; ?>

                <!-- Comments List -->
                <div id="commentsList">
                    <!-- Comments will be loaded dynamically or rendered here -->
                    <div class="text-center py-3" id="commentsLoading">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Loading comments...</span>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Back to News -->
            <div class="text-center mb-4">
                <a href="news.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i><?php echo __('back_to_news'); ?>
                </a>
            </div>
        </div>

    </div>
</div>

<script>
function shareNews() {
    if (navigator.share) {
        navigator.share({
            title: <?php echo json_encode($news['title'] ?? "News"); ?>,
            text: <?php echo json_encode($news['excerpt'] ?? ($news['title'] ?? "")); ?>,
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        alert('News link copied to clipboard!');
    }
}

function printArticle() {
    window.print();
}

// Social Features
const newsId = <?php echo $news_id; ?>;
const isLoggedIn = <?php echo $session->isLoggedIn() ? 'true' : 'false'; ?>;

function toggleLike(id) {
    if (!isLoggedIn) {
        window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    }

    const btn = document.getElementById('likeBtn');
    const text = document.getElementById('likeText');
    const count = document.getElementById('likeCount');
    
    btn.disabled = true;

    fetch('../api/like-news.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'news_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'liked') {
                btn.classList.replace('btn-outline-primary', 'btn-primary');
                text.textContent = 'Liked';
            } else {
                btn.classList.replace('btn-primary', 'btn-outline-primary');
                text.textContent = 'Like';
            }
            count.textContent = data.like_count;
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error))
    .finally(() => btn.disabled = false);
}

function loadComments() {
    const commentsList = document.getElementById('commentsList');
    if (!commentsList) return;

    fetch('../api/get-comments.php?news_id=' + newsId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('totalCommentCount').textContent = data.comment_count;
            document.getElementById('likeCount').textContent = data.like_count;
            
            if (data.is_liked) {
                document.getElementById('likeBtn').classList.replace('btn-outline-primary', 'btn-primary');
                document.getElementById('likeText').textContent = 'Liked';
            }

            if (data.comments.length === 0) {
                commentsList.innerHTML = '<p class="text-center text-muted my-4">No comments yet. Be the first to share your thoughts!</p>';
                return;
            }

            commentsList.innerHTML = renderComments(data.comments);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        commentsList.innerHTML = '<div class="alert alert-danger">Error loading comments.</div>';
    });
}

function renderComments(comments, isReply = false) {
    let html = '';
    comments.forEach(comment => {
        const avatar = comment.profile_image ? '../' + comment.profile_image : '../assets/images/default-avatar.svg';
        const date = new Date(comment.created_at).toLocaleString();
        
        html += `
            <div class="d-flex ${isReply ? 'mt-3' : 'mb-4'}" id="comment-${comment.comment_id}">
                <div class="flex-shrink-0">
                    <img src="${avatar}" class="rounded-circle" width="${isReply ? '30' : '40'}" height="${isReply ? '30' : '40'}" alt="Avatar">
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="bg-light p-3 rounded">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 fw-bold">${comment.full_name}</h6>
                            <small class="text-muted">${date}</small>
                        </div>
                        <p class="mb-0">${comment.comment}</p>
                    </div>
                    <div class="mt-1 small">
                        ${isLoggedIn ? `<button class="btn btn-link btn-sm p-0 text-decoration-none" onclick="prepareReply(${comment.comment_id}, '${comment.full_name}')">Reply</button>` : ''}
                    </div>
                    ${comment.replies && comment.replies.length > 0 ? `
                        <div class="ms-4 border-start ps-3 mt-3">
                            ${renderComments(comment.replies, true)}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    return html;
}

function prepareReply(id, author) {
    document.getElementById('parent_id').value = id;
    document.getElementById('replyAuthor').textContent = author;
    document.getElementById('replyingTo').classList.remove('d-none');
    document.querySelector('#commentForm textarea').focus();
    
    // Scroll to form
    document.getElementById('commentForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function cancelReply() {
    document.getElementById('parent_id').value = '';
    document.getElementById('replyingTo').classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', () => {
    loadComments();

    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Posting...';

            fetch('../api/add-comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    cancelReply();
                    loadComments();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post';
            });
        });
    }
});
</script>

<style>
.article-content {
    line-height: 1.6;
    font-size: 1.1rem;
    color: #333;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.content-full {
    margin-bottom: 1rem;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.content-text, .content-html {
    margin-bottom: 1rem;
}

.content-html p {
    margin-bottom: 1rem;
}

.content-html ul, .content-html ol {
    margin-bottom: 1rem;
    padding-left: 2rem;
}

.content-html li {
    margin-bottom: 0.5rem;
}

.content-html blockquote {
    border-left: 4px solid #007bff;
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    color: #666;
}

.content-html table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.content-html th, .content-html td {
    border: 1px solid #ddd;
    padding: 0.5rem;
    text-align: left;
}

.content-html th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.content-html pre {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    overflow-x: auto;
    margin-bottom: 1rem;
}

.content-html code {
    background-color: #e9ecef;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
}

@media print {
    .btn, .card-header, .comment-item, .col-lg-3 {
        display: none !important;
    }
    .article-content {
        font-size: 12pt;
        line-height: 1.4;
    }
    .col-lg-9 {
        width: 100% !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
