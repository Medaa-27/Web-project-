<?php
require_once '../includes/config.php';
$session->requireRole('admin');
header('Content-Type: application/json');
$pid = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
if ($pid <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment id']);
    exit;
}
$sql = "SELECT p.*, 
               u.full_name AS tenant_name, u.email AS tenant_email,
               ra.agreement_id, ra.start_date, ra.end_date,
               prop.title AS property_title, prop.property_id
        FROM payments p
        LEFT JOIN users u ON p.tenant_id = u.user_id
        LEFT JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
        LEFT JOIN properties prop ON ra.property_id = prop.property_id
        WHERE p.payment_id = ?";
$stmt = $db->prepare($sql);
$row = $db->getSingle($stmt, [$pid]);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Payment not found']);
    exit;
}
echo json_encode([
    'success' => true,
    'payment' => [
        'payment_id' => (int)$row['payment_id'],
        'transaction_id' => $row['transaction_id'],
        'amount' => (float)$row['amount'],
        'payment_method' => $row['payment_method'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'tenant_name' => $row['tenant_name'],
        'tenant_email' => $row['tenant_email'],
        'property_title' => $row['property_title'],
        'property_id' => (int)$row['property_id'],
        'agreement_id' => (int)$row['agreement_id'],
        'notes' => $row['notes'] ?? ''
    ]
]);
