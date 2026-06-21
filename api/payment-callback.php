<?php
/**
 * Payment Callback Handler
 * Handles payment verification callbacks from virtual banking gateway
 */

require_once '../includes/config.php';

header('Content-Type: application/json');

class PaymentCallbackHandler {
    private $db;
    private $testMode = true;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Handle payment callback from gateway
     */
    public function handleCallback() {
        try {
            // Get callback data
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid callback data');
            }
            
            // Log raw callback for debugging
            $this->logCallback('received', $input);
            
            // Verify callback signature (for production)
            if (!$this->testMode && !$this->verifySignature($input)) {
                throw new Exception('Invalid callback signature');
            }
            
            // Extract transaction reference
            $transactionReference = $input['transaction_reference'] ?? null;
            if (!$transactionReference) {
                throw new Exception('Missing transaction reference');
            }
            
            // Find transaction
            $transaction = $this->findTransaction($transactionReference);
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Process payment result
            $result = $this->processPaymentResult($transaction, $input);
            
            // Send response to gateway
            $this->sendGatewayResponse($result);
            
            return $result;
            
        } catch (Exception $e) {
            $this->logCallback('error', ['error' => $e->getMessage()]);
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Process payment result from callback
     */
    private function processPaymentResult($transaction, $callbackData) {
        $paymentStatus = $callbackData['status'] ?? 'failed';
        $gatewayTransactionId = $callbackData['gateway_transaction_id'] ?? null;
        $amount = floatval($callbackData['amount'] ?? 0);
        $callbackMessage = $callbackData['message'] ?? '';
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Update transaction record
            $this->updateTransactionStatus($transaction['transaction_id'], $paymentStatus, $callbackData);
            
            // Update payment record
            $paymentUpdateResult = $this->updatePaymentStatus($transaction['payment_id'], $paymentStatus, $callbackData);
            
            // Update rental agreement status if needed
            if ($paymentStatus === 'completed' || $paymentStatus === 'success') {
                $this->updateRentalAgreementStatus($transaction['payment_id']);
            }
            
            // Send notifications
            $this->sendPaymentNotifications($transaction['payment_id'], $paymentStatus);
            
            // Log audit trail
            $this->logPaymentAudit($transaction['payment_id'], $paymentStatus, $callbackData);
            
            // Commit transaction
            $this->db->commit();
            
            return [
                'status' => 'success',
                'payment_id' => $transaction['payment_id'],
                'transaction_id' => $transaction['transaction_id'],
                'payment_status' => $paymentStatus,
                'message' => 'Payment processed successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Find transaction by reference
     */
    private function findTransaction($transactionReference) {
        $sql = "SELECT pt.*, p.tenant_id, p.agreement_id, p.amount, p.payment_type
                FROM payment_transactions pt
                JOIN payments p ON pt.payment_id = p.payment_id
                WHERE pt.transaction_reference = ?";
        
        $stmt = $this->db->prepare($sql);
        return $this->db->getSingle($stmt, [$transactionReference]);
    }
    
    /**
     * Update transaction status
     */
    private function updateTransactionStatus($transactionId, $status, $callbackData) {
        $sql = "UPDATE payment_transactions 
                SET status = ?, gateway_transaction_id = ?, callback_received = TRUE, 
                   callback_data = ?, updated_at = NOW()
                WHERE transaction_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $status === 'completed' || $status === 'success' ? 'completed' : 'failed',
            $callbackData['gateway_transaction_id'] ?? null,
            json_encode($callbackData),
            $transactionId
        ]);
    }
    
    /**
     * Update payment status
     */
    private function updatePaymentStatus($paymentId, $status, $callbackData) {
        // Map gateway status to payment status
        $paymentStatus = 'Failed';
        if ($status === 'completed' || $status === 'success') {
            $paymentStatus = 'Verified';
        }
        
        $sql = "UPDATE payments 
                SET payment_status = ?, gateway_response = ?, verified_at = NOW()
                WHERE payment_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $paymentStatus,
            json_encode($callbackData),
            $paymentId
        ]);
        
        return $paymentStatus;
    }
    
    /**
     * Update rental agreement status based on payment
     */
    private function updateRentalAgreementStatus($paymentId) {
        // Get payment details
        $sql = "SELECT p.*, ra.status as agreement_status, ra.start_date, ra.end_date
                FROM payments p
                JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
                WHERE p.payment_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $payment = $this->db->getSingle($stmt, [$paymentId]);
        
        if (!$payment) {
            return;
        }
        
        // Update agreement status based on payment type and amount
        if ($payment['payment_type'] === 'FULL' || $payment['balance_remaining'] <= 0) {
            // Full payment completed - activate agreement
            $newStatus = 'active';
        } elseif ($payment['payment_type'] === 'MINIMUM') {
            // Minimum payment - set to partially paid
            $newStatus = 'partially_paid';
        } else {
            // Monthly payment - keep active
            $newStatus = 'active';
        }
        
        $sql = "UPDATE rental_agreements 
                SET status = ?, updated_at = NOW()
                WHERE agreement_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [$newStatus, $payment['agreement_id']]);
    }
    
    /**
     * Send payment notifications
     */
    private function sendPaymentNotifications($paymentId, $status) {
        // Get payment details with user information
        $sql = "SELECT p.*, ra.agreement_id, prop.title as property_title, 
                       u.full_name as tenant_name, u.email as tenant_email,
                       owner.full_name as owner_name, owner.email as owner_email
                FROM payments p
                JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
                JOIN properties prop ON ra.property_id = prop.property_id
                JOIN users u ON p.tenant_id = u.user_id
                JOIN users owner ON prop.owner_id = owner.user_id
                WHERE p.payment_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $payment = $this->db->getSingle($stmt, [$paymentId]);
        
        if (!$payment) {
            return;
        }
        
        $isSuccess = ($status === 'completed' || $status === 'success');
        $notificationType = $isSuccess ? 'success' : 'warning';
        $message = $isSuccess ? 
            "Payment of ETB " . number_format($payment['amount'], 0) . " received for {$payment['property_title']}" :
            "Payment of ETB " . number_format($payment['amount'], 0) . " failed for {$payment['property_title']}";
        
        // Send notification to tenant
        $this->createNotification($payment['tenant_id'], $notificationType, $message, 
            "payment-details.php?id={$paymentId}");
        
        // Send notification to property owner
        $this->createNotification($payment['owner_id'], $notificationType, 
            "Payment received from {$payment['tenant_name']} for {$payment['property_title']}", 
            "../owner/payments.php");
        
        // Send notification to employees (if any)
        $this->notifyEmployees($payment, $isSuccess);
    }
    
    /**
     * Create notification
     */
    private function createNotification($userId, $type, $message, $link = null) {
        $sql = "INSERT INTO notifications (user_id, type, message, link, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [$userId, $type, $message, $link]);
    }
    
    /**
     * Notify employees about payment
     */
    private function notifyEmployees($payment, $isSuccess) {
        $sql = "SELECT user_id FROM users WHERE role = 'employee' AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $employees = $this->db->getMultiple($stmt, []);
        
        $message = $isSuccess ? 
            "New payment received: {$payment['tenant_name']} paid ETB " . number_format($payment['amount'], 0) :
            "Payment failed: {$payment['tenant_name']} attempted to pay ETB " . number_format($payment['amount'], 0);
        
        foreach ($employees as $employee) {
            $this->createNotification($employee['user_id'], 'info', $message, 
                "employee/payments.php");
        }
    }
    
    /**
     * Log payment audit
     */
    private function logPaymentAudit($paymentId, $status, $callbackData) {
        $sql = "INSERT INTO payment_audit_log (payment_id, user_id, action, details, ip_address, user_agent, created_at)
                SELECT ?, tenant_id, 'payment_callback', ?, '', '', NOW()
                FROM payments WHERE payment_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [$paymentId, json_encode($callbackData), $paymentId]);
    }
    
    /**
     * Verify callback signature (for production)
     */
    private function verifySignature($data) {
        // In production, verify HMAC signature from gateway
        // For now, return true for testing
        return $this->testMode;
    }
    
    /**
     * Log callback events
     */
    private function logCallback($event, $data) {
        $sql = "INSERT INTO payment_audit_log (action, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            "callback_{$event}",
            json_encode($data),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    /**
     * Send response to gateway
     */
    private function sendGatewayResponse($result) {
        $response = [
            'status' => $result['status'],
            'message' => $result['message'],
            'timestamp' => time()
        ];
        
        echo json_encode($response);
    }
}

// Handle callback
try {
    $handler = new PaymentCallbackHandler($db);
    $handler->handleCallback();
} catch (Exception $e) {
    error_log("Payment Callback Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
?>
