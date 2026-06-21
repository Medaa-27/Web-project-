<?php
require_once '../includes/config.php';
require_once '../includes/wallet-functions.php';
require_once '../includes/PaymentGateways/PaymentGatewayInterface.php';
require_once '../includes/PaymentGateways/SimulatorGateway.php';
require_once '../includes/PaymentGateways/PaymentFactory.php';

// Ensure API returns clean JSON even if PHP notices/warnings occur
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
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

    $depositAmount  = $_POST['deposit_amount'] ?? '';
    $depositMethod  = $_POST['deposit_method'] ?? '';
    $transactionId  = trim($_POST['transaction_id'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    // Validation
    if (!$depositAmount || !is_numeric($depositAmount) || floatval($depositAmount) < 10) {
        throw new Exception('Minimum deposit amount is 10 ETB');
    }

    if (!$depositMethod) {
        throw new Exception('Payment method is required');
    }

    if (empty($transactionId)) {
        throw new Exception('Account or mobile number is required for verification');
    }

    // Bank-specific validation for transaction_id
    $transactionId = preg_replace('/[^0-9]/', '', $transactionId);
    switch (strtolower($depositMethod)) {
        case 'cbe':
            if (strlen($transactionId) !== 13) {
                throw new Exception('CBE account number must be exactly 13 digits');
            }
            break;
        case 'telebirr':
            if (!preg_match('/^09[0-9]{8}$/', $transactionId)) {
                throw new Exception('Telebirr number must be 10 digits starting with 09');
            }
            break;
        case 'mpesa':
            if (!preg_match('/^07[0-9]{8}$/', $transactionId)) {
                throw new Exception('M-PESA number must be 10 digits starting with 07');
            }
            break;
        case 'dashen':
            if (strlen($transactionId) < 10 || strlen($transactionId) > 13) {
                throw new Exception('Dashen Bank account should be 10-13 digits');
            }
            break;
        case 'boa':
            if (strlen($transactionId) < 9 || strlen($transactionId) > 13) {
                throw new Exception('Bank of Abyssinia account should be 9-13 digits');
            }
            break;
        case 'wegagen':
            if (strlen($transactionId) < 12 || strlen($transactionId) > 15) {
                throw new Exception('Wegagen Bank account should be 12-15 digits');
            }
            break;
    }

    $amount = floatval($depositAmount);

    $db->beginTransaction();

    // Insert deposit record (Pending state, waiting for verification)
    $sql = "INSERT INTO payments (
                tenant_id, property_id, amount, total_amount, amount_paid,
                balance_remaining, payment_type, payment_for, payment_date, payment_method,
                transaction_id, status, payment_status, notes, created_at
            ) VALUES (?, NULL, ?, ?, ?, ?, 'deposit', 'deposit', CURDATE(), ?, ?, 'pending', 'Pending', ?, NOW())";
    
    $stmt = $db->prepare($sql);
    $db->execute($stmt, [
        $tenantId,
        $amount,
        $amount,
        $amount,
        0,
        $depositMethod,
        $transactionId,
        $notes
    ]);

    $paymentId = $db->lastInsertId();
    if (!$paymentId) {
        throw new Exception('Failed to save deposit record');
    }

    $db->commit();

    // Get tenant details for Payment Gateway
    $tenantSql = "SELECT full_name, email, phone_number FROM users WHERE user_id = ?";
    $tenantStmt = $db->prepare($tenantSql);
    $tenant = $db->getSingle($tenantStmt, [$tenantId]);

    // Use Payment Abstraction Layer
    $gateway = \PaymentGateways\PaymentFactory::createGateway($depositMethod);
    
    // Create a unique reference combining our payment ID and random string
    $uniqueReference = 'DEP_' . $paymentId . '_' . uniqid();
    
    // Save reference to DB for later verification
    $updateRefSql = "UPDATE payments SET transaction_id = ? WHERE payment_id = ?";
    $updateRefStmt = $db->prepare($updateRefSql);
    $db->execute($updateRefStmt, [$uniqueReference, $paymentId]);

    $customerDetails = [
        'name' => $tenant['full_name'] ?? 'Tenant',
        'email' => $tenant['email'] ?? '',
        'phone' => $tenant['phone_number'] ?? $transactionId
    ];

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $dir = dirname($_SERVER['PHP_SELF']);
    $returnUrl = $baseUrl . str_replace('/api', '', $dir) . '/api/deposit-callback.php';

    $gatewayResponse = $gateway->initializePayment(
        $amount, 
        'ETB', 
        $uniqueReference, 
        $customerDetails, 
        $returnUrl
    );

    if (!$gatewayResponse['success']) {
        throw new Exception('Payment gateway initialization failed: ' . ($gatewayResponse['error'] ?? 'Unknown error'));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Redirecting to payment gateway...',
        'redirect_url' => $gatewayResponse['redirect_url']
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
