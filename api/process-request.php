<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!$session->isLoggedIn() || $session->getUserRole() !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$owner_id = (int)$session->getUserId();
$request_id = $_POST['request_id'] ?? null;
$action = $_POST['action'] ?? '';

if (!is_numeric($request_id) || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$request_id = (int)$request_id;

try {
    $sql = "SELECT rr.*, p.property_id, p.owner_id, p.title, p.monthly_rent, p.security_deposit
            FROM rental_requests rr
            JOIN properties p ON rr.property_id = p.property_id
            WHERE rr.request_id = ? AND p.owner_id = ?";
    $stmt = $db->prepare($sql);
    $req = $db->getSingle($stmt, [$request_id, $owner_id]);

    if (!$req) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    if (($req['status'] ?? '') !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Request already processed']);
        exit;
    }

    if ($action === 'approve') {
        $sql = "UPDATE rental_requests
                SET status = 'approved', approved_by = ?, approved_at = NOW()
                WHERE request_id = ?";
        $db->execute($db->prepare($sql), [$owner_id, $request_id]);

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+' . (int)AGREEMENT_PERIOD . ' months'));
        $monthly_rent = (float)$req['monthly_rent'];
        $security_deposit = $req['security_deposit'] !== null ? (float)$req['security_deposit'] : 0;
        $advance_payment = round($monthly_rent * (ADVANCE_PERCENTAGE / 100), 2);

        $sql = "INSERT INTO rental_agreements
                    (request_id, tenant_id, property_id, start_date, end_date, monthly_rent, security_deposit, advance_payment,
                     status, signed_by_tenant, signed_by_owner, created_at, updated_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, 'active', 0, 1, NOW(), NOW())";
        $db->execute($db->prepare($sql), [
            $request_id,
            (int)$req['tenant_id'],
            (int)$req['property_id'],
            $start_date,
            $end_date,
            $monthly_rent,
            $security_deposit,
            $advance_payment
        ]);

        $sql = "UPDATE properties SET status = 'rented' WHERE property_id = ?";
        $db->execute($db->prepare($sql), [(int)$req['property_id']]);

        $sql = "UPDATE rental_requests
                SET status = 'rejected', approved_by = ?, approved_at = NOW()
                WHERE property_id = ? AND status = 'pending' AND request_id != ?";
        $db->execute($db->prepare($sql), [$owner_id, (int)$req['property_id'], $request_id]);

        $msg = "Your rental request for '" . $req['title'] . "' has been approved.";
        createNotification((int)$req['tenant_id'], 'Rental Request Approved', $msg, 'success', '../tenant/agreements.php', 15);

        // Send email to tenant
        try {
            require_once '../includes/functions.php';
            $tenantStmt = $db->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
            $tenant = $db->getSingle($tenantStmt, [(int)$req['tenant_id']]);
            if ($tenant && !empty($tenant['email'])) {
                $subject = "Rental Request Approved - " . SITE_NAME;
                sendEmailTemplate($tenant['email'], $subject, 'request_decision', [
                    'user_name' => $tenant['full_name'],
                    'property_title' => $req['title'],
                    'status' => 'Approved',
                    'status_class' => 'status-approved',
                    'decision_date' => date('Y-m-d H:i:s'),
                    'message' => "Congratulations! Your rental request for '{$req['title']}' has been approved. You can now view your agreement and proceed with the payment.",
                    'action_link' => SITE_URL . 'tenant/agreements.php',
                    'site_name' => SITE_NAME
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to send approval email: " . $e->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Request approved']);
        exit;
    }

    // reject
    $sql = "UPDATE rental_requests
            SET status = 'rejected', approved_by = ?, approved_at = NOW()
            WHERE request_id = ?";
    $db->execute($db->prepare($sql), [$owner_id, $request_id]);

    $sql = "UPDATE properties SET status = 'available' WHERE property_id = ?";
    $db->execute($db->prepare($sql), [(int)$req['property_id']]);

    $msg = "Your rental request for '" . $req['title'] . "' was rejected.";
    createNotification((int)$req['tenant_id'], 'Rental Request Rejected', $msg, 'warning', null, 15);

    // Send email to tenant
    try {
        require_once '../includes/functions.php';
        $tenantStmt = $db->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
        $tenant = $db->getSingle($tenantStmt, [(int)$req['tenant_id']]);
        if ($tenant && !empty($tenant['email'])) {
            $subject = "Rental Request Update - " . SITE_NAME;
            $reject_msg = "We regret to inform you that your rental request for '{$req['title']}' was rejected.";
            sendEmailTemplate($tenant['email'], $subject, 'request_decision', [
                'user_name' => $tenant['full_name'],
                'property_title' => $req['title'],
                'status' => 'Rejected',
                'status_class' => 'status-rejected',
                'decision_date' => date('Y-m-d H:i:s'),
                'message' => $reject_msg,
                'action_link' => SITE_URL . 'tenant/requests.php',
                'site_name' => SITE_NAME
            ]);
        }
    } catch (Exception $e) {
        error_log("Failed to send rejection email: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Request rejected']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
