<?php
require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/wallet-functions.php';

header('Content-Type: application/json');

if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$bank_name = isset($_POST['bank_name']) ? sanitizeInput($_POST['bank_name']) : '';
$account_number = isset($_POST['account_number']) ? sanitizeInput($_POST['account_number']) : '';
$account_password = isset($_POST['account_password']) ? $_POST['account_password'] : '';

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

if (empty($bank_name) || empty($account_number)) {
    echo json_encode(['success' => false, 'message' => 'Bank name and account number are required']);
    exit;
}

// Bank-specific validation
$account_number = preg_replace('/[^0-9]/', '', $account_number);
switch ($bank_name) {
    case 'CBE':
        if (strlen($account_number) !== 13) {
            echo json_encode(['success' => false, 'message' => 'CBE account number must be exactly 13 digits']);
            exit;
        }
        break;
    case 'Telebirr':
        if (!preg_match('/^09[0-9]{8}$/', $account_number)) {
            echo json_encode(['success' => false, 'message' => 'Telebirr number must be 10 digits starting with 09']);
            exit;
        }
        break;
    case 'M-PESA':
        if (!preg_match('/^07[0-9]{8}$/', $account_number)) {
            echo json_encode(['success' => false, 'message' => 'M-PESA number must be 10 digits starting with 07']);
            exit;
        }
        break;
    case 'Dashen':
        if (strlen($account_number) < 10 || strlen($account_number) > 13) {
            echo json_encode(['success' => false, 'message' => 'Dashen Bank account should be 10-13 digits']);
            exit;
        }
        break;
    case 'Abyssinia':
        if (strlen($account_number) < 13 || strlen($account_number) > 15) {
            echo json_encode(['success' => false, 'message' => 'Bank of Abyssinia account should be 13-15 digits']);
            exit;
        }
        break;
    case 'Wegagen':
        if (strlen($account_number) < 12 || strlen($account_number) > 15) {
            echo json_encode(['success' => false, 'message' => 'Wegagen Bank account should be 12-15 digits']);
            exit;
        }
        break;
}

if (empty($account_password)) {
    echo json_encode(['success' => false, 'message' => 'Account password is required to authorize withdrawal']);
    exit;
}

// Verify system password
$stmt = $db->prepare("SELECT password_hash, full_name, email FROM users WHERE user_id = ?");
$user = $db->getSingle($stmt, [$user_id]);

if (!$user || !password_verify($account_password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid account password. Authorization failed.']);
    exit;
}

$current_balance = getWalletBalance($user_id);

$is_admin_user = strtolower($user['role'] ?? '') === 'admin';
$fee = $is_admin_user ? 0.00 : ($amount <= 1000 ? round($amount * 0.02, 2) : round($amount * 0.03, 2));
$total_deduction = $amount + $fee;

if ($total_deduction > $current_balance) {
    echo json_encode(['success' => false, 'message' => "Insufficient wallet balance. You need ETB " . number_format($total_deduction, 2) . " (including ETB " . number_format($fee, 2) . " fee) to withdraw ETB " . number_format($amount, 2)]);
    exit;
}

$description = "Withdrawal to $bank_name ($account_number)";
$transaction_id = logWalletTransaction($user_id, -$total_deduction, 'withdrawal', 'completed', $description, null, null, $fee, $amount);

if ($transaction_id) {
    // Transfer fee to admin wallet if applicable
    if ($fee > 0) {
        try {
            // Find an admin to receive the fee
            $adminStmt = $db->prepare("SELECT user_id FROM users WHERE role = 'admin' AND is_active = 1 LIMIT 1");
            $admin = $db->getSingle($adminStmt);
            if ($admin) {
                $admin_id = $admin['user_id'];
                $fee_desc = "Withdrawal fee from user #$user_id (Request #$transaction_id)";
                logWalletTransaction($admin_id, $fee, 'deposit', 'completed', $fee_desc, 'wallet_transactions', $transaction_id);
            }
        } catch (Exception $e) {
            error_log("Failed to transfer withdrawal fee to admin: " . $e->getMessage());
        }
    }

    // Add internal notification
    try {
        $notif_title = "Withdrawal Successful";
        $notif_message = "Your withdrawal of ETB " . number_format($amount, 2) . " via $bank_name has been processed. Total deducted: ETB " . number_format($total_deduction, 2) . " (includes ETB " . number_format($fee, 2) . " fee).";
        $stmt_notif = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'payment')");
        $db->execute($stmt_notif, [$user_id, $notif_title, $notif_message]);
    } catch (Exception $e) {
        error_log("Failed to create withdrawal notification: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true, 
        'message' => "Withdrawal successful! ETB " . number_format($total_deduction, 2) . " has been deducted from your wallet (ETB " . number_format($amount, 2) . " + ETB " . number_format($fee, 2) . " fee).",
        'new_balance' => getWalletBalance($user_id)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Withdrawal failed. Please check your balance and try again.']);
}
