<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$db = new Database();
$property_id = intval($_POST['property_id'] ?? 0);
$new_status = $_POST['status'] ?? '';
$notes = $_POST['notes'] ?? '';

if ($property_id <= 0 || empty($new_status)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

$valid_statuses = ['available', 'requested', 'rented', 'under_maintenance', 'inactive'];
if (!in_array($new_status, $valid_statuses)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid status']));
}

try {
    // Get current property info
    $current_sql = "SELECT * FROM properties WHERE property_id = ?";
    $current = $db->getSingle($db->prepare($current_sql), [$property_id]);
    
    if (!$current) {
        exit(json_encode(['success' => false, 'message' => 'Property not found']));
    }

    // Update property status
    $update_sql = "UPDATE properties SET status = ?, updated_at = NOW() WHERE property_id = ?";
    $stmt = $db->prepare($update_sql);
    $result = $db->execute($stmt, [$new_status, $property_id]);

    if ($result) {
        // Log the status change
        $log_sql = "INSERT INTO property_activity_log (property_id, employee_id, action, old_value, new_value, notes, created_at) 
                    VALUES (?, ?, 'status_change', ?, ?, ?, NOW())";
        $log_stmt = $db->prepare($log_sql);
        $db->execute($log_stmt, [
            $property_id,
            $_SESSION['user_id'],
            $current['status'],
            $new_status,
            $notes
        ]);

        // Send notification to property owner
        $notification_sql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                            VALUES (?, 'info', 'Property Status Updated', 
                            'Your property \"{$current['title']}\" status has been updated to " . ucfirst(str_replace('_', ' ', $new_status)) . ". 
                            Notes: " . htmlspecialchars($notes) . "', 
                            '../owner/properties.php', 0, NOW())";
        $notification_stmt = $db->prepare($notification_sql);
        $db->execute($notification_stmt, [$current['owner_id']]);

        echo json_encode(['success' => true, 'message' => 'Property status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update property status']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
