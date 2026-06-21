<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';

header('Content-Type: application/json');

// Check if user is admin
if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// CSRF check
$csrf_token = $data['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF token validation failed']);
    exit;
}

$transaction_id = $data['transaction_id'] ?? null;
$new_status = $data['status'] ?? ''; // 'completed', 'cancelled', or 'failed'

if (!$transaction_id || !in_array($new_status, ['completed', 'cancelled', 'failed'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // Process the withdrawal
    $result = processWithdrawalStatus($transaction_id, $new_status, $session->getUserId());
    
    if ($result) {
        // Get user ID for notification
        $stmt = $db->prepare("SELECT w.user_id, wt.amount 
                             FROM wallet_transactions wt 
                             JOIN wallets w ON wt.wallet_id = w.wallet_id 
                             WHERE wt.transaction_id = ?");
        $tx_info = $db->getSingle($stmt, [$transaction_id]);
        
        if ($tx_info) {
            $user_id = $tx_info['user_id'];
            $amount = abs($tx_info['amount']);
            
            // Get user role for link
            $stmt = $db->prepare("SELECT role, email, full_name FROM users WHERE user_id = ?");
            $user_data = $db->getSingle($stmt, [$user_id]);
            $role = $user_data['role'] ?? 'tenant';
            
            $link = '../tenant/payments.php';
            if ($role === 'owner') {
                $link = '../owner/withdrawals.php';
            } elseif ($role === 'admin') {
                $link = '../admin/withdrawals.php';
            }

            if ($new_status === 'completed') {
                $title = 'Withdrawal Approved';
                $message = "Your withdrawal request for " . number_format($amount, 2) . " ETB has been approved and completed.";
                $type = 'success';
            } elseif ($new_status === 'cancelled') {
                $title = 'Withdrawal Cancelled';
                $message = "Your withdrawal request for " . number_format($amount, 2) . " ETB has been cancelled. The amount has been refunded to your wallet.";
                $type = 'warning';
            } else { // failed
                $title = 'Withdrawal Failed';
                $message = "Your withdrawal request for " . number_format($amount, 2) . " ETB has failed. The amount has been refunded to your wallet.";
                $type = 'danger';
            }
            
            // Send notification
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
            $db->execute($stmt, [$user_id, $title, $message, $type, $link]);

            // Send Email Notification
            if (!empty($user_data['email'])) {
                try {
                    require_once '../includes/functions.php';
                    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Aksum Rental';
                    $subject = $title . " - " . $siteName;
                    
                    $siteUrl = defined('SITE_URL') ? SITE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/aksum-rental/";
                    
                    $currentBalance = getWalletBalance($user_id);
                    $color = ($new_status === 'completed') ? '#28a745' : (($new_status === 'cancelled') ? '#ffc107' : '#dc3545');

                    $emailBody = "<!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                            .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .amount { font-size: 24px; color: " . $color . "; font-weight: bold; text-align: center; margin: 10px 0; }
                            .balance { font-size: 18px; color: #0d6efd; font-weight: bold; text-align: center; margin: 10px 0 20px 0; padding-top: 10px; border-top: 1px dashed #dee2e6; }
                            .details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                            .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff !important; text-decoration: none; border-radius: 5px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2 style='color: " . $color . "; margin: 0;'>" . $title . "</h2>
                            </div>
                            
                            <p>Dear " . htmlspecialchars($user_data['full_name']) . ",</p>
                            
                            <p>" . $message . "</p>
                            
                            <div class='amount'>
                                ETB " . number_format($amount, 2) . "
                            </div>
                            
                            <div class='balance'>
                                Your Current Balance: ETB " . number_format($currentBalance, 2) . "
                            </div>
                            
                            <div class='details'>
                                <strong>Transaction Details:</strong><br>
                                Status: " . ucfirst($new_status) . "<br>
                                Date: " . date('F j, Y, g:i a') . "
                            </div>
                            
                            <div style='text-align: center;'>
                                <a href='" . $siteUrl . ltrim($link, '../') . "' class='btn'>View Dashboard</a>
                            </div>

                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                            <p style='color: #6c757d; font-size: 12px; text-align: center;'>
                                This is an automated notification from " . $siteName . ". Please do not reply to this email.
                            </p>
                        </div>
                    </body>
                    </html>";

                    sendEmail($user_data['email'], $subject, $emailBody);
                } catch (Exception $emailError) {
                    error_log("Failed to send withdrawal status email to user {$user_id}: " . $emailError->getMessage());
                }
            }

            // Notify Admin about the fee collected (if any)
            if ($new_status === 'completed') {
                try {
                    // Re-fetch transaction to get the fee
                    $stmt = $db->prepare("SELECT fee FROM wallet_transactions WHERE transaction_id = ?");
                    $tx_fee_data = $db->getSingle($stmt, [$transaction_id]);
                    $collected_fee = (float)($tx_fee_data['fee'] ?? 0);

                    if ($collected_fee > 0) {
                        $adminStmt = $db->prepare("SELECT email, full_name FROM users WHERE user_id = ?");
                        $admin_data = $db->getSingle($adminStmt, [$approver_id]);

                        // Notify admin in-app
                        $notifSql = "INSERT INTO notifications (user_id, title, message, type, link) 
                                     VALUES (?, 'Fee Collected', ?, 'success', '../admin/withdrawals.php')";
                        $adminNotifMsg = "You have collected ETB " . number_format($collected_fee, 2) . " processing fee for withdrawal request #{$transaction_id}.";
                        $notifStmt = $db->prepare($notifSql);
                        $db->execute($notifStmt, [$approver_id, $adminNotifMsg]);

                        if ($admin_data && !empty($admin_data['email'])) {
                            $adminSubject = "Withdrawal Fee Collected - " . $siteName;
                            $adminEmailBody = "
                            <html>
                            <head>
                                <style>
                                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                    .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                                    .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                                    .fee-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
                                    .amount { font-size: 24px; font-weight: bold; color: #28a745; }
                                </style>
                            </head>
                            <body>
                                <div class='container'>
                                    <div class='header'>
                                        <h2>💰 Fee Collected</h2>
                                        <p>A withdrawal fee has been collected and credited to your wallet</p>
                                    </div>
                                    <div class='content'>
                                        <p>Dear Admin,</p>

                                        <p>A withdrawal request has been approved and a processing fee was collected. Here are the details:</p>

                                        <div class='fee-details'>
                                            <h4>Fee Information</h4>
                                            <p><strong>Withdrawal ID:</strong> #{$transaction_id}</p>
                                            <p><strong>User:</strong> {$user_data['full_name']} ({$role})</p>
                                            <p><strong>Withdrawal Amount:</strong> ETB " . number_format($amount, 2) . "</p>
                                            <p><strong>Fee Collected:</strong> <span class='amount'>ETB " . number_format($collected_fee, 2) . "</span></p>
                                        </div>

                                        <p>The fee has been automatically credited to your wallet.</p>

                                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #dee2e6;'>
                                        <p style='color: #6c757d; font-size: 12px;'>
                                            This is an automated notification from {$siteName}. Please do not reply to this email.
                                        </p>
                                    </div>
                                </div>
                            </body>
                            </html>";

                            sendEmail($admin_data['email'], $adminSubject, $adminEmailBody);
                        }
                    }
                } catch (Exception $adminEmailError) {
                    error_log("Failed to send withdrawal fee email to admin: " . $adminEmailError->getMessage());
                }
            }
        }
        
        $status_text = $new_status === 'completed' ? 'approved' : ($new_status === 'cancelled' ? 'cancelled' : 'marked as failed');
        echo json_encode([
            'success' => true, 
            'message' => 'Withdrawal ' . $status_text . ' successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to process withdrawal. It may have already been processed or not found.']);
    }
} catch (Exception $e) {
    error_log("Admin processing withdrawal failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An internal server error occurred']);
}
