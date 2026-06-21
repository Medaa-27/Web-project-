<?php
/**
 * Payment Verification System
 * Handles manual payment verification and status checking
 */

require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class PaymentVerification {
    private $db;
    private $session;
    
    public function __construct($database, $session) {
        $this->db = $database;
        $this->session = $session;
    }
    
    /**
     * Verify payment manually (for employees/admins)
     */
    public function verifyPayment($paymentId, $verificationData) {
        try {
            // Check permissions
            if (!$this->canVerifyPayments()) {
                throw new Exception('Permission denied');
            }
            
            // Get payment details
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // Validate verification data
            $requiredFields = ['verification_code', 'amount'];
            foreach ($requiredFields as $field) {
                if (!isset($verificationData[$field]) || empty($verificationData[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Verify amount matches
            if (floatval($verificationData['amount']) != floatval($payment['amount'])) {
                throw new Exception('Amount mismatch');
            }
            
            // Update payment status
            $this->updatePaymentVerification($paymentId, $verificationData);
            
            // Update rental agreement if needed
            $this->updateRentalAgreementStatus($paymentId);
            
            // Send notifications
            $this->sendVerificationNotifications($paymentId, true);
            
            // Log audit trail
            $this->logVerificationAudit($paymentId, 'verified', $verificationData);
            
            return [
                'success' => true,
                'message' => 'Payment verified successfully',
                'payment_id' => $paymentId
            ];
            
        } catch (Exception $e) {
            error_log("Payment Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reject payment
     */
    public function rejectPayment($paymentId, $rejectionData) {
        try {
            // Check permissions
            if (!$this->canVerifyPayments()) {
                throw new Exception('Permission denied');
            }
            
            // Get payment details
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // Update payment status to failed
            $sql = "UPDATE payments 
                    SET payment_status = 'Failed', verified_at = NOW(), 
                        gateway_response = ?, verified_by = ?
                    WHERE payment_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, [
                json_encode($rejectionData),
                $this->session->getUserId(),
                $paymentId
            ]);
            
            // Send notifications
            $this->sendVerificationNotifications($paymentId, false);
            
            // Log audit trail
            $this->logVerificationAudit($paymentId, 'rejected', $rejectionData);
            
            return [
                'success' => true,
                'message' => 'Payment rejected successfully',
                'payment_id' => $paymentId
            ];
            
        } catch (Exception $e) {
            error_log("Payment Rejection Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check payment status
     */
    public function checkPaymentStatus($transactionReference) {
        try {
            // Get payment details
            $sql = "SELECT p.*, pt.status as transaction_status, pt.gateway_transaction_id
                    FROM payments p
                    LEFT JOIN payment_transactions pt ON p.payment_id = pt.payment_id
                    WHERE p.transaction_reference = ?";
            
            $stmt = $this->db->prepare($sql);
            $payment = $this->db->getSingle($stmt, [$transactionReference]);
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            return [
                'success' => true,
                'payment' => [
                    'payment_id' => $payment['payment_id'],
                    'transaction_reference' => $payment['transaction_reference'],
                    'amount' => $payment['amount'],
                    'payment_type' => $payment['payment_type'],
                    'payment_status' => $payment['payment_status'],
                    'transaction_status' => $payment['transaction_status'],
                    'gateway_transaction_id' => $payment['gateway_transaction_id'],
                    'created_at' => $payment['created_at'],
                    'verified_at' => $payment['verified_at']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get pending payments for verification
     */
    public function getPendingPayments() {
        try {
            // Check permissions
            if (!$this->canVerifyPayments()) {
                throw new Exception('Permission denied');
            }
            
            $sql = "SELECT p.*, ra.agreement_id, prop.title as property_title, 
                          l.location_name, u.full_name as tenant_name, u.email as tenant_email,
                          pt.transaction_reference, pt.gateway_transaction_id, pt.status as transaction_status
                    FROM payments p
                    JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
                    JOIN properties prop ON ra.property_id = prop.property_id
                    LEFT JOIN locations l ON prop.location_id = l.location_id
                    JOIN users u ON p.tenant_id = u.user_id
                    LEFT JOIN payment_transactions pt ON p.payment_id = pt.payment_id
                    WHERE p.payment_status = 'Pending'
                    ORDER BY p.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $payments = $this->db->getMultiple($stmt, []);
            
            return [
                'success' => true,
                'payments' => $payments
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user can verify payments
     */
    private function canVerifyPayments() {
        $userRole = $this->session->getUserRole();
        return in_array($userRole, ['admin', 'employee']);
    }
    
    /**
     * Get payment details
     */
    private function getPaymentDetails($paymentId) {
        $sql = "SELECT p.*, ra.agreement_id, prop.title as property_title, 
                      u.full_name as tenant_name
                FROM payments p
                JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
                JOIN properties prop ON ra.property_id = prop.property_id
                JOIN users u ON p.tenant_id = u.user_id
                WHERE p.payment_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $this->db->getSingle($stmt, [$paymentId]);
    }
    
    /**
     * Update payment verification
     */
    private function updatePaymentVerification($paymentId, $verificationData) {
        $sql = "UPDATE payments 
                SET payment_status = 'Verified', 
                    verification_code = ?,
                    verified_by = ?,
                    verified_at = NOW(),
                    gateway_response = ?
                WHERE payment_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $verificationData['verification_code'],
            $this->session->getUserId(),
            json_encode($verificationData),
            $paymentId
        ]);
    }
    
    /**
     * Update rental agreement status
     */
    private function updateRentalAgreementStatus($paymentId) {
        // Get payment details
        $sql = "SELECT p.*, ra.status as agreement_status
                FROM payments p
                JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
                WHERE p.payment_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $payment = $this->db->getSingle($stmt, [$paymentId]);
        
        if (!$payment) {
            return;
        }
        
        // Update agreement status based on payment type
        if ($payment['payment_type'] === 'FULL' || $payment['balance_remaining'] <= 0) {
            $newStatus = 'active';
        } elseif ($payment['payment_type'] === 'MINIMUM') {
            $newStatus = 'partially_paid';
        } else {
            $newStatus = 'active';
        }
        
        $sql = "UPDATE rental_agreements 
                SET status = ?, updated_at = NOW()
                WHERE agreement_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [$newStatus, $payment['agreement_id']]);
    }
    
    /**
     * Send verification notifications
     */
    private function sendVerificationNotifications($paymentId, $isVerified) {
        // Get payment details
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
        
        $notificationType = $isVerified ? 'success' : 'error';
        $message = $isVerified ? 
            "Your payment of ETB " . number_format($payment['amount'], 0) . " has been verified for {$payment['property_title']}" :
            "Your payment of ETB " . number_format($payment['amount'], 0) . " was rejected for {$payment['property_title']}";
        
        // Send notification to tenant
        $this->createNotification($payment['tenant_id'], $notificationType, $message, 
            "payment-details.php?id={$paymentId}");
        
        // Send notification to property owner
        $this->createNotification($payment['owner_id'], $notificationType, 
            "Payment verification " . ($isVerified ? "completed" : "failed") . " for {$payment['property_title']}", 
            "../owner/payments.php");
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
     * Log verification audit
     */
    private function logVerificationAudit($paymentId, $action, $data) {
        $sql = "INSERT INTO payment_audit_log (payment_id, user_id, action, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $paymentId,
            $this->session->getUserId(),
            "payment_{$action}",
            json_encode($data),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}

// Handle API requests
try {
    $verification = new PaymentVerification($db, $session);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // Verify or reject payment
            if (!$session->isLoggedIn()) {
                throw new Exception('User not authenticated');
            }
            
            $action = $input['action'] ?? '';
            $paymentId = $input['payment_id'] ?? 0;
            
            switch ($action) {
                case 'verify':
                    $result = $verification->verifyPayment($paymentId, $input);
                    break;
                    
                case 'reject':
                    $result = $verification->rejectPayment($paymentId, $input);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;
            
        case 'GET':
            // Check payment status or get pending payments
            $transactionReference = $_GET['reference'] ?? '';
            $getPending = $_GET['pending'] ?? '';
            
            if ($transactionReference) {
                $result = $verification->checkPaymentStatus($transactionReference);
            } elseif ($getPending && $verification->canVerifyPayments()) {
                $result = $verification->getPendingPayments();
            } else {
                throw new Exception('Invalid request');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
