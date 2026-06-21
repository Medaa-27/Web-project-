<?php
// Start output buffering to prevent any unwanted output
ob_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Clear any buffered output
ob_clean();

// Set JSON content type
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Check if user is logged in and is admin
if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Check CSRF token
if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();

    // Hide all withdrawal transactions from admin view
    $stmt = $db->prepare("UPDATE wallet_transactions SET is_visible_admin = 0 WHERE transaction_type = 'withdrawal'");
    $stmt->execute();

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'All withdrawal history has been cleared successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log("Error clearing withdrawal history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to clear withdrawal history. Please try again.'
    ]);
}
?>