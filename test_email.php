<?php
require_once 'includes/config.php';

$testEmail = 'hagomedhanye85@gmail.com';
$subject = 'Test Email from HRMS';
$body = '<h1>Test Email</h1><p>This is a test email to verify SMTP configuration.</p>';

echo "Attempting to send test email to $testEmail...\n";
if (sendEmail($testEmail, $subject, $body)) {
    echo "Test email sent successfully!\n";
} else {
    echo "Failed to send test email. Check logs for SMTP DEBUG info.\n";
}
?>
