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
$notification_id = $_POST['notification_id'] ?? null;
$source = $_POST['source'] ?? 'regular';

if (!is_numeric($notification_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification']);
    exit;
}

$success = false;
try {
    if ($source === 'property_review') {
        $role = $session->getUserRole();
        if ($role === 'owner') {
            $sql = "DELETE FROM property_review_notifications WHERE notification_id = ? AND owner_id = ?";
        } elseif ($role === 'employee') {
            $sql = "DELETE FROM property_review_notifications WHERE notification_id = ? AND employee_id = ?";
        } else {
            echo json_encode(['success' => false, 'message' => 'Action not allowed']);
            exit;
        }
        $stmt = $db->prepare($sql);
        $success = (bool)$db->execute($stmt, [(int)$notification_id, (int)$user_id]);
    }

    if (!$success) {
        $sql = "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $success = (bool)$db->execute($stmt, [(int)$notification_id, (int)$user_id]);
    }
} catch (Exception $e) {
    $success = false;
}

echo json_encode(['success' => $success]);
