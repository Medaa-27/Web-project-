<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check if user is employee
if (!$session->isLoggedIn() || !$session->hasRole('employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$property_id = intval($_POST['property_id'] ?? 0);
$action = $_POST['action'] ?? '';
$comments = trim($_POST['comments'] ?? '');

// Validate inputs
if ($property_id <= 0 || !in_array($action, ['approve', 'reject', 'request_revision'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

if (in_array(['reject', 'request_revision'], [$action]) && empty($comments)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comments are required for this action']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Get property details
    $sql = "SELECT p.*, u.full_name as owner_name, u.user_id as owner_id 
            FROM properties p 
            JOIN users u ON p.owner_id = u.user_id 
            WHERE p.property_id = ? AND p.review_status = 'pending'";
    $stmt = $db->prepare($sql);
    $property = $db->getSingle($stmt, [$property_id]);
    
    if (!$property) {
        throw new Exception("Property not found or already reviewed");
    }
    
    // Update property review status
    $review_status = match($action) {
        'approve' => 'approved',
        'reject' => 'rejected',
        'request_revision' => 'needs_revision',
        default => throw new Exception("Invalid action")
    };
    
    $property_status = ($action === 'approve') ? 'available' : 'pending';
    $employee_id = $session->getUserId();
    
    $sql = "UPDATE properties 
            SET review_status = ?, status = ?, reviewed_by = ?, 
                review_date = NOW(), review_comments = ?
            WHERE property_id = ?";
    $stmt = $db->prepare($sql);
    $db->execute($stmt, [$review_status, $property_status, $employee_id, $comments, $property_id]);
    
    // Create notification for property owner
    $notification_title = match($action) {
        'approve' => 'Property Approved',
        'reject' => 'Property Rejected',
        'request_revision' => 'Property Revision Requested'
    };
    
    $notification_message = match($action) {
        'approve' => "Your property '{$property['title']}' has been approved and is now visible to tenants.",
        'reject' => "Your property '{$property['title']}' has been rejected. Reason: {$comments}",
        'request_revision' => "Your property '{$property['title']}' needs revision. Please update the information and resubmit. Comments: {$comments}"
    };
    
    $notification_type = match($action) {
        'approve' => 'success',
        'reject' => 'error',
        'request_revision' => 'warning'
    };
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $link = "../owner/edit-property.php?id={$property_id}";
    $db->execute($stmt, [$property['owner_id'], $notification_title, $notification_message, $notification_type, $link]);
    
    // Log the review action
    $sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value, ip_address, user_agent) 
            VALUES (?, 'property_review', 'properties', ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $old_value = "review_status: pending";
    $new_value = "review_status: {$review_status}, comments: {$comments}";
    $db->execute($stmt, [
        $employee_id, 
        $property_id, 
        $old_value, 
        $new_value, 
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Property has been " . ucfirst($review_status) . " successfully",
        'property_id' => $property_id,
        'review_status' => $review_status
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => "Error reviewing property: " . $e->getMessage()
    ]);
}
?>
