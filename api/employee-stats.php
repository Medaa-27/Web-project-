<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is an employee
if (!$session->isLoggedIn() || $session->getUserRole() !== 'employee') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$employee_id = $session->getUserId();

try {
    $stats = [];
    
    // Pending property reviews - try with review_status column, fallback to available properties
    try {
        $sql = "SELECT COUNT(*) as count FROM properties WHERE review_status = 'pending'";
        $stmt = $db->prepare($sql);
        $result = $db->getSingle($stmt);
        $stats['pending_properties'] = $result['count'] ?? 0;
    } catch (Exception $e) {
        // Fallback: count available properties
        $sql = "SELECT COUNT(*) as count FROM properties WHERE status = 'available'";
        $stmt = $db->prepare($sql);
        $result = $db->getSingle($stmt);
        $stats['pending_properties'] = $result['count'] ?? 0;
    }
    
    // Properties to verify
    $sql = "SELECT COUNT(*) as count FROM properties WHERE status = 'available'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['properties_to_verify'] = $result['count'] ?? 0;
    
    // Total properties
    $sql = "SELECT COUNT(*) as count FROM properties";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['total_properties'] = $result['count'] ?? 0;
    
    // Active properties
    $sql = "SELECT COUNT(*) as count FROM properties WHERE status = 'active'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['active_properties'] = $result['count'] ?? 0;
    
    // Pending rental requests
    $sql = "SELECT COUNT(*) as count FROM rental_requests WHERE status = 'pending'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['pending_requests'] = $result['count'] ?? 0;
    
    // Active tenants
    $sql = "SELECT COUNT(*) as count FROM rental_agreements WHERE status = 'active'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['active_tenants'] = $result['count'] ?? 0;
    
    // Pending feedback
    $sql = "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['pending_feedback'] = $result['count'] ?? 0;
    
    // Open support tickets
    $sql = "SELECT COUNT(*) as count FROM support_tickets WHERE status = 'open'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['open_tickets'] = $result['count'] ?? 0;
    
    // Unread notifications for employee
    $unread_notifications = 0;
    try {
        // Regular notifications
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $db->prepare($sql);
        $result = $db->getSingle($stmt, [$employee_id]);
        $unread_notifications += (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        // Ignore if notifications table doesn't exist
    }
    
    try {
        // Property review notifications
        $sql = "SELECT COUNT(*) as count FROM property_review_notifications WHERE employee_id = ? AND is_read = 0";
        $stmt = $db->prepare($sql);
        $result = $db->getSingle($stmt, [$employee_id]);
        $unread_notifications += (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        // Ignore if property review notifications table doesn't exist
    }
    
    $stats['unread_notifications'] = $unread_notifications;
    
    echo json_encode($stats);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
