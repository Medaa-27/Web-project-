<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $session->getUserId();
$role = $session->getUserRole();

$success = true;

try {
    $sql = "DELETE FROM notifications WHERE user_id = ? AND is_read = 1";
    $stmt = $db->prepare($sql);
    $success = $success && (bool)$db->execute($stmt, [(int)$user_id]);

    if (in_array($role, ['owner', 'employee'], true)) {
        $column = $role === 'owner' ? 'owner_id' : 'employee_id';
        $sql = "DELETE FROM property_review_notifications WHERE {$column} = ? AND is_read = 1";
        $stmt = $db->prepare($sql);
        $success = $success && (bool)$db->execute($stmt, [(int)$user_id]);
    }
} catch (Exception $e) {
    $success = false;
}

echo json_encode(['success' => $success]);
