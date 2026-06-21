<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

header('Content-Type: application/json');

// Allow admin or owner to verify/reject payments
if (!$session->isLoggedIn() || !in_array($session->getUserRole(), ['owner', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Support JSON request bodies (fetch API)
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $input = array_merge($input, $decoded);
    }
}

// CSRF check
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrf_token) || !verifyCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit();
}

$approver_id = $session->getUserId();
$payment_id = $input['payment_id'] ?? 0;
$action = $input['action'] ?? '';
$notes = $input['notes'] ?? '';

// Validate
if (empty($payment_id) || !is_numeric($payment_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment']);
    exit();
}

// Normalize action values (frontend may send 'verify' for approval)
$action = $action === 'verify' ? 'approve' : $action;

if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Check if payment exists (owner can only process their own payments; admin can process any)
$sql = "SELECT p.*, prop.title, prop.owner_id
        FROM payments p
        LEFT JOIN properties prop ON p.property_id = prop.property_id
        WHERE p.payment_id = ? AND p.status = 'pending'";
$params = [$payment_id];

if ($session->getUserRole() === 'owner') {
    $sql .= " AND prop.owner_id = ?";
    $params[] = $approver_id;
}

$stmt = $db->prepare($sql);
$payment = $db->getSingle($stmt, $params);

if (!$payment) {
    // If not found by property_id, try finding via agreement_id as fallback
    $sql = "SELECT p.*, prop.title, prop.owner_id
            FROM payments p
            JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
            JOIN properties prop ON ra.property_id = prop.property_id
            WHERE p.payment_id = ? AND p.status = 'pending'";
    $params = [$payment_id];
    
    if ($session->getUserRole() === 'owner') {
        $sql .= " AND prop.owner_id = ?";
        $params[] = $approver_id;
    }
    
    $stmt = $db->prepare($sql);
    $payment = $db->getSingle($stmt, $params);
}

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment not found, already processed, or you do not have permission to verify it.']);
    exit();
}


if ($action === 'approve') {
    // Approve payment
    $db->beginTransaction();
    try {
        $sql = "UPDATE payments SET status = 'completed', payment_status = 'Verified', verified_by = ?, verified_at = NOW(), notes = ?
                WHERE payment_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$approver_id, $notes, $payment_id]);

        // Logic for handling rental agreement status based on payment type
        if (in_array($payment['payment_for'], ['rent', 'advance'])) {
            $agreementId = $payment['agreement_id'];
            $paymentType = $payment['payment_type'];
            
            if ($paymentType === 'MINIMUM') {
                // Minimum payment (20%) - set to partially_paid and set deadline
                $deadline = date('Y-m-d H:i:s', strtotime('+10 days'));
                $sql = "UPDATE rental_agreements 
                        SET status = 'partially_paid', 
                            payment_deadline = ?,
                            updated_at = NOW()
                        WHERE agreement_id = ?";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$deadline, $agreementId]);
            } else if ($paymentType === 'FULL' || $paymentType === 'MONTHLY' || $payment['balance_remaining'] <= 0) {
                // Full payment or balance cleared - set to active
                $sql = "UPDATE rental_agreements 
                        SET status = 'active', 
                            payment_deadline = NULL,
                            updated_at = NOW()
                        WHERE agreement_id = ?";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$agreementId]);
            }
        }

        // Try to update existing wallet transaction first
        if (!updateWalletTransactionStatus('payments', $payment_id, 'completed')) {
            // If no existing pending transaction, log a new one
            if ($payment['payment_for'] === 'deposit') {
                logWalletTransaction($payment['tenant_id'], $payment['amount'], 'deposit', 'completed', 'Wallet Deposit (Verified)', 'payments', $payment_id);
            } else {
                // For rent payments, we log it for history but we don't necessarily deduct balance 
                // unless it was a wallet payment. However, the current logic is to log it.
                // To avoid breaking existing flow, we'll keep logging but ensure it doesn't fail.
                logWalletTransaction($payment['tenant_id'], -$payment['amount'], 'payment', 'completed', 'Payment for ' . ($payment['payment_for'] ?: 'rent') . ' - ' . ($payment['title'] ?? 'Property'), 'payments', $payment_id);
            }
        }

        // Create notification for tenant
        $prop_title = $payment['title'] ?? 'Property';
        $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
                VALUES (?, 'Payment Verified', ?, 'success', '../tenant/payments.php')";
        $msg = "Your payment of ETB " . number_format($payment['amount'], 0) . " for " . $prop_title . " has been verified and approved.";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$payment['tenant_id'], $msg]);

        // Send email notification to tenant for payment verification
        $tenantUserSql = "SELECT email, full_name FROM users WHERE user_id = ?";
        $tenantUserStmt = $db->prepare($tenantUserSql);
        $tenantUser = $db->getSingle($tenantUserStmt, [$payment['tenant_id']]);
        
        if ($tenantUser && !empty($tenantUser['email'])) {
            $subject = "Payment Verified - " . SITE_NAME;
            $siteUrl = defined('SITE_URL') ? SITE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/aksum-rental/";
            $currentTenantBalance = getWalletBalance($payment['tenant_id']);
            
            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                    .payment-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
                    .amount { font-size: 24px; font-weight: bold; color: #28a745; }
                    .balance { font-size: 18px; color: #0d6efd; font-weight: bold; text-align: center; margin: 10px 0; padding-top: 10px; border-top: 1px dashed #dee2e6; }
                    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>✅ Payment Verified</h2>
                        <p>Your payment has been successfully verified</p>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>{$tenantUser['full_name']}</strong>,</p>

                        <p>Your payment has been successfully verified and processed. Here are the details:</p>

                        <div class='payment-details'>
                            <h4>Payment Information</h4>
                            <p><strong>Property:</strong> {$prop_title}</p>
                            <p><strong>Payment ID:</strong> #{$payment_id}</p>
                            <p><strong>Amount Paid:</strong> <span class='amount'>ETB " . number_format($payment['amount'], 2) . "</span></p>
                            <p><strong>Payment Method:</strong> " . ucfirst($payment['payment_method']) . "</p>
                            <p><strong>Status:</strong> <span style='color: #28a745; font-weight: bold;'>Verified & Approved</span></p>
                        </div>

                        <div class='balance'>
                            Your Current Wallet Balance: ETB " . number_format($currentTenantBalance, 2) . "
                        </div>

                        <p>The property owner has been notified and the payment has been credited to their wallet.</p>

                        <p>Please log in to your dashboard to view payment history:</p>
                        <a href='" . $siteUrl . "tenant/payments.php' class='btn'>View Payments</a>

                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                        <p style='color: #6c757d; font-size: 12px;'>
                            This is an automated notification from " . SITE_NAME . ". Please do not reply to this email.
                        </p>
                    </div>
                </div>
            </body>
            </html>";

            try {
                sendEmail($tenantUser['email'], $subject, $emailBody);
            } catch (Exception $emailError) {
                error_log("Failed to send payment verified email to tenant {$payment['tenant_id']}: " . $emailError->getMessage());
            }
        }

        // Send email notification for successful deposit
        if ($payment['payment_for'] === 'deposit') {
            try {
                $userSql = "SELECT email, full_name FROM users WHERE user_id = ?";
                $userStmt = $db->prepare($userSql);
                $user = $db->getSingle($userStmt, [$payment['tenant_id']]);
                
                if ($user && !empty($user['email'])) {
                    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Aksum Rental';
                    $subject = "Deposit Successful - " . $siteName;
                    
                    $siteUrl = defined('SITE_URL') ? SITE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/aksum-rental/";
                    
                    // Get current balance after deposit
                    $currentBalance = getWalletBalance($payment['tenant_id']);
                    
                    $emailBody = "<!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                            .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .amount { font-size: 24px; color: #28a745; font-weight: bold; text-align: center; margin: 10px 0; }
                            .balance { font-size: 18px; color: #0d6efd; font-weight: bold; text-align: center; margin: 10px 0 20px 0; padding-top: 10px; border-top: 1px dashed #dee2e6; }
                            .details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                            .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff !important; text-decoration: none; border-radius: 5px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2 style='color: #28a745; margin: 0;'>Deposit Verified & Successful</h2>
                            </div>
                            
                            <p>Dear " . htmlspecialchars($user['full_name']) . ",</p>
                            
                            <p>Your deposit has been successfully verified and added to your wallet balance.</p>
                            
                            <div class='amount'>
                                + ETB " . number_format($payment['amount'], 2) . "
                            </div>
                            
                            <div class='balance'>
                                Your Current Balance: ETB " . number_format($currentBalance, 2) . "
                            </div>
                            
                            <div class='details'>
                                <strong>Payment Details:</strong><br>
                                Method: " . ucfirst($payment['payment_method']) . "<br>
                                Transaction Ref: " . htmlspecialchars($payment['transaction_id']) . "<br>
                                Date: " . date('F j, Y, g:i a') . "
                            </div>
                            
                            <div style='text-align: center;'>
                                <a href='" . $siteUrl . "tenant/payments.php' class='btn'>View Wallet</a>
                            </div>

                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                            <p style='color: #6c757d; font-size: 12px; text-align: center;'>
                                This is an automated notification from " . $siteName . ". Please do not reply to this email.
                            </p>
                        </div>
                    </body>
                    </html>";

                    sendEmail($user['email'], $subject, $emailBody);
                }
            } catch (Exception $emailError) {
                error_log("Failed to send deposit success email to user {$payment['tenant_id']}: " . $emailError->getMessage());
            }
        }

        // Credit Owner Wallet (if it's a rent/advance payment and not a deposit)
        if (in_array($payment['payment_for'], ['rent', 'advance']) && !empty($payment['owner_id'])) {
            $owner_id = $payment['owner_id'];
            $baseRent = (float)($payment['base_rent'] ?? ($payment['amount'] / 1.05)); // Use base_rent if available, else infer from total paid
            $ownerFee = $baseRent * 0.07; // 7% owner fee
            $ownerReceives = $baseRent - $ownerFee;
            $adminCommission = $baseRent * 0.12; // Total commission 12%

            // Conditionally store commission fields if schema supports them
            $hasOwnerReceives = $db->columnExists('payments', 'owner_receives');
            $hasOwnerFee = $db->columnExists('payments', 'owner_fee');
            $hasAdminCommission = $db->columnExists('payments', 'admin_commission');

            if ($hasOwnerReceives || $hasOwnerFee || $hasAdminCommission) {
                $updateFields = [];
                $updateValues = [];
                if ($hasOwnerReceives) {
                    $updateFields[] = 'owner_receives = ?';
                    $updateValues[] = $ownerReceives;
                }
                if ($hasOwnerFee) {
                    $updateFields[] = 'owner_fee = ?';
                    $updateValues[] = $ownerFee;
                }
                if ($hasAdminCommission) {
                    $updateFields[] = 'admin_commission = ?';
                    $updateValues[] = $adminCommission;
                }
                if (!empty($updateFields)) {
                    $updateValues[] = $payment_id;
                    $updateSql = "UPDATE payments SET " . implode(', ', $updateFields) . " WHERE payment_id = ?";
                    $updateStmt = $db->prepare($updateSql);
                    $db->execute($updateStmt, $updateValues);
                }
            }

            $description = "Rent payment received for " . $prop_title . " (Payment ID: " . $payment_id . ") - Amount: ETB " . number_format($ownerReceives, 2);
            logWalletTransaction($owner_id, $ownerReceives, 'deposit', 'completed', $description, 'payments', $payment_id);
            
            // Notify owner
            $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
                    VALUES (?, 'Payment Received', ?, 'success', '../owner/payments.php')";
            $owner_msg = "You have received ETB " . number_format($ownerReceives, 2) . " for " . $prop_title . " (after 7% commission deduction). The amount has been credited to your wallet.";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$owner_id, $owner_msg]);

            // Send email to owner about payment received
            $ownerUserSql = "SELECT email, full_name FROM users WHERE user_id = ?";
            $ownerUserStmt = $db->prepare($ownerUserSql);
            $ownerUser = $db->getSingle($ownerUserStmt, [$owner_id]);
            
            if ($ownerUser && !empty($ownerUser['email'])) {
                $subject = "Payment Received - " . SITE_NAME;
                $siteUrl = defined('SITE_URL') ? SITE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/aksum-rental/";
                $currentOwnerBalance = getWalletBalance($owner_id);
                
                $emailBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                        .payment-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
                        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
                        .balance { font-size: 18px; color: #0d6efd; font-weight: bold; text-align: center; margin: 10px 0; padding-top: 10px; border-top: 1px dashed #dee2e6; }
                        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>💰 Payment Received</h2>
                            <p>A payment has been verified and credited to your wallet</p>
                        </div>
                        <div class='content'>
                            <p>Dear <strong>{$ownerUser['full_name']}</strong>,</p>

                            <p>A payment for your property has been successfully verified. Here are the details:</p>

                            <div class='payment-details'>
                                <h4>Payment Information</h4>
                                <p><strong>Property:</strong> {$prop_title}</p>
                                <p><strong>Payment ID:</strong> #{$payment_id}</p>
                                <p><strong>Base Rent:</strong> ETB " . number_format($baseRent, 2) . "</p>
                                <p><strong>Your Commission (7%):</strong> ETB " . number_format($ownerFee, 2) . "</p>
                                <p><strong>Amount Credited:</strong> <span class='amount'>ETB " . number_format($ownerReceives, 2) . "</span></p>
                            </div>

                            <div class='balance'>
                                Your Current Wallet Balance: ETB " . number_format($currentOwnerBalance, 2) . "
                            </div>

                            <p>Please log in to your dashboard to view payment history:</p>
                            <a href='" . $siteUrl . "owner/payments.php' class='btn'>View Payments</a>

                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                            <p style='color: #6c757d; font-size: 12px;'>
                                This is an automated notification from " . SITE_NAME . ". Please do not reply to this email.
                            </p>
                        </div>
                    </div>
                </body>
                </html>";

                try {
                    sendEmail($ownerUser['email'], $subject, $emailBody);
                } catch (Exception $emailError) {
                    error_log("Failed to send payment received email to owner {$owner_id}: " . $emailError->getMessage());
                }
            }

            // Credit admin wallet with commission
            $adminSql = "SELECT user_id, email FROM users WHERE role = 'admin' LIMIT 1";
            $adminStmt = $db->prepare($adminSql);
            $admin = $db->getSingle($adminStmt, []);
            if ($admin && !empty($admin['user_id'])) {
                $admin_id = $admin['user_id'];
                $admin_description = "Commission earned for payment ID " . $payment_id . " on " . $prop_title;
                $adminTx = logWalletTransaction($admin_id, $adminCommission, 'deposit', 'completed', $admin_description, 'payments', $payment_id);

                if ($adminTx === false) {
                    throw new Exception('Failed to credit admin commission.');
                }

                // Optional notify admin in-app
                $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
                        VALUES (?, 'Commission Earned', ?, 'success', '../admin/payments.php')";
                $stmt = $db->prepare($sql);
                $admin_msg = "You have earned ETB " . number_format($adminCommission, 2) . " commission for payment ID " . $payment_id . ".";
                $db->execute($stmt, [$admin_id, $admin_msg]);

                // Send email to admin about commission
                $subject = "Commission Earned - Payment Verified - " . SITE_NAME;

                $emailBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                        .commission-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
                        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
                        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>💰 Commission Earned</h2>
                            <p>A payment has been verified and commission collected</p>
                        </div>
                        <div class='content'>
                            <p>Dear Admin,</p>

                            <p>A payment has been successfully verified. Here are the commission details:</p>

                            <div class='commission-details'>
                                <h4>Commission Breakdown</h4>
                                <p><strong>Property:</strong> {$prop_title}</p>
                                <p><strong>Payment ID:</strong> #{$payment_id}</p>
                                <p><strong>Base Rent:</strong> ETB " . number_format($baseRent, 2) . "</p>
                                <p><strong>Tenant Fee (5%):</strong> ETB " . number_format($baseRent * 0.05, 2) . "</p>
                                <p><strong>Owner Fee (7%):</strong> ETB " . number_format($ownerFee, 2) . "</p>
                                <p><strong>Total Commission Earned:</strong> <span class='amount'>ETB " . number_format($adminCommission, 2) . "</span></p>
                                <p><strong>Owner Received:</strong> ETB " . number_format($ownerReceives, 2) . "</p>
                            </div>

                            <p>The commission has been recorded in the system. You can view detailed reports in the admin dashboard.</p>

                            <a href='" . SITE_URL . "admin/payments.php' class='btn'>View Payments</a>

                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                            <p style='color: #6c757d; font-size: 12px;'>
                                This is an automated notification from " . SITE_NAME . ". Please do not reply to this email.
                            </p>
                        </div>
                    </div>
                </body>
                </html>";

                try {
                    require_once '../includes/functions.php';
                    sendEmail($admin['email'], $subject, $emailBody);
                } catch (Exception $emailError) {
                    error_log("Failed to send commission email to admin: " . $emailError->getMessage());
                }

                // New logic: Notify all admins about ANY payment/deposit
                try {
                    $allAdminsStmt = $db->prepare("SELECT email, full_name FROM users WHERE role = 'admin' AND is_active = 1");
                    $allAdmins = $db->getMultiple($allAdminsStmt);
                    
                    if (!empty($allAdmins)) {
                        $senderStmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
                        $sender = $db->getSingle($senderStmt, [$payment['tenant_id']]);
                        
                        foreach ($allAdmins as $adminUser) {
                            if (!empty($adminUser['email'])) {
                                $depositSubject = "New Payment Received - " . SITE_NAME;
                                sendEmailTemplate($adminUser['email'], $depositSubject, 'admin_deposit_notif', [
                                    'admin_name' => $adminUser['full_name'],
                                    'sender_name' => $sender['full_name'] ?? 'User',
                                    'amount' => number_format($payment['amount'], 2),
                                    'payment_type' => 'Verified Payment: ' . ($payment['payment_for'] ?: 'Rent'),
                                    'transaction_date' => date('F d, Y H:i:s'),
                                    'site_name' => SITE_NAME
                                ]);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Failed to send admin payment notification: " . $e->getMessage());
                }
            }
        }
        
        // Log activity
        $session->logActivity($approver_id, 'verify_payment', 'payments', $payment_id);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Payment approved successfully']);
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error in verify-payment.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    // Reject payment
    $db->beginTransaction();
    try {
        $sql = "UPDATE payments SET status = 'cancelled', payment_status = 'Cancelled', verified_by = ?, verified_at = NOW(), notes = ?
                WHERE payment_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$approver_id, $notes, $payment_id]);

        // Update wallet transaction status to cancelled
        updateWalletTransactionStatus('payments', $payment_id, 'cancelled');
        if (!empty($notes)) {
            $message .= ' Reason: ' . $notes;
        }
        
        $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
                VALUES (?, 'Payment Rejected', ?, 'warning', '../tenant/payments.php')";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$payment['tenant_id'], $message]);
        
        // Log activity
        $session->logActivity($approver_id, 'reject_payment', 'payments', $payment_id);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Payment rejected successfully']);
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error in verify-payment.php (reject): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
