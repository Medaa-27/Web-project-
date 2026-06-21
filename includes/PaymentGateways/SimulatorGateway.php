<?php

namespace PaymentGateways;

class SimulatorGateway implements PaymentGatewayInterface {

    public function initializePayment($amount, $currency, $reference, $customerDetails, $returnUrl) {
        // Create a unique session ID for the simulation
        $sessionId = 'sim_sess_' . uniqid() . '_' . time();

        // In a real application, we'd store these details in a cache or session
        // For the simulation, we'll pass them securely via the URL parameters or session
        $_SESSION['payment_sim_' . $sessionId] = [
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $reference,
            'customer' => $customerDetails,
            'returnUrl' => $returnUrl,
            'status' => 'pending'
        ];

        // Construct the URL to the payment simulation page
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $dir = dirname($_SERVER['PHP_SELF']);
        
        // This assumes the API is called from /api/, so we go up one level
        $simUrl = $baseUrl . str_replace('/api', '', $dir) . '/public/payment-simulation.php?session_id=' . $sessionId;

        return [
            'success' => true,
            'redirect_url' => $simUrl
        ];
    }

    public function verifyPayment($transactionId, $reference) {
        // In the simulator, the transaction ID is generated on the simulation page.
        // The simulation page updates the database directly or updates the session.
        // For the abstraction, we'll verify it based on the session or assume it's successful 
        // if it reaches here and the database record exists.
        
        // In a real integration (e.g. Telebirr), we would call the provider's API 
        // with the transactionId and reference to check the status.
        
        // For this simulator, we just return success assuming the simulation page already validated it.
        return [
            'success' => true,
            'status' => 'completed',
            'amount' => null, // The real amount would come from the gateway
            'currency' => 'ETB'
        ];
    }
}
