<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
header('Content-Type: application/json');

// Only allow logged-in users
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_role = $session->getUserRole();
if (!in_array($user_role, ['owner', 'employee', 'tenant'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$notification_id = $_POST['notification_id'] ?? 0;

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit;
}

try {
    $user_id = $session->getUserId();
    
    // First try to update regular notifications table
    $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);
    $db->execute($stmt, [$notification_id, $user_id]);
    
    $rows_affected = $stmt ? $stmt->rowCount() : 0;
    
    // If no rows were affected in regular notifications, try property review notifications
    if ($rows_affected === 0 && in_array($user_role, ['owner', 'employee'], true)) {
        $column = $user_role === 'owner' ? 'owner_id' : 'employee_id';
        $sql = "UPDATE property_review_notifications SET is_read = 1 WHERE notification_id = ? AND {$column} = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$notification_id, $user_id]);
        $rows_affected = $stmt ? $stmt->rowCount() : 0;
    }
    
    if ($rows_affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found or already read']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
