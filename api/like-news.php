<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to like posts']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$news_id = isset($_POST['news_id']) ? intval($_POST['news_id']) : 0;
$user_id = $session->getUserId();

if ($news_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid news ID']);
    exit;
}

try {
    // Check if news exists
    $sql = "SELECT news_id FROM system_news WHERE news_id = ? AND status = 'published'";
    $stmt = $db->prepare($sql);
    if (!$stmt || !$db->getSingle($stmt, [$news_id])) {
        echo json_encode(['success' => false, 'message' => 'News article not found']);
        exit;
    }

    // Check if already liked
    $sql = "SELECT like_id FROM news_likes WHERE news_id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);
    $existing_like = $stmt ? $db->getSingle($stmt, [$news_id, $user_id]) : null;

    if ($existing_like) {
        // Unlike
        $sql = "DELETE FROM news_likes WHERE news_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) $db->execute($stmt, [$news_id, $user_id]);
        $action = 'unliked';
    } else {
        // Like
        $sql = "INSERT INTO news_likes (news_id, user_id) VALUES (?, ?)";
        $stmt = $db->prepare($sql);
        if ($stmt) $db->execute($stmt, [$news_id, $user_id]);
        $action = 'liked';
    }

    // Get updated like count
    $sql = "SELECT COUNT(*) as count FROM news_likes WHERE news_id = ?";
    $stmt = $db->prepare($sql);
    $result = $stmt ? $db->getSingle($stmt, [$news_id]) : null;
    $like_count = $result['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'action' => $action,
        'like_count' => $like_count
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing request: ' . $e->getMessage()]);
}
?>
