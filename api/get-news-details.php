<?php
/**
 * AJAX API Endpoint for News Details
 * Returns news article details as JSON
 */
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/lang.php';

// Initialize session
$session = new SessionManager($db);

// Set JSON content type
header('Content-Type: application/json');

// Get news ID from query parameter
$news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($news_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => __('invalid_id'),
        'message' => 'Invalid news ID'
    ]);
    exit;
}

try {
    // Get news article details
    $sql = "SELECT sn.news_id, sn.title, sn.content, sn.excerpt, sn.target_audience, 
                   sn.priority, sn.category_id, sn.featured, sn.allow_comments, 
                   sn.publication_date, sn.expiry_date, sn.created_by, sn.status, 
                   sn.view_count
            FROM system_news sn
            WHERE sn.news_id = ? AND sn.status = 'published'";
    
    $stmt = $db->prepare($sql);
    $news_data = $db->getSingle($stmt, [$news_id]);
    
    if (!$news_data) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => __('article_not_found'),
            'message' => 'News article not found'
        ]);
        exit;
    }
    
    // Check if article has expired
    if ($news_data['expiry_date'] && strtotime($news_data['expiry_date']) < time()) {
        http_response_code(410);
        echo json_encode([
            'success' => false,
            'error' => __('article_expired'),
            'message' => 'This article has expired'
        ]);
        exit;
    }
    
    // Check target audience
    $can_view = false;
    $aud = strtolower(trim($news_data['target_audience'] ?? 'all'));
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
        $can_view = ($aud === 'all');
    }
    
    if (!$can_view) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => __('access_denied'),
            'message' => 'You do not have permission to view this article'
        ]);
        exit;
    }
    
    // Record view if logged in
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
    
    // Get category info
    $category_name = null;
    $category_color = '#6c757d';
    if ($news_data['category_id']) {
        $sql = "SELECT category_name, color FROM news_categories WHERE category_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $category = $db->getSingle($stmt, [$news_data['category_id']]);
            if ($category) {
                $category_name = $category['category_name'];
                $category_color = $category['color'];
            }
        }
    }
    
    // Get author info
    $author_name = 'System Administrator';
    if ($news_data['created_by']) {
        $sql = "SELECT full_name FROM users WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $author = $db->getSingle($stmt, [$news_data['created_by']]);
            if ($author) {
                $author_name = $author['full_name'];
            }
        }
    }
    
    // Get related news
    $related_news = [];
    $sql = "SELECT sn.news_id, sn.title, sn.excerpt, sn.created_at
            FROM system_news sn
            WHERE sn.news_id != ? AND sn.status = 'published'
            ORDER BY sn.created_at DESC
            LIMIT 5";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $related_news_raw = $db->getMultiple($stmt, [$news_id]);
        foreach ($related_news_raw as $item) {
            $related_news[] = [
                'news_id' => $item['news_id'],
                'title' => $item['title'],
                'excerpt' => $item['excerpt']
            ];
        }
    }
    
    // Get like info
    $sql = "SELECT COUNT(*) as count FROM news_likes WHERE news_id = ?";
    $stmt = $db->prepare($sql);
    $like_result = $db->getSingle($stmt, [$news_id]);
    $like_count = $like_result['count'] ?? 0;

    $is_liked = false;
    if ($session->isLoggedIn()) {
        $sql = "SELECT like_id FROM news_likes WHERE news_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $is_liked = (bool)$db->getSingle($stmt, [$news_id, $session->getUserId()]);
    }

    // Get comments and replies
    $comments = [];
    if ($news_data['allow_comments']) {
        try {
            $sql = "SELECT nc.*, u.full_name, u.profile_image 
                    FROM news_comments nc
                    LEFT JOIN users u ON nc.user_id = u.user_id
                    WHERE nc.news_id = ? AND nc.status = 'approved'
                    ORDER BY nc.created_at ASC";
            $stmt = $db->prepare($sql);
            $all_comments = $db->getMultiple($stmt, [$news_id]);

            // Group by parent_id for nesting
            $comments_by_parent = [];
            foreach ($all_comments as $c) {
                $parent_id = $c['parent_id'] ?: 0;
                if (!isset($comments_by_parent[$parent_id])) {
                    $comments_by_parent[$parent_id] = [];
                }
                $comments_by_parent[$parent_id][] = $c;
            }

            // Simple recursive function for API response
            if (!function_exists('nestComments')) {
                function nestComments($parent_id, $comments_by_parent) {
                    $tree = [];
                    if (isset($comments_by_parent[$parent_id])) {
                        foreach ($comments_by_parent[$parent_id] as $comment) {
                            $comment['replies'] = nestComments($comment['comment_id'], $comments_by_parent);
                            $tree[] = $comment;
                        }
                    }
                    return $tree;
                }
            }

            $comments = nestComments(0, $comments_by_parent);
        } catch (Exception $e) {
            // Table might not exist or other error
        }
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'news_id' => $news_data['news_id'],
            'title' => $news_data['title'],
            'content' => $news_data['content'],
            'excerpt' => $news_data['excerpt'],
            'category_name' => $category_name,
            'category_color' => $category_color,
            'author_name' => $author_name,
            'publication_date' => $news_data['publication_date'],
            'priority' => $news_data['priority'],
            'target_audience' => $news_data['target_audience'],
            'featured' => $news_data['featured'],
            'view_count' => $news_data['view_count'] + 1,
            'allow_comments' => $news_data['allow_comments'],
            'like_count' => $like_count,
            'is_liked' => $is_liked,
            'comment_count' => isset($all_comments) ? count($all_comments) : 0,
            'comments' => $comments,
            'related_news' => $related_news
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => __('error_loading_article'),
        'message' => 'An error occurred while loading the article'
    ]);
}
?>
