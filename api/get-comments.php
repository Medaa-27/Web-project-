<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

$news_id = isset($_GET['news_id']) ? intval($_GET['news_id']) : 0;
$current_user_id = $session->isLoggedIn() ? $session->getUserId() : 0;

if ($news_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid news ID']);
    exit;
}

try {
    // 1. Get likes info
    $sql = "SELECT COUNT(*) as count FROM news_likes WHERE news_id = ?";
    $stmt = $db->prepare($sql);
    $like_result = $stmt ? $db->getSingle($stmt, [$news_id]) : null;
    $like_count = $like_result['count'] ?? 0;

    $is_liked = false;
    if ($current_user_id > 0) {
        $sql = "SELECT like_id FROM news_likes WHERE news_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $is_liked = $stmt ? (bool)$db->getSingle($stmt, [$news_id, $current_user_id]) : false;
    }

    // 2. Get comments and replies
    // Fetch all approved comments for this news
    $sql = "SELECT nc.*, u.full_name, u.profile_image 
            FROM news_comments nc
            LEFT JOIN users u ON nc.user_id = u.user_id
            WHERE nc.news_id = ? AND nc.status = 'approved'
            ORDER BY nc.created_at ASC";
    $stmt = $db->prepare($sql);
    $all_comments = $stmt ? $db->getMultiple($stmt, [$news_id]) : [];

    // Group comments by parent_id
    $comments_by_parent = [];
    foreach ($all_comments as $c) {
        $parent_id = $c['parent_id'] ?: 0;
        if (!isset($comments_by_parent[$parent_id])) {
            $comments_by_parent[$parent_id] = [];
        }
        $comments_by_parent[$parent_id][] = $c;
    }

    // Recursive function to build comment tree
    function buildCommentTree($parent_id, $comments_by_parent) {
        $tree = [];
        if (isset($comments_by_parent[$parent_id])) {
            foreach ($comments_by_parent[$parent_id] as $comment) {
                $comment['replies'] = buildCommentTree($comment['comment_id'], $comments_by_parent);
                $tree[] = $comment;
            }
        }
        return $tree;
    }

    $comment_tree = buildCommentTree(0, $comments_by_parent);

    echo json_encode([
        'success' => true,
        'like_count' => $like_count,
        'is_liked' => $is_liked,
        'comment_count' => count($all_comments),
        'comments' => $comment_tree
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
