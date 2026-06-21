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

// Validate tenant IDs
if (!isset($input['tenant_ids']) || !is_array($input['tenant_ids']) || empty($input['tenant_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No tenants selected']);
    exit;
}

$tenantIds = array_map('intval', $input['tenant_ids']);

// Verify all selected users are actually tenants
$placeholders = str_repeat('?,', count($tenantIds) - 1) . '?';
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE user_id IN ($placeholders) AND role = 'tenant'");
$result = $db->getSingle($stmt, $tenantIds);

if (!$result || $result['count'] != count($tenantIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid tenant selection']);
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();

    if (!empty($tenantIds)) {
        // Delete all wallet transactions for selected tenants
        $placeholders = str_repeat('?,', count($tenantIds) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM wallet_transactions WHERE wallet_id IN (
            SELECT wallet_id FROM wallets WHERE user_id IN ($placeholders)
        )");
        $stmt->execute($tenantIds);

        // Reset selected tenant wallet balances to 0.00
        $stmt = $db->prepare("UPDATE wallets SET balance = 0.00 WHERE user_id IN ($placeholders)");
        $stmt->execute($tenantIds);
    }

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Selected tenant wallet history has been cleared and balances reset to zero'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log("Error clearing tenant wallet history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to clear tenant wallet history. Please try again.'
    ]);
}
?>