<?php
require_once '../includes/config.php';

// Enable error reporting for debugging
error_log("Cancel request API called");

// Check if user is logged in and is a tenant
if (!$session->isLoggedIn() || $_SESSION['user_role'] !== 'tenant') {
    error_log("Unauthorized access attempt");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$request_id = $_POST['request_id'] ?? '';
$tenant_id = $session->getUserId();

error_log("Attempting to cancel request ID: $request_id by tenant ID: $tenant_id");

if (empty($request_id) || !is_numeric($request_id)) {
    error_log("Invalid request ID: $request_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

try {
    // Check if the request belongs to the current tenant and is pending
    $sql = "SELECT rr.*, p.status as property_status 
            FROM rental_requests rr
            JOIN properties p ON rr.property_id = p.property_id
            WHERE rr.request_id = ? AND rr.tenant_id = ? AND rr.status = 'pending'";
    $stmt = $db->prepare($sql);
    $request = $db->getSingle($stmt, [$request_id, $tenant_id]);
    
    if (!$request) {
        error_log("Request not found or cannot be cancelled. Request ID: $request_id, Tenant ID: $tenant_id");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Request not found or cannot be cancelled']);
        exit;
    }
    
    error_log("Request found: " . json_encode($request));
    
    // Update request status to cancelled
    $sql = "UPDATE rental_requests SET status = 'cancelled'
            WHERE request_id = ?";
    $stmt = $db->prepare($sql);
    $result = $db->execute($stmt, [$request_id]);
    error_log("Request update result: " . ($result ? 'success' : 'failed'));
    
    // Always set property back to available when request is cancelled
    $sql = "UPDATE properties SET status = 'available'
            WHERE property_id = ?";
    $stmt = $db->prepare($sql);
    $property_result = $db->execute($stmt, [$request['property_id']]);
    error_log("Property update result: " . ($property_result ? 'success' : 'failed'));
    error_log("Property ID " . $request['property_id'] . " set to available");
    
    // Send notification to property owner
    $sql = "SELECT owner_id FROM properties WHERE property_id = ?";
    $stmt = $db->prepare($sql);
    $owner = $db->getSingle($stmt, [$request['property_id']]);
    
    if ($owner) {
        error_log("Owner found: " . json_encode($owner));
        $sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                VALUES (?, 'Rental Request Cancelled', 
                'A tenant has cancelled their rental request for your property. Please check your requests panel.', 
                'warning', NOW())";
        $stmt = $db->prepare($sql);
        $notification_result = $db->execute($stmt, [$owner['owner_id']]);
        error_log("Notification insert result: " . ($notification_result ? 'success' : 'failed'));
        
        // Debug: Log notification
        error_log("Notification sent to owner ID: " . $owner['owner_id'] . " for cancelled request: " . $request_id);
    } else {
        error_log("Owner not found for property ID: " . $request['property_id']);
    }
    
    error_log("Cancellation process completed successfully");
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
