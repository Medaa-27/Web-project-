<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$news_id = $_POST['news_id'] ?? 0;
$user_id = $session->getUserId(); // Use session ID for security
$comment = trim($_POST['comment'] ?? '');
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;

// Validate input
if (empty($news_id) || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Comment content is required']);
    exit;
}

try {
    // Check if news exists and comments are allowed
    $sql = "SELECT allow_comments FROM system_news WHERE news_id = ? AND status = 'published'";
    $stmt = $db->prepare($sql);
    $news = $stmt ? $db->getSingle($stmt, [$news_id]) : null;

    if (!$news) {
        echo json_encode(['success' => false, 'message' => 'News article not found']);
        exit;
    }

    if (!$news['allow_comments']) {
        echo json_encode(['success' => false, 'message' => 'Comments are not allowed for this article']);
        exit;
    }

    // If parent_id is set, verify it exists and belongs to the same news article
    if ($parent_id !== NULL && $parent_id > 0) {
        $sql = "SELECT comment_id FROM news_comments WHERE comment_id = ? AND news_id = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt || !$db->getSingle($stmt, [$parent_id, $news_id])) {
            $parent_id = NULL; // Reset if invalid
        }
    } else {
        $parent_id = NULL;
    }

    // Insert comment
    $sql = "INSERT INTO news_comments (news_id, parent_id, user_id, comment, status) VALUES (?, ?, ?, ?, 'approved')";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Unable to prepare comment statement. Please check if the system is up to date.");
    }
    $result = $db->execute($stmt, [$news_id, $parent_id, $user_id, $comment]);

    if ($result) {
        $new_id = $db->getLastInsertId();
        
        // Get user info for immediate display
        $user_sql = "SELECT full_name, profile_image FROM users WHERE user_id = ?";
        $user_stmt = $db->prepare($user_sql);
        $user = $db->getSingle($user_stmt, [$user_id]);

        echo json_encode([
            'success' => true, 
            'message' => 'Comment posted successfully',
            'comment_id' => $new_id,
            'user_name' => $user['full_name'] ?? 'User',
            'created_at' => date('Y-m-d H:i:s'),
            'parent_id' => $parent_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post comment']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
