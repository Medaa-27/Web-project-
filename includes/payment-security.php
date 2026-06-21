<?php
/**
 * Payment Security and Audit System
 * Comprehensive security measures for the Virtual Banking Payment Module
 */

require_once '../includes/config.php';

class PaymentSecurity {
    private $db;
    private $session;
    private $maxAttempts = 5;
    private $lockoutTime = 900; // 15 minutes
    private $suspiciousThreshold = 3;

    public function __construct($database, $session) {
        $this->db = $database;
        $this->session = $session;
    }

    /**
     * Log comprehensive audit trail for payment activities
     */
    public function logPaymentAudit($action, $paymentId = null, $transactionId = null, $data = [], $severity = 'INFO') {
        try {
            $userId = $this->session->getUserId() ?? null;
            $userRole = $this->session->getUserRole() ?? 'guest';
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $sessionId = session_id();

            // Sanitize sensitive data
            $data = $this->sanitizeAuditData($data);

            $sql = "INSERT INTO payment_audit_log (
                        payment_id, transaction_id, user_id, action, severity, old_status, new_status,
                        amount, ip_address, user_agent, session_id, details, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, [
                $paymentId,
                $transactionId,
                $userId,
                $action,
                $severity,
                $data['old_status'] ?? null,
                $data['new_status'] ?? null,
                $data['amount'] ?? null,
                $ipAddress,
                $userAgent,
                $sessionId,
                json_encode($data)
            ]);

            // Check for suspicious activity
            $this->detectSuspiciousActivity($action, $userId, $ipAddress, $data);

            return true;

        } catch (Exception $e) {
            error_log("Payment Audit Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate payment request security
     */
    public function validatePaymentRequest($paymentData) {
        $violations = [];

        // Check rate limiting
        if ($this->isRateLimited($paymentData['tenant_id'] ?? null)) {
            $violations[] = 'Rate limit exceeded. Please wait before making another payment.';
        }

        // Check for suspicious amount patterns
        if ($this->isSuspiciousAmount($paymentData['amount'] ?? 0)) {
            $violations[] = 'Payment amount appears suspicious.';
            $this->logPaymentAudit('suspicious_amount_detected', null, null, $paymentData, 'WARNING');
        }

        // Check for rapid successive payments
        if ($this->hasRapidPayments($paymentData['tenant_id'] ?? null)) {
            $violations[] = 'Multiple rapid payment attempts detected.';
            $this->logPaymentAudit('rapid_payments_detected', null, null, $paymentData, 'WARNING');
        }

        // Validate transaction reference format
        if (isset($paymentData['transaction_reference']) &&
            !$this->isValidTransactionReference($paymentData['transaction_reference'])) {
            $violations[] = 'Invalid transaction reference format.';
        }

        // Check for potential fraud patterns
        if ($this->detectFraudPatterns($paymentData)) {
            $violations[] = 'Payment request flagged for security review.';
            $this->logPaymentAudit('fraud_pattern_detected', null, null, $paymentData, 'CRITICAL');
        }

        return $violations;
    }

    /**
     * Check if user is rate limited
     */
    private function isRateLimited($userId) {
        if (!$userId) return false;

        $sql = "SELECT COUNT(*) as attempts FROM payment_audit_log
                WHERE user_id = ? AND action IN ('payment_initiated', 'payment_attempt')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";

        $stmt = $this->db->prepare($sql);
        $result = $this->db->getSingle($stmt, [$userId]);

        return ($result['attempts'] ?? 0) >= $this->maxAttempts;
    }

    /**
     * Check for suspicious amount patterns
     */
    private function isSuspiciousAmount($amount) {
        if ($amount <= 0) return true;

        // Check for round numbers that might indicate fraud
        if ($amount % 1000 === 0 && $amount >= 10000) {
            return true;
        }

        // Check for amounts that are too large
        if ($amount > 500000) { // ETB 500,000 threshold
            return true;
        }

        return false;
    }

    /**
     * Check for rapid payment attempts
     */
    private function hasRapidPayments($userId) {
        if (!$userId) return false;

        $sql = "SELECT COUNT(*) as recent_attempts FROM payment_audit_log
                WHERE user_id = ? AND action = 'payment_initiated'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";

        $stmt = $this->db->prepare($sql);
        $result = $this->db->getSingle($stmt, [$userId]);

        return ($result['recent_attempts'] ?? 0) >= 3;
    }

    /**
     * Validate transaction reference format
     */
    private function isValidTransactionReference($reference) {
        // AKSUM + Year + 6 digits
        return preg_match('/^AKSUM\d{4}\d{6}$/', $reference);
    }

    /**
     * Detect fraud patterns
     */
    private function detectFraudPatterns($paymentData) {
        $userId = $paymentData['tenant_id'] ?? null;
        if (!$userId) return false;

        // Check for failed payment patterns
        $sql = "SELECT
                    COUNT(*) as total_attempts,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_events,
                    MAX(created_at) as last_attempt
                FROM payment_audit_log
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        $stmt = $this->db->prepare($sql);
        $result = $this->db->getSingle($stmt, [$userId]);

        $totalAttempts = $result['total_attempts'] ?? 0;
        $criticalEvents = $result['critical_events'] ?? 0;
        $lastAttempt = $result['last_attempt'] ?? null;

        // Flag if too many attempts or critical events
        if ($totalAttempts >= 10 || $criticalEvents >= 2) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize sensitive data for audit logging
     */
    private function sanitizeAuditData($data) {
        $sensitiveFields = ['card_number', 'cvv', 'password', 'pin', 'bank_credentials'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Detect and log suspicious activity
     */
    private function detectSuspiciousActivity($action, $userId, $ipAddress, $data) {
        $suspiciousPatterns = [
            'multiple_ips' => $this->checkMultipleIPs($userId, $ipAddress),
            'unusual_amount' => $this->isSuspiciousAmount($data['amount'] ?? 0),
            'unusual_time' => $this->isUnusualTime(),
            'foreign_ip' => $this->isForeignIP($ipAddress)
        ];

        $suspiciousCount = array_sum(array_map('intval', $suspiciousPatterns));

        if ($suspiciousCount >= $this->suspiciousThreshold) {
            $this->logPaymentAudit('suspicious_activity_detected', null, null,
                array_merge($data, ['suspicious_patterns' => $suspiciousPatterns]), 'CRITICAL');
        }
    }

    /**
     * Check if user is using multiple IP addresses
     */
    private function checkMultipleIPs($userId, $currentIP) {
        if (!$userId) return false;

        $sql = "SELECT COUNT(DISTINCT ip_address) as ip_count FROM payment_audit_log
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        $stmt = $this->db->prepare($sql);
        $result = $this->db->getSingle($stmt, [$userId]);

        return ($result['ip_count'] ?? 0) >= 3;
    }

    /**
     * Check if payment is made at unusual time
     */
    private function isUnusualTime() {
        $hour = (int) date('H');
        // Flag payments between 2 AM and 6 AM
        return $hour >= 2 && $hour <= 6;
    }

    /**
     * Check if IP is from foreign location (basic check)
     */
    private function isForeignIP($ipAddress) {
        // For Ethiopian system, flag non-Ethiopian IPs
        // This is a basic implementation - in production, use GeoIP database
        return !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Generate security token for payment verification
     */
    public function generateSecurityToken($paymentId, $userId) {
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        $data = $paymentId . $userId . $timestamp . $random;

        return hash_hmac('sha256', $data, PAYMENT_SECRET_KEY ?? 'default_secret_key');
    }

    /**
     * Validate security token
     */
    public function validateSecurityToken($token, $paymentId, $userId, $maxAge = 3600) {
        // In a real implementation, you'd need to store and verify against stored tokens
        // This is a basic implementation
        return strlen($token) === 64; // SHA256 hash length
    }

    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get security report for admin dashboard
     */
    public function getSecurityReport($days = 7) {
        try {
            $sql = "SELECT
                        action,
                        severity,
                        COUNT(*) as count,
                        DATE(created_at) as date
                    FROM payment_audit_log
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY action, severity, DATE(created_at)
                    ORDER BY date DESC, count DESC";

            $stmt = $this->db->prepare($sql);
            $report = $this->db->getMultiple($stmt, [$days]);

            // Get suspicious activities summary
            $sql = "SELECT COUNT(*) as suspicious_count FROM payment_audit_log
                    WHERE severity = 'CRITICAL'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";

            $stmt = $this->db->prepare($sql);
            $suspicious = $this->db->getSingle($stmt, [$days]);

            return [
                'report' => $report,
                'suspicious_activities' => $suspicious['suspicious_count'] ?? 0,
                'period_days' => $days
            ];

        } catch (Exception $e) {
            error_log("Security Report Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Lock user account for suspicious activity
     */
    public function lockUserAccount($userId, $reason, $duration = 3600) {
        // In a real implementation, you'd have a user_lockouts table
        $this->logPaymentAudit('account_locked', null, null,
            ['user_id' => $userId, 'reason' => $reason, 'duration' => $duration], 'CRITICAL');

        // Here you would implement actual account locking logic
        // For now, just log the action
    }

    /**
     * Check if user account is locked
     */
    public function isAccountLocked($userId) {
        // In a real implementation, check user_lockouts table
        // For now, return false
        return false;
    }
}

// Initialize security system
$paymentSecurity = new PaymentSecurity($db, $session);
?>
