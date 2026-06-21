# Advance Payment Module

This module implements the 20% advance payment requirement for tenants after their rental request is approved.

## Features

- Generate unique reference codes for each payment
- Simulate payment processing (80% success rate for demo)
- Track payment status (pending, paid, failed)
- Simple UI for tenants to make advance payments

## Database Setup

Run the following SQL to create the `advance_payments` table:

```sql
CREATE TABLE IF NOT EXISTS `advance_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `reference_code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `reference_code` (`reference_code`),
  KEY `tenant_id` (`tenant_id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `advance_payments_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `advance_payments_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Files Modified/Created

1. `../advance_payments_table.sql` - SQL schema for the payments table
2. `../includes/functions.php` - Added payment processing functions
3. `../tenant/advance-payment.php` - Payment UI for tenants
4. `../setup_advance_payments.php` - Setup script (run via web browser)
5. `../test_advance_payments.php` - Test file to verify functionality

## Functions Added

### `generateAdvancePaymentReference()`
Generates a unique reference code in format: ADV{date}{random}

### `createAdvancePayment($tenant_id, $property_id, $amount)`
Creates a new advance payment record with pending status.

### `processAdvancePayment($payment_id)`
Simulates payment processing with 80% success rate.

### `getAdvancePayment($payment_id)`
Retrieves payment details with tenant and property information.

### `getTenantAdvancePayments($tenant_id)`
Gets all advance payments for a specific tenant.

### `hasPendingAdvancePayment($tenant_id, $property_id)`
Checks if a tenant has a pending payment for a property.

## Usage

1. After a rental request is approved, the tenant can access `../tenant/advance-payment.php`
2. The system shows approved requests that require advance payment
3. Tenant clicks "Pay Advance" to create a payment record
4. Tenant can then "Process" the payment (simulated)
5. Payment status updates to "paid" or "failed"

## Business Rules

- Tenants must pay 20% advance after request approval
- Each payment gets a unique reference code
- Payments can be pending, paid, or failed
- One advance payment per tenant per property
- Failed payments can be retried

## Testing

Access `../test_advance_payments.php` in your browser to test the functionality.
