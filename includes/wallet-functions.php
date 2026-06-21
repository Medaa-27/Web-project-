<?php
/**
 * Wallet functions for Aksum Rental system
 */

/**
 * Get the current balance of a user's wallet
 */
function getWalletBalance($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $result = $db->getSingle($stmt, [$user_id]);
    return $result ? (float)$result['balance'] : 0.00;
}

/**
 * Log a wallet transaction and update the balance
 * 
 * @param int $user_id
 * @param float $amount Positive for deposits, negative for payments/withdrawals
 * @param string $type 'deposit', 'payment', 'withdrawal'
 * @param string $status 'pending', 'completed', 'failed', 'cancelled'
 * @param string $description
 * @param string|null $reference_table
 * @param int|null $reference_id
 * @return int|bool Transaction ID or false on failure
 */
function logWalletTransaction($user_id, $amount, $type, $status, $description, $reference_table = null, $reference_id = null, $fee = 0.00, $net_amount = null) {
    global $db;
    
    $fee = max(0.00, (float)$fee);
    if ($net_amount === null) {
        $net_amount = ($type === 'withdrawal' ? max(0.00, abs($amount) - $fee) : abs($amount));
    } else {
        $net_amount = max(0.00, (float)$net_amount);
    }
    
    // Use the database class transaction handling
    $is_already_in_transaction = $db->inTransaction();
    if (!$is_already_in_transaction) {
        $db->beginTransaction();
    }
    
    try {
        // 1. Get or create wallet
        $stmt = $db->prepare("SELECT wallet_id, balance FROM wallets WHERE user_id = ?");
        $wallet = $db->getSingle($stmt, [$user_id]);
        
        if (!$wallet) {
            $stmt = $db->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
            $db->execute($stmt, [$user_id]);
            $wallet_id = $db->lastInsertId();
            $current_balance = 0.00;
        } else {
            $wallet_id = $wallet['wallet_id'];
            $current_balance = (float)$wallet['balance'];
        }
        
        // 2. Check for sufficient balance if it's a withdrawal or payment and status is completed/pending
        if (($type === 'withdrawal' || $type === 'payment') && $status !== 'failed' && $status !== 'cancelled') {
            if ($current_balance + $amount < 0) {
                if (!$is_already_in_transaction) $db->rollback();
                error_log("Wallet transaction failed for user {$user_id}: insufficient balance ({$current_balance} + {$amount} < 0)");
                return false; // Insufficient balance
            }
        }
        
        // 3. Log transaction
        $hasFeeColumn = $db->columnExists('wallet_transactions', 'fee');
        $hasNetAmountColumn = $db->columnExists('wallet_transactions', 'net_amount');

        if ($hasFeeColumn && $hasNetAmountColumn) {
            $stmt = $db->prepare("INSERT INTO wallet_transactions 
                (wallet_id, amount, transaction_type, status, fee, net_amount, reference_table, reference_id, description, is_visible_admin, is_visible_user) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");
            $db->execute($stmt, [$wallet_id, $amount, $type, $status, $fee, $net_amount, $reference_table, $reference_id, $description]);
        } else {
            $stmt = $db->prepare("INSERT INTO wallet_transactions 
                (wallet_id, amount, transaction_type, status, reference_table, reference_id, description, is_visible_admin, is_visible_user) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)");
            $db->execute($stmt, [$wallet_id, $amount, $type, $status, $reference_table, $reference_id, $description]);
        }
        $transaction_id = $db->lastInsertId();
        
        // 4. Update balance ONLY if status is 'completed' (case-insensitive)
        $status_lower = strtolower($status);
        if ($status_lower === 'completed') {
            $new_balance = $current_balance + $amount;
            $stmt = $db->prepare("UPDATE wallets SET balance = ? WHERE wallet_id = ?");
            $db->execute($stmt, [$new_balance, $wallet_id]);
        }
        
        if (!$is_already_in_transaction) {
            $db->commit();
        }
        
        return $transaction_id;
    } catch (Exception $e) {
        if (!$is_already_in_transaction) {
            $db->rollback();
        }
        error_log("Wallet transaction failed for user $user_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Process a pending withdrawal (complete or cancel)
 */
function processWithdrawalStatus($transaction_id, $new_status, $processor_user_id = null) {
    global $db;
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        $stmt = $db->prepare("SELECT wt.*, w.user_id AS requester_id, u.role AS requester_role
                             FROM wallet_transactions wt
                             JOIN wallets w ON wt.wallet_id = w.wallet_id
                             JOIN users u ON w.user_id = u.user_id
                             WHERE wt.transaction_id = ? AND wt.transaction_type = 'withdrawal' FOR UPDATE");
        $tx = $db->getSingle($stmt, [$transaction_id]);
        
        if (!$tx || $tx['status'] !== 'pending') {
            $conn->rollBack();
            return false;
        }
        
        $amount = abs((float)$tx['amount']);
        $is_admin_withdrawal = strtolower($tx['requester_role']) === 'admin';
        $fee = $is_admin_withdrawal ? 0.00 : ($amount <= 1000 ? round($amount * 0.02, 2) : round($amount * 0.03, 2));
        $net_amount = max(0.00, round($amount - $fee, 2));
        
        if ($new_status === 'completed') {
            $updateSql = "UPDATE wallet_transactions SET status = ?";
            $updateParams = [$new_status];
            $hasFeeColumn = $db->columnExists('wallet_transactions', 'fee');
            $hasNetAmountColumn = $db->columnExists('wallet_transactions', 'net_amount');

            if ($hasFeeColumn) {
                $updateSql .= ", fee = ?";
                $updateParams[] = $fee;
            }
            if ($hasNetAmountColumn) {
                $updateSql .= ", net_amount = ?";
                $updateParams[] = $net_amount;
            }

            $updateSql .= " WHERE transaction_id = ?";
            $updateParams[] = $transaction_id;
            $stmt = $db->prepare($updateSql);
            $db->execute($stmt, $updateParams);
            
            if ($fee > 0 && $processor_user_id) {
                $admin_description = "Withdrawal fee collected for request #{$transaction_id}";
                $adminFeeTxId = logWalletTransaction($processor_user_id, $fee, 'deposit', 'completed', $admin_description, 'wallet_transactions', $transaction_id);
                if (!$adminFeeTxId) {
                    throw new Exception('Failed to credit admin fee');
                }
            }
        } elseif ($new_status === 'cancelled' || $new_status === 'failed') {
            // Add the money back to balance
            $stmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE wallet_id = ?");
            $db->execute($stmt, [abs($tx['amount']), $tx['wallet_id']]);
            
            $stmt = $db->prepare("UPDATE wallet_transactions SET status = ? WHERE transaction_id = ?");
            $db->execute($stmt, [$new_status, $transaction_id]);
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Processing withdrawal failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Update the status of a wallet transaction and update balance accordingly
 */
function updateWalletTransactionStatus($reference_table, $reference_id, $new_status) {
    global $db;
    
    $db->beginTransaction();
    
    try {
        // Get the transaction
        $stmt = $db->prepare("SELECT * FROM wallet_transactions WHERE reference_table = ? AND reference_id = ? AND status = 'pending'");
        $tx = $db->getSingle($stmt, [$reference_table, $reference_id]);
        
        if (!$tx) {
            // If there is already a completed wallet transaction for this payment, treat it as already processed.
            $stmt = $db->prepare("SELECT * FROM wallet_transactions WHERE reference_table = ? AND reference_id = ? AND status = 'completed'");
            $completedTx = $db->getSingle($stmt, [$reference_table, $reference_id]);
            if ($completedTx) {
                $db->commit();
                return true;
            }

            return false;
        }
        
        $old_status = $tx['status'];
        $wallet_id = $tx['wallet_id'];
        $amount = (float)$tx['amount'];
        $type = $tx['transaction_type'];
        
        // Update transaction status
        $stmt = $db->prepare("UPDATE wallet_transactions SET status = ? WHERE transaction_id = ?");
        $db->execute($stmt, [$new_status, $tx['transaction_id']]);
        
        // Update balance if moving to completed
        if ($new_status === 'completed' && $old_status === 'pending') {
            // Withdrawal is already deducted when pending, so don't deduct again
            if ($type !== 'withdrawal') {
                $stmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE wallet_id = ?");
                $db->execute($stmt, [$amount, $wallet_id]);
            }
        } elseif (($new_status === 'failed' || $new_status === 'cancelled') && $old_status === 'pending') {
            // Refund the amount back to balance
            if ($type === 'withdrawal') {
                $stmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE wallet_id = ?");
                $db->execute($stmt, [abs($amount), $wallet_id]);
            } elseif ($type === 'payment') {
                // For cancelled payments, refund the full amount to tenant
                $stmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE wallet_id = ?");
                $db->execute($stmt, [abs($amount), $wallet_id]);
            }
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Update wallet transaction status failed: " . $e->getMessage());
        return false;
    }
}
