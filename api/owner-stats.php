<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!$session->isLoggedIn() || $session->getUserRole() !== 'owner') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$owner_id = $session->getUserId();

try {
    $stats = [];

    $sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ?";
    $stats['total_properties'] = (int)($db->getSingle($db->prepare($sql), [$owner_id])['count'] ?? 0);

    $sql = "SELECT COUNT(*) as count
            FROM rental_requests r
            JOIN properties p ON r.property_id = p.property_id
            WHERE p.owner_id = ? AND r.status = 'pending'";
    $stats['pending_requests'] = (int)($db->getSingle($db->prepare($sql), [$owner_id])['count'] ?? 0);

    $sql = "SELECT COUNT(DISTINCT ra.tenant_id) as count
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            WHERE p.owner_id = ? AND ra.status = 'active'";
    $stats['active_tenants'] = (int)($db->getSingle($db->prepare($sql), [$owner_id])['count'] ?? 0);

    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stats['unread_notifications'] = (int)($db->getSingle($db->prepare($sql), [$owner_id])['count'] ?? 0);

    $sql = "SELECT COUNT(*) as count
            FROM maintenance_requests mr
            JOIN properties p ON mr.property_id = p.property_id
            WHERE p.owner_id = ? AND mr.status = 'pending'";
    $stats['pending_maintenance'] = (int)($db->getSingle($db->prepare($sql), [$owner_id])['count'] ?? 0);

    $sql = "SELECT COUNT(*) as count
            FROM feedback f
            JOIN properties p ON f.property_id = p.property_id
            WHERE p.owner_id = ? AND f.status = 'pending'";
    $stats['pending_feedback'] = (int)($db->getSingle($db->prepare($sql), [$owner_id])['count'] ?? 0);

    echo json_encode($stats);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load stats']);
}
