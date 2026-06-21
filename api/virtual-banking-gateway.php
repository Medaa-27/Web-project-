<?php
/**
 * Virtual Banking Payment Gateway Integration
 * Handles secure payment processing with external bank APIs
 */

require_once '../includes/config.php';

// Ensure API returns clean JSON even if PHP notices/warnings occur
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class VirtualBankingGateway {
    private $db;
    private $session;
    private $testMode = true; // Set to false for production
    
    // Gateway configuration
    private $gatewayConfig = [
        'test' => [
            'endpoint' => 'https://test-bank.example.com/api/payment',
            'merchant_id' => 'TEST_MERCHANT_001',
            'api_key' => 'test_api_key_12345',
            'secret_key' => 'test_secret_key_67890',
            'callback_url' => 'https://your-domain.com/api/payment-callback.php',
            'success_url' => 'https://your-domain.com/tenant/payment-success.php',
            'failure_url' => 'https://your-domain.com/tenant/payment-failure.php'
        ],
        'production' => [
            'endpoint' => 'https://bank.example.com/api/payment',
            'merchant_id' => 'PROD_MERCHANT_001',
            'api_key' => 'prod_api_key_secure',
            'secret_key' => 'prod_secret_key_secure',
            'callback_url' => 'https://your-domain.com/api/payment-callback.php',
            'success_url' => 'https://your-domain.com/tenant/payment-success.php',
            'failure_url' => 'https://your-domain.com/tenant/payment-failure.php'
        ]
    ];
    
    public function __construct($database, $session) {
        $this->db = $database;
        $this->session = $session;
    }
    
    /**
     * Initiate payment with virtual banking gateway
     */
    public function initiatePayment($paymentData) {
        try {
            // Validate required fields
            $requiredFields = ['agreement_id', 'payment_type', 'tenant_id'];
            foreach ($requiredFields as $field) {
                if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Get rental agreement details
            $agreement = $this->getRentalAgreement($paymentData['agreement_id'], $paymentData['tenant_id']);
            if (!$agreement) {
                throw new Exception('Rental agreement not found or access denied');
            }
            
            // Calculate payment amounts based on type
            $paymentAmounts = $this->calculatePaymentAmounts($paymentData['payment_type'], $agreement);
            
            // Generate unique transaction reference
            $transactionReference = $this->generateTransactionReference();
            
            // Create payment record
            $paymentId = $this->createPaymentRecord($paymentData, $paymentAmounts, $transactionReference);
            
            // Create transaction record
            $transactionId = $this->createTransactionRecord($paymentId, $transactionReference, $paymentAmounts['amount']);
            
            // Log audit trail
            $this->logPaymentAudit($paymentData['tenant_id'], 'payment_initiated', $paymentId, $transactionId, [
                'payment_type' => $paymentData['payment_type'],
                'amount' => $paymentAmounts['amount'],
                'transaction_reference' => $transactionReference
            ]);
            
            // Prepare gateway request
            $gatewayRequest = $this->prepareGatewayRequest($paymentData, $paymentAmounts, $transactionReference, $agreement);
            
            // Send to gateway (or simulate for test mode)
            if ($this->testMode) {
                $gatewayResponse = $this->simulateGatewayResponse($gatewayRequest);
            } else {
                $gatewayResponse = $this->sendToGateway($gatewayRequest);
            }
            
            // Update transaction with gateway response
            $this->updateTransactionResponse($transactionId, $gatewayResponse);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'transaction_id' => $transactionId,
                'transaction_reference' => $transactionReference,
                'amount' => $paymentAmounts['amount'],
                'payment_type' => $paymentData['payment_type'],
                'gateway_url' => $gatewayResponse['redirect_url'] ?? null,
                'test_mode' => $this->testMode
            ];
            
        } catch (Exception $e) {
            error_log("Virtual Banking Gateway Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate payment amounts based on payment type
     */
    private function calculatePaymentAmounts($paymentType, $agreement) {
        $monthlyRent = floatval($agreement['monthly_rent']);
        $securityDeposit = floatval($agreement['security_deposit'] ?? 0);
        
        switch ($paymentType) {
            case 'FULL':
                // 6-month full payment
                $totalAmount = $monthlyRent * 6;
                $amountPaid = $totalAmount;
                $balanceRemaining = 0;
                break;
                
            case 'MINIMUM':
                // 20% reservation fee or first month rent (whichever is higher)
                $reservationFee = $monthlyRent * 0.2;
                $firstMonthRent = $monthlyRent;
                $amountPaid = max($reservationFee, $firstMonthRent);
                $totalAmount = $monthlyRent * 6;
                $balanceRemaining = $totalAmount - $amountPaid;
                break;
                
            case 'MONTHLY':
            default:
                // Regular monthly payment
                $totalAmount = $monthlyRent;
                $amountPaid = $monthlyRent;
                $balanceRemaining = 0;
                break;
        }
        
        return [
            'total_amount' => $totalAmount,
            'amount' => $amountPaid,
            'balance_remaining' => $balanceRemaining
        ];
    }
    
    /**
     * Generate unique transaction reference
     */
    private function generateTransactionReference() {
        return 'AKSUM' . date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create payment record
     */
    private function createPaymentRecord($paymentData, $amounts, $transactionReference) {
        $sql = "INSERT INTO payments (
                    agreement_id, tenant_id, property_id, amount, total_amount, amount_paid, 
                    balance_remaining, payment_type, payment_for, payment_date, payment_method, 
                    transaction_reference, payment_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'rent', CURDATE(), 'virtual_bank', ?, 'Pending', NOW())";
        
        $stmt = $this->db->prepare($sql);
        $params = [
            $paymentData['agreement_id'],
            $paymentData['tenant_id'],
            $paymentData['property_id'] ?? null,
            $amounts['amount'],
            $amounts['total_amount'],
            $amounts['amount'],
            $amounts['balance_remaining'],
            $paymentData['payment_type'],
            $transactionReference
        ];
        
        $this->db->execute($stmt, $params);
        return $this->db->getLastInsertId();
    }
    
    /**
     * Create transaction record
     */
    private function createTransactionRecord($paymentId, $transactionReference, $amount) {
        $sql = "INSERT INTO payment_transactions (
                    payment_id, transaction_reference, gateway_provider, 
                    amount, currency, status, callback_url
                ) VALUES (?, ?, 'Virtual Bank', ?, 'ETB', 'initiated', ?)";
        
        $config = $this->gatewayConfig[$this->testMode ? 'test' : 'production'];

        try {
            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, [
                $paymentId,
                $transactionReference,
                $amount,
                $config['callback_url']
            ]);
            return $this->db->getLastInsertId();
        } catch (Exception $e) {
            // In case the payment_transactions table does not exist or fails,
            // log and continue so that the payment flow does not break.
            error_log('Failed to create transaction record: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get rental agreement details
     */
    private function getRentalAgreement($agreementId, $tenantId) {
        $sql = "SELECT ra.*, p.monthly_rent, p.security_deposit, p.title, p.property_id
                FROM rental_agreements ra
                JOIN properties p ON ra.property_id = p.property_id
                WHERE ra.agreement_id = ? AND ra.tenant_id = ? AND ra.status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        return $this->db->getSingle($stmt, [$agreementId, $tenantId]);
    }
    
    /**
     * Prepare gateway request data
     */
    private function prepareGatewayRequest($paymentData, $amounts, $transactionReference, $agreement) {
        $config = $this->gatewayConfig[$this->testMode ? 'test' : 'production'];
        
        return [
            'merchant_id' => $config['merchant_id'],
            'transaction_reference' => $transactionReference,
            'amount' => $amounts['amount'],
            'currency' => 'ETB',
            'payment_type' => $paymentData['payment_type'],
            'description' => "Rent payment for {$agreement['title']} - {$paymentData['payment_type']}",
            'customer_info' => [
                'tenant_id' => $paymentData['tenant_id'],
                'agreement_id' => $paymentData['agreement_id']
            ],
            'callback_url' => $config['callback_url'],
            'success_url' => $config['success_url'],
            'failure_url' => $config['failure_url'],
            'timestamp' => time(),
            'test_mode' => $this->testMode
        ];
    }
    
    /**
     * Simulate gateway response for testing
     */
    private function simulateGatewayResponse($gatewayRequest) {
        // Simulate processing delay
        usleep(100000); // 0.1 second
        
        return [
            'status' => 'success',
            'redirect_url' => "https://test-bank.example.com/pay?ref=" . $gatewayRequest['transaction_reference'],
            'gateway_transaction_id' => 'GT_' . time() . '_' . mt_rand(1000, 9999),
            'message' => 'Payment initiated successfully'
        ];
    }
    
    /**
     * Send request to actual gateway (for production)
     */
    private function sendToGateway($gatewayRequest) {
        $config = $this->gatewayConfig[$this->testMode ? 'test' : 'production'];
        
        // Generate signature
        $signature = $this->generateSignature($gatewayRequest, $config['secret_key']);
        $gatewayRequest['signature'] = $signature;
        
        $ch = curl_init($config['endpoint']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gatewayRequest));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Gateway request failed with HTTP code: $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Generate signature for gateway request
     */
    private function generateSignature($data, $secretKey) {
        ksort($data);
        $signatureString = '';
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $signatureString .= $key . '=' . $value . '&';
        }
        $signatureString .= 'secret_key=' . $secretKey;
        
        return hash_hmac('sha256', $signatureString, $secretKey);
    }
    
    /**
     * Update transaction with gateway response
     */
    private function updateTransactionResponse($transactionId, $gatewayResponse) {
        if (empty($transactionId)) {
            // If we didn't create a transaction record, skip updating transaction response.
            return;
        }

        $sql = "UPDATE payment_transactions 
                SET gateway_transaction_id = ?, status = 'processing', gateway_response = ?
                WHERE transaction_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $gatewayResponse['gateway_transaction_id'] ?? null,
            json_encode($gatewayResponse),
            $transactionId
        ]);
    }
    
    /**
     * Log payment audit trail
     */
    private function logPaymentAudit($userId, $action, $paymentId = null, $transactionId = null, $details = []) {
        $sql = "INSERT INTO payment_audit_log (
                    payment_id, transaction_id, user_id, action, details, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $this->db->execute($stmt, [
            $paymentId,
            $transactionId,
            $userId,
            $action,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}

// Handle API requests
try {
    $gateway = new VirtualBankingGateway($db, $session);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Verify user is logged in
    if (!$session->isLoggedIn()) {
        http_response_code(401);
        throw new Exception('User not authenticated');
    }
    
    // Add tenant_id to payment data
    $input['tenant_id'] = $session->getUserId();
    
    // Process payment initiation
    $result = $gateway->initiatePayment($input);

    $debug = trim(ob_get_clean());
    if (!empty($debug) && empty($result['debug'])) {
        $result['debug'] = $debug;
    }

    echo json_encode($result);

} catch (Exception $e) {
    $debug = trim(ob_get_clean());
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
    ];
    if (!empty($debug)) {
        $response['debug'] = $debug;
    }
    echo json_encode($response);
}
?>
