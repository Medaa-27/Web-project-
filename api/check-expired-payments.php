<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

// This script can be run via CRON or manually
// It checks for partially paid agreements that have passed their payment deadline

header('Content-Type: application/json');

$db->beginTransaction();

try {
    // 1. Find expired agreements
    $sql = "SELECT ra.*, p.title as property_title, p.owner_id
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            WHERE ra.status = 'partially_paid' 
            AND ra.payment_deadline < NOW()";
    
    $stmt = $db->prepare($sql);
    $expired_agreements = $db->getMultiple($stmt);

    $processed_count = 0;

    foreach ($expired_agreements as $agreement) {
        $agreementId = $agreement['agreement_id'];
        $propertyId = $agreement['property_id'];
        $tenantId = $agreement['tenant_id'];
        $ownerId = $agreement['owner_id'];
        $requestId = $agreement['request_id'];

        // 2. Update agreement status to 'expired'
        $updateSql = "UPDATE rental_agreements SET status = 'expired', updated_at = NOW() WHERE agreement_id = ?";
        $updateStmt = $db->prepare($updateSql);
        $db->execute($updateStmt, [$agreementId]);

        // 3. Update request status to 'expired' (if exists)
        $reqUpdateSql = "UPDATE rental_requests SET status = 'cancelled' WHERE request_id = ?";
        $reqUpdateStmt = $db->prepare($reqUpdateSql);
        $db->execute($reqUpdateStmt, [$requestId]);

        // 4. Make property available again
        $propSql = "UPDATE properties SET status = 'available', updated_at = NOW() WHERE property_id = ?";
        $propStmt = $db->prepare($propSql);
        $db->execute($propStmt, [$propertyId]);

        // 5. Distribute the 20% advance payment (non-refundable)
        // Find the successful 20% payment for this agreement
        $paySql = "SELECT * FROM payments 
                   WHERE agreement_id = ? AND payment_type = 'MINIMUM' AND status = 'completed' 
                   ORDER BY created_at DESC LIMIT 1";
        $payStmt = $db->prepare($paySql);
        $payment = $db->getSingle($payStmt, [$agreementId]);

        $landlordAmount = 0;
        if ($payment) {
            $amount = (float)$payment['amount_paid']; // Use amount_paid (base rent part)
            
            // 80% to landlord
            $landlordAmount = $amount * 0.80;
            $landlordDesc = "Non-refundable advance payment from expired agreement #$agreementId for property '{$agreement['property_title']}' (80% share)";
            logWalletTransaction($ownerId, $landlordAmount, 'deposit', 'completed', $landlordDesc, 'payments', $payment['payment_id']);

            // 20% to platform (admin)
            $platformAmount = $amount * 0.20;
            $adminSql = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
            $adminStmt = $db->prepare($adminSql);
            $admin = $db->getSingle($adminStmt);
            
            if ($admin) {
                $platformDesc = "Platform fee from expired agreement #$agreementId (20% share of non-refundable advance)";
                logWalletTransaction($admin['user_id'], $platformAmount, 'deposit', 'completed', $platformDesc, 'payments', $payment['payment_id']);
            }
        }

        // 6. Notifications
        $notifSql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
        $notifStmt = $db->prepare($notifSql);

        // Notify Tenant
        $tenantMsg = "Your rental agreement for '{$agreement['property_title']}' has expired because the remaining balance was not paid within the 10-day deadline. The advance payment is non-refundable.";
        $db->execute($notifStmt, [$tenantId, "Agreement Expired", $tenantMsg, 'danger']);

        // Notify Owner
        $ownerMsg = "The rental agreement for your property '{$agreement['property_title']}' has expired. The property is now available for rent again. You have been credited ETB " . number_format($landlordAmount, 2) . " from the non-refundable advance payment.";
        $db->execute($notifStmt, [$ownerId, "Agreement Expired", $ownerMsg, 'info']);
        
        $processed_count++;
    }

    $db->commit();
    echo json_encode(['success' => true, 'processed_count' => $processed_count]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
