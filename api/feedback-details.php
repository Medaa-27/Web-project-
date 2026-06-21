<?php
require_once '../includes/config.php';
$session->requireRole('admin');
header('Content-Type: application/json');
$fid = isset($_GET['feedback_id']) ? (int)$_GET['feedback_id'] : 0;
if ($fid <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid feedback id']);
    exit;
}
$sql = "SELECT f.*, u.full_name, u.email, u.role as user_role, 
               p.title as property_title,
               e.full_name AS reviewed_by_name
        FROM feedback f
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN properties p ON f.property_id = p.property_id
        LEFT JOIN users e ON f.reviewed_by = e.user_id
        WHERE f.feedback_id = ?";
$stmt = $db->prepare($sql);
$row = $db->getSingle($stmt, [$fid]);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Feedback not found']);
    exit;
}
$responses = [];
if (!empty($row['response'])) {
    $responses[] = [
        'admin_name' => $row['reviewed_by_name'] ?: 'Admin',
        'created_at' => $row['reviewed_at'] ?: $row['created_at'],
        'response' => $row['response']
    ];
}
echo json_encode([
    'success' => true,
    'feedback' => [
        'feedback_id' => (int)$row['feedback_id'],
        'property_title' => $row['property_title'] ?: 'General',
        'type' => $row['type'],
        'status' => $row['status'],
        'rating' => (int)$row['rating'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'user_role' => $row['user_role'],
        'created_at' => $row['created_at'],
        'comment' => $row['comment'],
        'responses' => $responses
    ]
]);
