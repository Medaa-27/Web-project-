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
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $success = $success && (bool)$db->execute($stmt, [(int)$user_id]);

    if (in_array($role, ['owner', 'employee'], true)) {
        $column = $role === 'owner' ? 'owner_id' : 'employee_id';
        $sql = "UPDATE property_review_notifications SET is_read = 1 WHERE {$column} = ?";
        $stmt = $db->prepare($sql);
        $success = $success && (bool)$db->execute($stmt, [(int)$user_id]);
    }
} catch (Exception $e) {
    $success = false;
}

echo json_encode(['success' => $success]);
