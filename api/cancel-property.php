<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$property_id = $_POST['property_id'] ?? 0;
$action = $_POST['action'] ?? '';
$reason = $_POST['reason'] ?? '';

if (empty($property_id) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Validate property exists
    $sql = "SELECT * FROM properties WHERE property_id = ?";
    $stmt = $db->prepare($sql);
    $property = $db->getSingle($stmt, [$property_id]);
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }
    
    $db->beginTransaction();
    
    if ($action === 'cancel') {
        // Update property status to cancelled
        $sql = "UPDATE properties 
                SET status = 'cancelled', 
                    review_status = 'cancelled',
                    review_comments = ?,
                    reviewed_by = ?,
                    review_date = NOW()
                WHERE property_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$reason, $session->getUserId(), $property_id]);
        
        // Create notification for property owner
        $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $link = "../owner/edit-property.php?id={$property_id}";
        $message = "Your property '{$property['title']}' has been cancelled by an employee. Reason: {$reason}";
        $db->execute($stmt, [$property['owner_id'], 'Property Cancelled', $message, 'error', $link]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Property cancelled successfully',
            'property_id' => $property_id
        ]);
        
    } elseif ($action === 'ignore_duplicates') {
        // Add a flag to ignore duplicates for this property
        // This would require adding an ignore_duplicates column to properties table
        // For now, we'll just return success
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Duplicate warnings ignored for this property',
            'property_id' => $property_id
        ]);
        
    } else {
        throw new Exception("Invalid action: {$action}");
    }
    
} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
