<?php
require_once '../includes/config.php';

// Require admin login
$session->requireRole('admin');

// Set JSON response header
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = (int)$data['user_id'];
$action = $data['action'];

if (!in_array($action, ['activate', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Get user details before action
    $sql = "SELECT full_name, email, role FROM users WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $user = $db->getSingle($stmt, [$userId]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if ($action === 'activate') {
        // Activate user account
        $sql = "UPDATE users SET status = 'active', updated_at = NOW() WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $result = $db->execute($stmt, [$userId]);
        
        if ($result) {
            // Log the activation
            logActivity($_SESSION['user_id'], 'Activated user account: ' . $user['full_name'] . ' (' . $user['role'] . ')');
            
            // Create notification for the user
            $notification_message = "Your account has been activated! You can now log in and access the system.";
            $sql = "INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, 'success', ?, 0, NOW())";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$userId, $notification_message]);
            
            echo json_encode(['success' => true, 'message' => 'User account activated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to activate user account']);
        }
    } elseif ($action === 'reject') {
        // Reject and delete user account
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $result = $db->execute($stmt, [$userId]);
        
        if ($result) {
            // Log the rejection
            logActivity($_SESSION['user_id'], 'Rejected and deleted user account: ' . $user['full_name'] . ' (' . $user['role'] . ')');
            
            echo json_encode(['success' => true, 'message' => 'User account rejected and deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject user account']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
