<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify user is logged in and is an owner
if (!$session->isLoggedIn() || $session->getUserRole() !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$notice_id = $_POST['notice_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$notice_id || !is_numeric($notice_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notice ID']);
    exit;
}

if (!in_array($status, ['pending', 'acknowledged', 'completed'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$user_id = $session->getUserId();

try {
    // Verify that this notice belongs to owner's property and get the related agreement/property
    $sql = "SELECT vn.notice_id, vn.agreement_id, ra.property_id 
            FROM vacating_notices vn
            JOIN rental_agreements ra ON vn.agreement_id = ra.agreement_id
            JOIN properties p ON ra.property_id = p.property_id
            WHERE vn.notice_id = ? AND p.owner_id = ?";
    
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$notice_id, $user_id]);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Vacating notice not found or access denied']);
        exit;
    }
    
    // Update the notice status
    if ($status === 'acknowledged') {
        $sql = "UPDATE vacating_notices SET status = ?, acknowledged_at = NOW(), acknowledged_by = ? WHERE notice_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$status, $user_id, $notice_id]);
    } else {
        $sql = "UPDATE vacating_notices SET status = ? WHERE notice_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$status, $notice_id]);
    }
    
    // If the notice has been completed, terminate the agreement and re-list the property
    if ($status === 'completed') {
        $sql = "UPDATE rental_agreements SET status = 'terminated', updated_at = NOW() WHERE agreement_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$result['agreement_id']]);

        $sql = "UPDATE properties SET status = 'available', updated_at = NOW() WHERE property_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$result['property_id']]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Vacating notice status updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
