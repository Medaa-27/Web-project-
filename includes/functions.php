<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
// Helper functions for Aksum Rental Management System

/**
 * Check for expired partially paid agreements and process them
 */
function checkExpiredPayments() {
    global $db;
    
    // Use a flag to avoid multiple runs in the same request if called twice
    static $already_run = false;
    if ($already_run) return;
    $already_run = true;

    // Find expired agreements
    $sql = "SELECT ra.*, p.title as property_title, p.owner_id
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            WHERE ra.status = 'partially_paid' 
            AND ra.payment_deadline < NOW()";
    
    $stmt = $db->prepare($sql);
    $expired_agreements = $db->getMultiple($stmt);

    if (empty($expired_agreements)) return;

    $db->beginTransaction();

    try {
        foreach ($expired_agreements as $agreement) {
            $agreementId = $agreement['agreement_id'];
            $propertyId = $agreement['property_id'];
            $tenantId = $agreement['tenant_id'];
            $ownerId = $agreement['owner_id'];
            $requestId = $agreement['request_id'];

            // 1. Update agreement status to 'expired'
            $updateSql = "UPDATE rental_agreements SET status = 'expired', updated_at = NOW() WHERE agreement_id = ?";
            $updateStmt = $db->prepare($updateSql);
            $db->execute($updateStmt, [$agreementId]);

            // 2. Update request status to 'cancelled' (or 'expired' if you prefer)
            $reqUpdateSql = "UPDATE rental_requests SET status = 'cancelled' WHERE request_id = ?";
            $reqUpdateStmt = $db->prepare($reqUpdateSql);
            $db->execute($reqUpdateStmt, [$requestId]);

            // 3. Make property available again
            $propSql = "UPDATE properties SET status = 'available', updated_at = NOW() WHERE property_id = ?";
            $propStmt = $db->prepare($propSql);
            $db->execute($propStmt, [$propertyId]);

            // 4. Distribute the 20% advance payment (non-refundable)
            $paySql = "SELECT * FROM payments 
                       WHERE agreement_id = ? AND payment_type = 'MINIMUM' AND status = 'completed' 
                       ORDER BY created_at DESC LIMIT 1";
            $payStmt = $db->prepare($paySql);
            $payment = $db->getSingle($payStmt, [$agreementId]);

            $landlordAmount = 0;
            if ($payment) {
                $amount = (float)$payment['amount_paid']; 
                
                // 80% to landlord
                $landlordAmount = $amount * 0.80;
                $landlordDesc = "Non-refundable advance payment from expired agreement #$agreementId for property '{$agreement['property_title']}' (80% share)";
                logWalletTransaction($ownerId, $landlordAmount, 'deposit', 'completed', $landlordDesc, 'payments', $payment['payment_id']);

                // 20% to platform (admin)
                $platformAmount = $amount * 0.20;
                $adminSql = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
                $adminStmt = $db->prepare($adminSql);
                $admin = $db->getSingle($adminStmt);
                
                if ($admin) {
                    $platformDesc = "Platform fee from expired agreement #$agreementId (20% share of non-refundable advance)";
                    logWalletTransaction($admin['user_id'], $platformAmount, 'deposit', 'completed', $platformDesc, 'payments', $payment['payment_id']);
                }
            }

            // 5. Notifications
            $notifSql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $notifStmt = $db->prepare($notifSql);

            // Notify Tenant
            $tenantMsg = "Your rental agreement for '{$agreement['property_title']}' has expired because the remaining balance was not paid within the 10-day deadline. The advance payment is non-refundable.";
            $db->execute($notifStmt, [$tenantId, "Agreement Expired", $tenantMsg, 'danger']);

            // Notify Owner
            $ownerMsg = "The rental agreement for your property '{$agreement['property_title']}' has expired. The property is now available for rent again. You have been credited ETB " . number_format($landlordAmount, 2) . " from the non-refundable advance payment.";
            $db->execute($notifStmt, [$ownerId, "Agreement Expired", $ownerMsg, 'info']);
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error in checkExpiredPayments: " . $e->getMessage());
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 2) . ' ' . CURRENCY;
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Check if user owns property
function isPropertyOwner($user_id, $property_id) {
    global $db;
    $sql = "SELECT owner_id FROM properties WHERE property_id = ? AND owner_id = ?";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$property_id, $user_id]);
    return $result !== false;
}

// Get property details
function getPropertyDetails($property_id) {
    global $db;
    $sql = "SELECT p.*, l.location_name, u.full_name as owner_name 
            FROM properties p 
            LEFT JOIN locations l ON p.location_id = l.location_id
            LEFT JOIN users u ON p.owner_id = u.user_id
            WHERE p.property_id = ?";
    $stmt = $db->prepare($sql);
    return $db->getSingle($stmt, [$property_id]);
}

// Get property images
function getPropertyImages($property_id) {
    global $db;
    $sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id ASC";
    $stmt = $db->prepare($sql);
    return $db->getMultiple($stmt, [$property_id]);
}

// Get a valid primary image URL for a property.
// Falls back to any existing uploaded image if the primary file is missing, otherwise returns default image.
function getPropertyPrimaryImage($property_id) {
    global $db;

    // Fetch images ordered by is_primary desc then newest first
    $sql = "SELECT image_url FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id DESC";
    $stmt = $db->prepare($sql);
    $images = $db->getMultiple($stmt, [$property_id]);

    foreach ($images as $img) {
        $image_url = $img['image_url'];
        
        // Handle both full path (../assets/uploads/properties/filename.jpg) 
        // and filename only (filename.jpg) formats
        if (strpos($image_url, '../assets/uploads/properties/') === 0) {
            // Full path format - use as-is
            if (file_exists($image_url)) {
                return $image_url;
            }
            $filename = basename($image_url);
        } else {
            // Filename only format
            $filename = $image_url;
        }

        // Determine server-side uploads base (UPLOAD_PATH may be empty in CLI/testing contexts)
        $uploads_base = rtrim(UPLOAD_PATH, '/\\');
        if (!is_dir($uploads_base)) {
            $uploads_base = realpath(__DIR__ . '/../assets/uploads');
        }

        $server_path = $uploads_base . DIRECTORY_SEPARATOR . 'properties' . DIRECTORY_SEPARATOR . $filename;
        if ($server_path && file_exists($server_path)) {
            // Return a web-safe relative URL to the uploaded image
            return '../' . PROPERTY_IMG_PATH . $filename;
        }
    }

    // No uploaded image found or files missing on disk
    return '../assets/images/default-avatar.svg';
}

// Get user details
function getUserDetails($user_id) {
    global $db;
    $sql = "SELECT user_id, full_name, email, phone, role, status, profile_image, id_image, created_at 
            FROM users WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    return $db->getSingle($stmt, [$user_id]);
}

// Get the ID image path for a user record.
function getUserIdImagePath($user) {
    if (empty($user)) {
        return null;
    }

    if (!empty($user['id_image'])) {
        return $user['id_image'];
    }

    if (!empty($user['profile_image'])) {
        return $user['profile_image'];
    }

    return null;
}

// Generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

function ensureSupportChatSchema($db) {
    $schemaUpdates = [
        'support_messages' => [
            'file_path' => 'VARCHAR(255) NULL',
            'file_type' => 'VARCHAR(50) NULL',
            'reply_to' => 'INT NULL',
            'is_deleted' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'updated_at' => 'TIMESTAMP NULL'
        ],
        'support_tickets' => [
            'updated_at' => 'TIMESTAMP NULL'
        ]
    ];

    foreach ($schemaUpdates as $table => $columns) {
        foreach ($columns as $columnName => $definition) {
            $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
            $checkStmt = $db->prepare($checkSql);
            if (!$checkStmt) {
                continue;
            }
            $column = $db->getSingle($checkStmt, [DB_NAME, $table, $columnName]);
            if (!$column) {
                $alterSql = "ALTER TABLE `$table` ADD COLUMN `$columnName` $definition";
                $alterStmt = $db->prepare($alterSql);
                if ($alterStmt) {
                    $db->execute($alterStmt);
                }
            }
        }
    }
}

function sendEmail($to, $subject, $body)
{
    // All email notifications are disabled
    error_log("Email notification suppressed: To: {$to}, Subject: {$subject}");
    return false;
    
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        // use configuration constants or settings from database if available
        $host = defined('SMTP_HOST') ? SMTP_HOST : settings_get('smtp_host', '', 'email');
        $port = defined('SMTP_PORT') ? SMTP_PORT : settings_get('smtp_port', '', 'email');
        $user = defined('SMTP_USER') ? SMTP_USER : settings_get('smtp_username', '', 'email');
        $pass = defined('SMTP_PASS') ? SMTP_PASS : settings_get('smtp_password', '', 'email');
        $from = defined('SITE_EMAIL') ? SITE_EMAIL : $user;
        $fromName = defined('SITE_NAME') ? SITE_NAME : '';

        if (!$host) {
            error_log("Email not sent: SMTP host is not configured.");
            return false;
        }

        $mail->Host = $host;
        $mail->SMTPAuth = !empty($user) && !empty($pass);
        if (!empty($user)) {
            $mail->Username = $user;
        }
        if (!empty($pass)) {
            $mail->Password = $pass;
        }

        $mail->SMTPSecure = 'tls';
        $mail->Port = $port ?: 587;
        $mail->SMTPAutoTLS = true;

        // Gmail strictly requires the From address to match the authenticated user
        $mail->setFrom($user, $fromName ?: 'Aksum House Rental System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email send failed to {$to}: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false;
    }
}

function notificationExists($user_id, $title, $message, $type = 'info', $minutes = 10)
{
    global $db;

    $sql = "SELECT notification_id FROM notifications WHERE user_id = ? AND title = ? AND message = ? AND type = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE) LIMIT 1";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $existing = $db->getSingle($stmt, [$user_id, $title, $message, $type, $minutes]);
    return !empty($existing);
}

function createNotification($user_id, $title, $message, $type = 'info', $link = null, $dedupeMinutes = 10)
{
    global $db;

    if (notificationExists($user_id, $title, $message, $type, $dedupeMinutes)) {
        return false;
    }

    $sql = "INSERT INTO notifications (user_id, title, message, type, link, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare notification insert statement.");
        return false;
    }

    return $db->execute($stmt, [$user_id, $title, $message, $type, $link]);
}

function buildDefaultEmailBody($subject, $vars = [])
{
    $userName = htmlspecialchars($vars['user_name'] ?? $vars['tenant_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
    $propertyTitle = htmlspecialchars($vars['property_title'] ?? '', ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($vars['status'] ?? '', ENT_QUOTES, 'UTF-8');
    $decisionDate = htmlspecialchars($vars['decision_date'] ?? date('F d, Y H:i:s'), ENT_QUOTES, 'UTF-8');
    $messageText = htmlspecialchars($vars['message'] ?? '', ENT_QUOTES, 'UTF-8');
    $actionLink = htmlspecialchars($vars['action_link'] ?? SITE_URL, ENT_QUOTES, 'UTF-8');
    $siteName = htmlspecialchars($vars['site_name'] ?? SITE_NAME, ENT_QUOTES, 'UTF-8');

    return "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>" . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; }
        .header { background: #007bff; color: #fff; padding: 20px; text-align: center; }
        .content { padding: 30px; background: #fff; }
        .details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .btn { display: inline-block; padding: 12px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { padding: 20px; background: #f1f3f5; font-size: 12px; color: #6c757d; text-align: center; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h1 style=\"margin: 0;\">" . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</h1>
        </div>
        <div class=\"content\">
            <p>Dear {$userName},</p>
            <p>{$messageText}</p>
            <div class=\"details\">
                " . ($propertyTitle ? "<p><strong>Property:</strong> {$propertyTitle}</p>" : "") . "
                " . ($status ? "<p><strong>Status:</strong> {$status}</p>" : "") . "
                <p><strong>Date & Time:</strong> {$decisionDate}</p>
            </div>
            <p style=\"text-align:center;\"><a href=\"{$actionLink}\" class=\"btn\">View Details</a></p>
        </div>
        <div class=\"footer\">
            <p>This is an automated notification from {$siteName}. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>";
}

// Upload file
function uploadFile($file, $destination, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_type = $file['type'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Check file type
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Check file size (5MB max)
    if ($file_size > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    // Generate unique filename
    $new_file_name = uniqid() . '.' . $file_ext;
    $upload_path = $destination . $new_file_name;
    
    // Create directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return ['success' => true, 'filename' => $new_file_name, 'path' => $upload_path];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

// Pagination helper
function paginate($total_items, $items_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'has_next' => $current_page < $total_pages,
        'has_prev' => $current_page > 1
    ];
}

// Render a simple email template from either admin settings or file.
// Admins can edit templates via the settings screen; those values are stored
// under the key "tpl_<name>" in the "templates" group. If a setting exists
// it takes priority. Otherwise we load the corresponding file from
// templates/emails/<name>.html.
function renderEmailTemplate($name, $vars = []) {
    // look for override in settings
    $settingKey = 'tpl_' . $name;
    if (function_exists('settings_get')) {
        $override = settings_get($settingKey, null, 'templates');
        if ($override !== null && $override !== '') {
            $content = $override;
        } else {
            $tplPath = __DIR__ . '/../templates/emails/' . $name . '.html';
            if (!file_exists($tplPath)) {
                error_log("Email template not found: {$tplPath}");
                return '';
            }
            $content = file_get_contents($tplPath);
        }
    } else {
        $tplPath = __DIR__ . '/../templates/emails/' . $name . '.html';
        if (!file_exists($tplPath)) {
            error_log("Email template not found: {$tplPath}");
            return '';
        }
        $content = file_get_contents($tplPath);
    }

    foreach ($vars as $k => $v) {
        $content = str_replace('{{' . $k . '}}', $v, $content);
    }
    return $content;
}

// Convenience: render template and send
function sendEmailTemplate($to, $subject, $templateName, $vars = []) {
    $body = renderEmailTemplate($templateName, $vars);
    if ($body === '') {
        $body = buildDefaultEmailBody($subject, $vars);
    }
    return sendEmail($to, $subject, $body);
}

// Get dashboard statistics
function getDashboardStats($user_role, $user_id) {
    global $db;
    $stats = [];
    
    switch ($user_role) {
        case 'admin':
            $stats['total_users'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM users"))['total'];
            $stats['total_properties'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM properties"))['total'];
            $stats['active_rentals'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM rental_agreements WHERE status = 'active'"))['total'];
            $stats['pending_requests'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM rental_agreements WHERE status = 'pending'"))['total'];
            break;
            
        case 'owner':
            $stats['total_properties'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM properties WHERE owner_id = ?"), [$user_id])['total'];
            $stats['available_properties'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM properties WHERE owner_id = ? AND status = 'available'"), [$user_id])['total'];
            $stats['active_rentals'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM rental_agreements WHERE owner_id = ? AND status = 'active'"), [$user_id])['total'];
            $stats['pending_requests'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM rental_agreements WHERE owner_id = ? AND status = 'pending'"), [$user_id])['total'];
            break;
            
        case 'tenant':
            $stats['active_rentals'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM rental_agreements WHERE tenant_id = ? AND status = 'active'"), [$user_id])['total'];
            $stats['pending_requests'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM rental_agreements WHERE tenant_id = ? AND status = 'pending'"), [$user_id])['total'];
            $stats['total_payments'] = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM payments WHERE tenant_id = ? AND status = 'completed'"), [$user_id])['total'];
            break;
    }
    
    return $stats;
}

// Advance Payment Functions

// Generate unique reference code for advance payment
function generateAdvancePaymentReference() {
    do {
        $reference = 'ADV' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        if (!advancePaymentReferenceExists($reference)) {
            return $reference;
        }
    } while (true);
}

// Check if reference code already exists
function advancePaymentReferenceExists($reference) {
    global $db;
    $sql = "SELECT payment_id FROM advance_payments WHERE reference_code = ?";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$reference]);
    return $result !== false;
}

// Create advance payment record
function createAdvancePayment($tenant_id, $property_id, $amount) {
    global $db;

    $reference_code = generateAdvancePaymentReference();

    $sql = "INSERT INTO advance_payments (tenant_id, property_id, amount, reference_code, status) 
            VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $db->prepare($sql);

    if ($db->execute($stmt, [$tenant_id, $property_id, $amount, $reference_code])) {
        return $db->lastInsertId();
    }

    return false;
}

// Process advance payment (simulate payment)
function processAdvancePayment($payment_id) {
    global $db;

    // Get payment details
    $sql = "SELECT * FROM advance_payments WHERE payment_id = ?";
    $stmt = $db->prepare($sql);
    $payment = $db->getSingle($stmt, [$payment_id]);

    if (!$payment) {
        return ['success' => false, 'message' => 'Payment not found'];
    }

    if ($payment['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Payment already processed'];
    }

    // Simulate payment processing (80% success rate for demo)
    $success = (mt_rand(1, 10) <= 8); // 80% success rate

    $new_status = $success ? 'paid' : 'failed';
    $payment_date = $success ? date('Y-m-d H:i:s') : null;

    // Update payment status
    $sql = "UPDATE advance_payments SET status = ?, payment_date = ? WHERE payment_id = ?";
    $stmt = $db->prepare($sql);
    $result = $db->execute($stmt, [$new_status, $payment_date, $payment_id]);

    if ($result) {
        return [
            'success' => true,
            'status' => $new_status,
            'message' => $success ? 'Payment processed successfully' : 'Payment failed'
        ];
    }

    return ['success' => false, 'message' => 'Failed to update payment status'];
}

// Get advance payment details
function getAdvancePayment($payment_id) {
    global $db;
    $sql = "SELECT ap.*, u.full_name as tenant_name, p.property_name, p.monthly_rent
            FROM advance_payments ap
            JOIN users u ON ap.tenant_id = u.user_id
            JOIN properties p ON ap.property_id = p.property_id
            WHERE ap.payment_id = ?";
    $stmt = $db->prepare($sql);
    return $db->getSingle($stmt, [$payment_id]);
}

// Get advance payments for a tenant
function getTenantAdvancePayments($tenant_id) {
    global $db;
    $sql = "SELECT ap.*, p.property_name
            FROM advance_payments ap
            JOIN properties p ON ap.property_id = p.property_id
            WHERE ap.tenant_id = ?
            ORDER BY ap.created_at DESC";
    $stmt = $db->prepare($sql);
    return $db->getMultiple($stmt, [$tenant_id]);
}

// Check if tenant has pending advance payment for a property
function hasPendingAdvancePayment($tenant_id, $property_id) {
    global $db;
    $sql = "SELECT payment_id FROM advance_payments 
            WHERE tenant_id = ? AND property_id = ? AND status = 'pending'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$tenant_id, $property_id]);
    return $result !== false;
}

?>