<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

// Ensure errors are displayed for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$reference = $_GET['reference'] ?? null;
$transactionId = $_GET['transaction_id'] ?? null;
$status = $_GET['status'] ?? null;

if (!$reference || !$status) {
    die("Invalid callback parameters.");
}

try {
    $db->beginTransaction();

    // Find the pending payment
    $sql = "SELECT * FROM payments WHERE transaction_id = ? AND payment_for = 'deposit' AND status = 'pending' LIMIT 1";
    $stmt = $db->prepare($sql);
    $payment = $db->getSingle($stmt, [$reference]);

    if (!$payment) {
        throw new Exception("Payment not found or already processed.");
    }

    $paymentId = $payment['payment_id'];
    $tenantId = $payment['tenant_id'];
    $amount = floatval($payment['amount']);
    $depositMethod = $payment['payment_method'];

    if ($status === 'completed') {
        // Update payment record to completed
        $updateSql = "UPDATE payments SET status = 'completed', payment_status = 'Completed', transaction_id = ? WHERE payment_id = ?";
        $updateStmt = $db->prepare($updateSql);
        $db->execute($updateStmt, [$transactionId, $paymentId]);

        // Log in wallet system
        $walletTxId = logWalletTransaction($tenantId, $amount, 'deposit', 'completed', " Wallet Deposit via " . ucfirst($depositMethod), 'payments', $paymentId);
        
        if (!$walletTxId) {
            throw new Exception('Failed to update wallet balance.');
        }

        // Create notification
        createNotification($tenantId, 'Deposit Successful', "Your deposit of ETB " . number_format($amount, 2) . " has been completed successfully.", 'success', '../tenant/payments.php', 15);

        // Send email notification to user
        try {
            require_once '../includes/functions.php';
            $userSql = "SELECT email, full_name FROM users WHERE user_id = ?";
            $userStmt = $db->prepare($userSql);
            $user = $db->getSingle($userStmt, [$tenantId]);
            
            if ($user && !empty($user['email'])) {
                $siteName = defined('SITE_NAME') ? SITE_NAME : 'Aksum Rental';
                $subject = "Deposit Successful - " . $siteName;
                
                $siteUrl = defined('SITE_URL') ? SITE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/aksum-rental/";
                
                // Get current balance after deposit
                $currentBalance = getWalletBalance($tenantId);
                
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
                            <h2 style='color: #28a745; margin: 0;'>Deposit Successful</h2>
                        </div>
                        
                        <p>Dear " . htmlspecialchars($user['full_name']) . ",</p>
                        
                        <p>Your deposit has been successfully processed and added to your wallet balance.</p>
                        
                        <div class='amount'>
                            + ETB " . number_format($amount, 2) . "
                        </div>
                        
                        <div class='details'>
                            <p><strong>Transaction ID:</strong> " . htmlspecialchars($transactionId) . "</p>
                            <p><strong>Method:</strong> " . ucfirst($depositMethod) . "</p>
                            <p><strong>Date:</strong> " . date('F d, Y H:i:s') . "</p>
                        </div>
                        
                        <div class='balance'>
                            New Balance: ETB " . number_format($currentBalance, 2) . "
                        </div>
                        
                        <div style='text-align: center;'>
                            <a href='{$siteUrl}tenant/payments.php' class='btn'>View Payment History</a>
                        </div>
                        
                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                        <p style='color: #6c757d; font-size: 12px; text-align: center;'>
                            This is an automated notification from " . (defined('SITE_NAME') ? SITE_NAME : 'Aksum Rental') . ". Please do not reply to this email.
                        </p>
                    </div>
                </body>
                </html>";

                sendEmail($user['email'], $subject, $emailBody);
            }

            // Notify Admin about the deposit
            $adminStmt = $db->prepare("SELECT email, full_name FROM users WHERE role = 'admin' AND is_active = 1");
            $admins = $db->getMultiple($adminStmt);
            
            if (!empty($admins)) {
                foreach ($admins as $admin) {
                    if (!empty($admin['email'])) {
                        $adminSubject = "Admin Wallet Deposit Received - " . (defined('SITE_NAME') ? SITE_NAME : 'Aksum Rental');
                        sendEmailTemplate($admin['email'], $adminSubject, 'admin_deposit_notif', [
                            'admin_name' => $admin['full_name'],
                            'sender_name' => $user['full_name'] ?? 'User',
                            'amount' => number_format($amount, 2),
                            'payment_type' => 'Wallet Deposit',
                            'transaction_date' => date('F d, Y H:i:s'),
                            'site_name' => defined('SITE_NAME') ? SITE_NAME : 'Aksum Rental'
                        ]);
                    }
                }
            }
        } catch (Exception $emailError) {
            error_log("Failed to send deposit notifications: " . $emailError->getMessage());
        }

        $db->commit();
        
        // Redirect to payments page with success message
        header("Location: ../tenant/payments.php?deposit=success");
        exit;
    } else {
        // Update payment record to failed
        $updateSql = "UPDATE payments SET status = 'failed', payment_status = 'Failed' WHERE payment_id = ?";
        $updateStmt = $db->prepare($updateSql);
        $db->execute($updateStmt, [$paymentId]);

        // Create notification
        createNotification($tenantId, 'Deposit Failed', "Your deposit of ETB " . number_format($amount, 2) . " failed.", 'danger', '../tenant/payments.php', 15);

        $db->commit();
        
        // Redirect to payments page with error message
        header("Location: ../tenant/payments.php?deposit=failed");
        exit;
    }

} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $ignored) {}
    }
    die("Error processing payment: " . htmlspecialchars($e->getMessage()));
}
