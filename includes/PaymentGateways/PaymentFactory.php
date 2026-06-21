<?php

namespace PaymentGateways;

class PaymentFactory {
    /**
     * Creates an instance of a PaymentGatewayInterface based on the requested method.
     *
     * @param string $method The requested payment method (e.g. 'telebirr', 'cbe', 'mpesa', etc.)
     * @return PaymentGatewayInterface Returns a specific implementation of the interface.
     */
    public static function createGateway($method) {
        // Here we would switch based on the method
        // For example:
        // switch (strtolower($method)) {
        //     case 'telebirr':
        //         return new TelebirrGateway();
        //     case 'cbe':
        //         return new CBEGateway();
        //     default:
        //         throw new Exception("Payment method not supported.");
        // }

        // For now, as requested, we use the SimulatorGateway for all methods 
        // to provide a testing environment that can easily be swapped later.
        
        return new SimulatorGateway();
    }
}
