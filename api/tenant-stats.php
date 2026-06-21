<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is a tenant
if (!$session->isLoggedIn() || $session->getUserRole() !== 'tenant') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $session->getUserId();

try {
    $stats = [];
    
    // Get active rental agreements
    $sql = "SELECT COUNT(*) as count FROM rental_agreements 
            WHERE tenant_id = ? AND status = 'active'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['active_rentals'] = $result['count'] ?? 0;
    
    // Get pending rental requests
    $sql = "SELECT COUNT(*) as count FROM rental_requests 
            WHERE tenant_id = ? AND status = 'pending'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['pending_requests'] = $result['count'] ?? 0;
    
    // Get unread notifications
    $sql = "SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = 0";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['unread_notifications'] = $result['count'] ?? 0;
    
    // Get pending maintenance requests
    $sql = "SELECT COUNT(*) as count FROM maintenance_requests 
            WHERE tenant_id = ? AND status = 'pending'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['pending_maintenance'] = $result['count'] ?? 0;
    
    // Get total payments made
    $sql = "SELECT COUNT(*) as count FROM payments 
            WHERE tenant_id = ? AND status = 'completed'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['total_payments'] = $result['count'] ?? 0;
    
    // Get upcoming rent payments (due in next 7 days)
    $sql = "SELECT COUNT(*) as count FROM rental_agreements ra
            LEFT JOIN payments p ON ra.agreement_id = p.agreement_id 
                AND p.status = 'completed' 
                AND p.created_at >= DATE_SUB(ra.end_date, INTERVAL 30 DAY)
            WHERE ra.tenant_id = ? AND ra.status = 'active' 
                AND ra.end_date > NOW()
                AND (p.payment_id IS NULL OR p.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
                AND ra.end_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['upcoming_payments'] = $result['count'] ?? 0;
    
    // Get total feedback submitted
    $sql = "SELECT COUNT(*) as count FROM feedback 
            WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['total_feedback'] = $result['count'] ?? 0;
    
    // Get vacating notices
    $sql = "SELECT COUNT(*) as count FROM vacating_notices 
            WHERE tenant_id = ? AND status = 'pending'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$user_id]);
    $stats['pending_notices'] = $result['count'] ?? 0;
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load stats']);
}
?>
