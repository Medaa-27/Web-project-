<?php

namespace PaymentGateways;

interface PaymentGatewayInterface {
    /**
     * Initialize a payment session.
     *
     * @param float $amount The amount to be paid.
     * @param string $currency The currency code (e.g., 'ETB').
     * @param string $reference A unique reference for the transaction.
     * @param array $customerDetails Array containing customer info (e.g., 'name', 'email', 'phone').
     * @param string $returnUrl The URL to redirect back to after payment.
     * @return array Returns an array with 'success' (bool) and 'redirect_url' (string) or 'error' (string).
     */
    public function initializePayment($amount, $currency, $reference, $customerDetails, $returnUrl);

    /**
     * Verify a payment transaction.
     *
     * @param string $transactionId The gateway's transaction ID.
     * @param string $reference The original unique reference.
     * @return array Returns an array with 'success' (bool), 'status' (string), 'amount' (float), and 'currency' (string).
     */
    public function verifyPayment($transactionId, $reference);
}
