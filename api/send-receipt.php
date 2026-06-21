<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only admin/owner should send receipts
if (!$session->isLoggedIn() || !in_array($session->getUserRole(), ['admin', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Support JSON request bodies
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $input = array_merge($input, $decoded);
    }
}

$payment_id = $input['payment_id'] ?? 0;
$email = trim($input['email'] ?? '');

if (empty($payment_id) || !is_numeric($payment_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment id']);
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

$sql = "SELECT p.*, u.full_name AS tenant_name, u.email AS tenant_email, prop.title AS property_title
        FROM payments p
        LEFT JOIN users u ON p.tenant_id = u.user_id
        LEFT JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
        LEFT JOIN properties prop ON ra.property_id = prop.property_id
        WHERE p.payment_id = ?";
$stmt = $db->prepare($sql);
$payment = $db->getSingle($stmt, [$payment_id]);

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment not found']);
    exit();
}

$subject = "Payment Receipt - " . SITE_NAME;

$vars = [
    'tenant_name' => $payment['tenant_name'] ?? '',
    'amount' => number_format($payment['amount'], 2),
    'payment_method' => $payment['payment_method'] ?? '',
    'transaction_id' => $payment['transaction_id'] ?? '',
    'property_title' => $payment['property_title'] ?? '',
    'date' => date('M d, Y', strtotime($payment['created_at'] ?? '')),
    'payment_id' => $payment['payment_id'],
];

$sent = sendEmailTemplate($email, $subject, 'payment_received', $vars);

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Receipt sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send receipt']);
}
