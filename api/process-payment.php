<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

// Ensure API returns clean JSON even if PHP notices/warnings occur
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $prepare = function($sql) use ($db) {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            $error = $db->getLastError();
            error_log("Payment Prepare Error: " . ($error ?: "Unknown PDO error") . " | SQL: " . $sql);
            throw new Exception('An error occurred while preparing the payment request. Please try again.');
        }
        return $stmt;
    };
    if (!$session->isLoggedIn()) {
        http_response_code(401);
        throw new Exception('User not authenticated');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }

    // CSRF check
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('CSRF token validation failed');
    }

    $tenantId = $session->getUserId();

    $agreementId    = $_POST['agreement_id'] ?? '';
    $propertyId     = $_POST['property_id'] ?? null;
    $paymentType    = $_POST['payment_type'] ?? '';
    $paymentMethod  = $_POST['payment_method'] ?? '';
    $transactionId  = trim($_POST['transaction_id'] ?? '');
    $paymentDate    = $_POST['payment_date'] ?? date('Y-m-d');
    $notes          = trim($_POST['notes'] ?? '');

    if (!$agreementId || !$paymentType || !$paymentMethod) {
        throw new Exception('Missing required fields');
    }

    // Validation for wallet payments
    if ($paymentMethod === 'wallet') {
        $walletBalance = getWalletBalance($tenantId);
    } else if ($paymentMethod !== 'virtual_bank' && empty($transactionId)) {
        throw new Exception('Transaction ID is required for manual methods');
    }

    $sql = "SELECT ra.*, p.monthly_rent, p.security_deposit, p.property_id
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            WHERE ra.agreement_id = ? AND ra.tenant_id = ? AND ra.status IN ('active','partially_paid')";
    $stmt = $prepare($sql);
    $agreement = $db->getSingle($stmt, [$agreementId, $tenantId]);

    if (!$agreement) {
        throw new Exception('Rental agreement not found or access denied');
    }

    $monthlyRent = floatval($agreement['monthly_rent']);
    $baseRent = $monthlyRent; // Store base rent before commissions

    if ($paymentType === 'MINIMUM') {
        // Double check if this is truly the first payment
        $countSql = "SELECT COUNT(*) as count FROM payments WHERE agreement_id = ? AND payment_for = 'rent' AND status = 'completed'";
        $countStmt = $prepare($countSql);
        $res = $db->getSingle($countStmt, [$agreementId]);
        if ($res && $res['count'] > 0) {
            throw new Exception('The Minimum Payment (20%) option is only available for the first month of rent.');
        }
    }

    switch ($paymentType) {
        case 'FULL':
            $baseRent = $monthlyRent * 6;
            $totalAmount = $monthlyRent * 6;
            $amountPaid = $totalAmount;
            $balanceRemaining = 0;
            break;
        case 'MINIMUM':
            $reservationFee = $monthlyRent * 0.2;
            $baseRent = $reservationFee; // Minimum payment is 20% reservation fee
            $totalAmount = $monthlyRent; // Change to monthly rent instead of 6 months
            $amountPaid = $baseRent;
            $balanceRemaining = $totalAmount - $amountPaid; // e.g. 3000 - 600 = 2400
            break;
        case 'MONTHLY':
        default:
            $baseRent = $monthlyRent;
            $totalAmount = $monthlyRent;
            $amountPaid = $monthlyRent;
            $balanceRemaining = 0;
            $paymentType = 'MONTHLY';
            break;
    }

    // Apply tenant fee (5%)
    $tenantFee = $amountPaid * 0.05;
    $tenantTotalPaid = $amountPaid + $tenantFee;

    // Check for duplicate payments (same agreement, same payment type, same amount, within last 24 hours)
    $duplicateCheckSql = "SELECT payment_id FROM payments WHERE agreement_id = ? AND payment_type = ? AND amount = ? AND payment_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND status IN ('pending', 'completed')";
    $duplicateCheckStmt = $prepare($duplicateCheckSql);
    $existingPayment = $db->getSingle($duplicateCheckStmt, [$agreementId, $paymentType, $tenantTotalPaid]);

    if ($existingPayment) {
        $message = 'A similar payment already exists for this agreement within the last 24 hours. Please check your payment history.';
        error_log($message);
        throw new Exception($message);
    }

    $db->beginTransaction();

    // Check balance for wallet payments using total payable amount
    if ($paymentMethod === 'wallet' && $walletBalance < $tenantTotalPaid) {
        $message = 'Insufficient wallet balance. Available: ETB ' . number_format($walletBalance, 2) . ', required: ETB ' . number_format($tenantTotalPaid, 2);
        error_log($message);
        throw new Exception($message);
    }

    $paymentStatus = ($paymentMethod === 'wallet') ? 'Verified' : 'Pending';
    $status = ($paymentMethod === 'wallet') ? 'completed' : 'pending';

    $hasBaseRent = $db->columnExists('payments', 'base_rent');
    $hasTenantFee = $db->columnExists('payments', 'tenant_fee');

    $columns = [
        'agreement_id',
        'tenant_id',
        'property_id',
        'amount'
    ];
    $values = [
        $agreementId,
        $tenantId,
        $propertyId ?: $agreement['property_id'],
        $tenantTotalPaid
    ];

    if ($hasBaseRent) {
        $columns[] = 'base_rent';
        $values[] = $amountPaid;
    }
    if ($hasTenantFee) {
        $columns[] = 'tenant_fee';
        $values[] = $tenantFee;
    }

    $columns = array_merge($columns, [
        'total_amount',
        'amount_paid',
        'balance_remaining',
        'payment_type',
        'payment_for',
        'payment_date',
        'payment_method',
        'transaction_id',
        'status',
        'payment_status',
        'notes',
        'created_at'
    ]);
    $values = array_merge($values, [
        $totalAmount,
        $amountPaid,
        $balanceRemaining,
        $paymentType,
        'rent',
        $paymentDate,
        $paymentMethod,
        $transactionId,
        $status,
        $paymentStatus,
        $notes
    ]);

    $placeholders = implode(', ', array_fill(0, count($values), '?')) . ', NOW()';
    $insertColumns = implode(', ', $columns);

    $sql = "INSERT INTO payments ($insertColumns) VALUES ($placeholders)";
    $stmt = $prepare($sql);
    $db->execute($stmt, $values);

    $paymentId = $db->getLastInsertId();

    // Logic for handling rental agreement status based on payment type (similar to verify-payment.php)
    // This is needed for wallet payments which are auto-verified
    if ($paymentMethod === 'wallet') {
        if ($paymentType === 'MINIMUM') {
            // Minimum payment (20%) - set to partially_paid and set deadline
            $deadline = date('Y-m-d H:i:s', strtotime('+10 days'));
            $sql = "UPDATE rental_agreements 
                    SET status = 'partially_paid', 
                        payment_deadline = ?,
                        updated_at = NOW()
                    WHERE agreement_id = ?";
            $stmt = $prepare($sql);
            $db->execute($stmt, [$deadline, $agreementId]);
        } else if ($paymentType === 'FULL' || $paymentType === 'MONTHLY' || $balanceRemaining <= 0) {
            // Full payment or balance cleared - set to active
            $sql = "UPDATE rental_agreements 
                    SET status = 'active', 
                        payment_deadline = NULL,
                        updated_at = NOW()
                    WHERE agreement_id = ?";
            $stmt = $prepare($sql);
            $db->execute($stmt, [$agreementId]);
        }
    }

    // Log in wallet system - Set status to 'completed' for wallet payments
    $walletStatus = ($paymentMethod === 'wallet') ? 'completed' : 'pending';
    $logResult = logWalletTransaction($tenantId, -$tenantTotalPaid, 'payment', $walletStatus, "Payment for " . ($paymentType === 'FULL' ? '6 Months Rent' : 'Rent') . " - Agreement #$agreementId (including 5% fee)", 'payments', $paymentId);

    if ($paymentMethod === 'wallet' && $logResult === false) {
        throw new Exception('Failed to process wallet transaction');
    }

    // For wallet payments, immediately credit owner and admin commission
    if ($paymentMethod === 'wallet') {
        // Calculate commission amounts
        $ownerFee = $amountPaid * 0.07; // 7% owner fee
        $ownerReceives = $amountPaid - $ownerFee;
        $adminCommission = $amountPaid * 0.12; // Total commission 12%

        // Get owner ID
        $propertyIdUsed = $propertyId ?: $agreement['property_id'];
        $ownerSql = "SELECT owner_id FROM properties WHERE property_id = ?";
        $ownerStmt = $prepare($ownerSql);
        $owner = $db->getSingle($ownerStmt, [$propertyIdUsed]);
        
        if ($owner && isset($owner['owner_id'])) {
            $ownerId = $owner['owner_id'];
            
            // Credit owner wallet
            $description = "Rent payment received for Agreement #$agreementId - Amount: ETB " . number_format($ownerReceives, 2);
            logWalletTransaction($ownerId, $ownerReceives, 'deposit', 'completed', $description, 'payments', $paymentId);
            
            // Credit admin wallet with commission
            $adminSql = "SELECT user_id, email FROM users WHERE role = 'admin' LIMIT 1";
            $adminStmt = $prepare($adminSql);
            $admin = $db->getSingle($adminStmt, []);
            if ($admin && !empty($admin['user_id'])) {
                $adminId = $admin['user_id'];
                $adminDescription = "Commission earned for payment ID $paymentId on Agreement #$agreementId";
                logWalletTransaction($adminId, $adminCommission, 'deposit', 'completed', $adminDescription, 'payments', $paymentId);

                // Notify admin in-app
                $notifSql = "INSERT INTO notifications (user_id, title, message, type, link) 
                             VALUES (?, 'Commission Earned', ?, 'success', '../admin/payments.php')";
                $adminNotifMsg = "You have earned ETB " . number_format($adminCommission, 2) . " commission for wallet payment ID #{$paymentId}.";
                $notifStmt = $prepare($notifSql);
                $db->execute($notifStmt, [$adminId, $adminNotifMsg]);

                // Send email to admin about commission
                if (!empty($admin['email'])) {
                    $subject = "Commission Earned - Wallet Payment - " . SITE_NAME;
                    
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
                                <p>A wallet payment has been processed and commission collected</p>
                            </div>
                            <div class='content'>
                                <p>Dear Admin,</p>

                                <p>A wallet payment has been successfully processed. Here are the commission details:</p>

                                <div class='commission-details'>
                                    <h4>Commission Breakdown</h4>
                                    <p><strong>Property:</strong> {$propertyTitle}</p>
                                    <p><strong>Payment ID:</strong> #{$paymentId}</p>
                                    <p><strong>Base Rent:</strong> ETB " . number_format($amountPaid, 2) . "</p>
                                    <p><strong>Tenant Fee (5%):</strong> ETB " . number_format($tenantFee, 2) . "</p>
                                    <p><strong>Owner Fee (7%):</strong> ETB " . number_format($ownerFee, 2) . "</p>
                                    <p><strong>Total Commission Earned:</strong> <span class='amount'>ETB " . number_format($adminCommission, 2) . "</span></p>
                                    <p><strong>Owner Received:</strong> ETB " . number_format($ownerReceives, 2) . "</p>
                                </div>

                                <p>The commission has been automatically credited to your wallet. You can view detailed reports in the admin dashboard.</p>

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
                        error_log("Failed to send wallet commission email to admin: " . $emailError->getMessage());
                    }
                }
            }
        }
    }

    // Notify property owner about new payment submission
    $propertyIdUsed = $propertyId ?: $agreement['property_id'];
    $ownerSql = "SELECT owner_id FROM properties WHERE property_id = ?";
    $ownerStmt = $prepare($ownerSql);
    $owner = $db->getSingle($ownerStmt, [$propertyIdUsed]);

    if ($owner && isset($owner['owner_id'])) {
        $ownerId = $owner['owner_id'];

        // Get owner details for email
        $ownerDetailsSql = "SELECT full_name, email FROM users WHERE user_id = ?";
        $ownerDetailsStmt = $prepare($ownerDetailsSql);
        $ownerDetails = $db->getSingle($ownerDetailsStmt, [$ownerId]);

        // Email notification to tenant
        $tenantDetailsSql = "SELECT full_name, email FROM users WHERE user_id = ?";
        $tenantDetailsStmt = $prepare($tenantDetailsSql);
        $tenantDetails = $db->getSingle($tenantDetailsStmt, [$tenantId]);

        // Get property details for the email before building the message
        $propertyDetailsSql = "SELECT title FROM properties WHERE property_id = ?";
        $propertyDetailsStmt = $prepare($propertyDetailsSql);
        $propertyDetails = $db->getSingle($propertyDetailsStmt, [$propertyIdUsed]);
        $propertyTitle = $propertyDetails['title'] ?? 'Property';

        if ($tenantDetails && $tenantDetails['email']) {
            $subject = "Payment Submitted Successfully - " . SITE_NAME;

            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #708090 0%, #4a5568 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                    .payment-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #708090; }
                    .amount { font-size: 24px; font-weight: bold; color: #28a745; }
                    .commission { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
                    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>💳 Payment Submitted</h2>
                        <p>Your payment has been successfully submitted</p>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>{$tenantDetails['full_name']}</strong>,</p>

                        <p>Your payment has been submitted successfully. Here are the details:</p>

                        <div class='payment-details'>
                            <h4>Payment Breakdown</h4>
                            <p><strong>Property:</strong> {$propertyTitle}</p>
                            <p><strong>Agreement ID:</strong> #{$agreementId}</p>
                            <p><strong>Payment Type:</strong> {$paymentType}</p>
                            <p><strong>Payment Method:</strong> " . ucfirst($paymentMethod) . "</p>
                            <p><strong>Base Rent:</strong> ETB " . number_format($amountPaid, 2) . "</p>
                            <p><strong>Processing Fee (5%):</strong> ETB " . number_format($tenantFee, 2) . "</p>
                            <p><strong>Total Paid:</strong> <span class='amount'>ETB " . number_format($tenantTotalPaid, 2) . "</span></p>
                            <p><strong>Transaction ID:</strong> " . ($transactionId ?: 'N/A') . "</p>
                            <p><strong>Payment Date:</strong> {$paymentDate}</p>
                            " . ($notes ? "<p><strong>Notes:</strong> {$notes}</p>" : "") . "
                        </div>

                        <div class='commission'>
                            <strong>Note:</strong> A 5% processing fee has been added to your payment. The property owner will receive the base rent amount minus their 7% commission. Our admin earns the total commission to maintain the platform.
                        </div>

                        <div class='payment-details'>
                            <h4>Wallet Balance Update</h4>
                            <p><strong>Amount Deducted:</strong> ETB " . number_format($tenantTotalPaid, 2) . "</p>
                            <p><strong>Current Wallet Balance:</strong> <span class='amount'>ETB " . number_format(getWalletBalance($tenantId), 2) . "</span></p>
                        </div>

                        <p><strong>Status:</strong> <span style='color: " . ($paymentMethod === 'wallet' ? '#28a745' : '#ffc107') . ";'>" . ($paymentMethod === 'wallet' ? 'Verified (Paid)' : 'Pending Verification') . "</span></p>
                        " . ($paymentMethod === 'wallet' ? "<p>Your payment has been automatically verified as it was paid using your wallet.</p>" : "<p>Your payment is currently pending verification by our team. You will receive another notification once the payment is verified.</p>") . "

                        <p>Please log in to your dashboard to view payment history:</p>
                        <a href='" . SITE_URL . "tenant/payments.php' class='btn'>View Payments</a>

                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                        <p style='color: #6c757d; font-size: 12px;'>
                            This is an automated notification from " . SITE_NAME . ". Please do not reply to this email.
                        </p>
                    </div>
                </div>
            </body>
            </html>";

            // Send email (don't fail the payment if email fails)
            try {
                require_once '../includes/functions.php';
                sendEmail($tenantDetails['email'], $subject, $emailBody);
            } catch (Exception $emailError) {
                // Log email error but don't fail payment
                error_log("Failed to send payment confirmation email to tenant {$tenantId}: " . $emailError->getMessage());
            }
        }

        // Get property details
        $propertyDetailsSql = "SELECT title FROM properties WHERE property_id = ?";
        $propertyDetailsStmt = $prepare($propertyDetailsSql);
        $propertyDetails = $db->getSingle($propertyDetailsStmt, [$propertyIdUsed]);

        // In-app notification
        $notificationSql = "INSERT INTO notifications (user_id, title, message, type, link, created_at) VALUES (?, ?, ?, 'info', ?, NOW())";
        $notificationStmt = $prepare($notificationSql);

        $ownerMessage = "A new payment of ETB " . number_format($tenantTotalPaid, 2) . " was submitted for agreement #{$agreementId} (Base rent: ETB " . number_format($amountPaid, 2) . ", you will receive ETB " . number_format($amountPaid * 0.93, 2) . " after 7% commission).";
        if ($paymentMethod === 'wallet') {
            $ownerMessage = "A new payment of ETB " . number_format($tenantTotalPaid, 2) . " has been paid via wallet for agreement #{$agreementId} (Base rent: ETB " . number_format($amountPaid, 2) . ", you will receive ETB " . number_format($amountPaid * 0.93, 2) . " after 7% commission).";
        }
        $db->execute($notificationStmt, [
            $ownerId,
            $paymentMethod === 'wallet' ? 'New payment received (Paid)' : 'New payment received',
            $ownerMessage,
            '../owner/payments.php'
        ]);

        // Email notification
        if ($ownerDetails && $ownerDetails['email'] && $tenantDetails && $propertyDetails) {
            $subject = "New Payment Submitted - " . SITE_NAME;

            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #708090 0%, #4a5568 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                    .payment-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #708090; }
                    .amount { font-size: 24px; font-weight: bold; color: #28a745; }
                    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>💰 New Payment Received</h2>
                        <p>A tenant has submitted a payment for your property</p>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>{$ownerDetails['full_name']}</strong>,</p>

                        <p>A new payment has been submitted for your property. Here are the details:</p>

                        <div class='payment-details'>
                            <h4>Payment Information</h4>
                            <p><strong>Property:</strong> {$propertyDetails['title']}</p>
                            <p><strong>Tenant:</strong> {$tenantDetails['full_name']}</p>
                            <p><strong>Agreement ID:</strong> #{$agreementId}</p>
                            <p><strong>Payment Type:</strong> {$paymentType}</p>
                            <p><strong>Payment Method:</strong> " . ucfirst($paymentMethod) . "</p>
                            <p><strong>Base Rent:</strong> ETB " . number_format($amountPaid, 2) . "</p>
                            <p><strong>Tenant Fee (5%):</strong> ETB " . number_format($tenantFee, 2) . "</p>
                            <p><strong>Total Tenant Paid:</strong> <span class='amount'>ETB " . number_format($tenantTotalPaid, 2) . "</span></p>
                            <p><strong>Your Commission (7%):</strong> ETB " . number_format($amountPaid * 0.07, 2) . "</p>
                            <p><strong>You Will Receive:</strong> ETB " . number_format($amountPaid * 0.93, 2) . "</p>
                            <p><strong>Transaction ID:</strong> " . ($transactionId ?: 'N/A') . "</p>
                            <p><strong>Payment Date:</strong> {$paymentDate}</p>
                            " . ($notes ? "<p><strong>Notes:</strong> {$notes}</p>" : "") . "
                        </div>

                        <p><strong>Status:</strong> <span style='color: " . ($paymentMethod === 'wallet' ? '#28a745' : '#ffc107') . ";'>" . ($paymentMethod === 'wallet' ? 'Verified (Paid)' : 'Pending Verification') . "</span></p>
                        " . ($paymentMethod === 'wallet' ? "<p>The payment has been automatically verified as it was paid using the tenant's wallet.</p>
                        <div class='payment-details'>
                            <h4>Wallet Update</h4>
                            <p><strong>Amount Credited:</strong> ETB " . number_format($amountPaid * 0.95, 2) . "</p>
                            <p><strong>Current Wallet Balance:</strong> <span class='amount'>ETB " . number_format(getWalletBalance($ownerId), 2) . "</span></p>
                        </div>" : "<p>The payment is currently pending verification by our team. You will receive another notification once the payment is verified.</p>") . "

                        <p>Please log in to your dashboard to review and verify this payment:</p>
                        <a href='" . SITE_URL . "owner/payments.php' class='btn'>View Payment Details</a>

                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                        <p style='color: #6c757d; font-size: 12px;'>
                            This is an automated notification from " . SITE_NAME . ". Please do not reply to this email.
                        </p>
                    </div>
                </div>
            </body>
            </html>";

            // Send email (don't fail the payment if email fails)
            try {
                require_once '../includes/functions.php';
                sendEmail($ownerDetails['email'], $subject, $emailBody);
            } catch (Exception $emailError) {
                // Log email error but don't fail payment
                error_log("Failed to send payment notification email to owner {$ownerId}: " . $emailError->getMessage());
            }
        }
    }

    // New logic: Notify all admins about ANY wallet payment
    if ($paymentMethod === 'wallet') {
        try {
            $allAdminsStmt = $db->prepare("SELECT email, full_name FROM users WHERE role = 'admin' AND is_active = 1");
            $allAdmins = $db->getMultiple($allAdminsStmt);
            
            if (!empty($allAdmins)) {
                $senderStmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
                $sender = $db->getSingle($senderStmt, [$tenantId]);
                
                foreach ($allAdmins as $adminUser) {
                    if (!empty($adminUser['email'])) {
                        $depositSubject = "New Wallet Payment Received - " . SITE_NAME;
                        sendEmailTemplate($adminUser['email'], $depositSubject, 'admin_deposit_notif', [
                            'admin_name' => $adminUser['full_name'],
                            'sender_name' => $sender['full_name'] ?? 'User',
                            'amount' => number_format($amountPaid, 2),
                            'payment_type' => 'Wallet Payment: ' . $paymentType,
                            'transaction_date' => date('F d, Y H:i:s'),
                            'site_name' => SITE_NAME
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to send admin wallet payment notification: " . $e->getMessage());
        }
    }

    $db->commit();

    // Clear any buffered output to ensure a clean JSON response
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment submitted successfully',
        'payment_id' => $paymentId,
        'amount' => $amountPaid,
        'payment_type' => $paymentType
    ]);
} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $ignored) {}
    }
    $debug = trim(ob_get_clean());
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
    ];
    if (!empty($debug)) {
        $response['debug'] = $debug;
    }
    echo json_encode($response);
} catch (Throwable $e) {
    // Catch fatal errors or PHP 7+ Throwables not caught by Exception
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $ignored) {}
    }
    $debug = trim(ob_get_clean());
    $response = [
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'type' => get_class($e)
    ];
    if (!empty($debug)) {
        $response['debug'] = $debug;
    }
    echo json_encode($response);
}

?>
