<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$title = "Email Verification - Aksum House Rental System";

$message = "";
$message_type = "info";

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);

    // Debug logging
    error_log("[VERIFY] Received token: " . substr($token, 0, 10) . "...");

    // Check if token exists and is not expired
    $sql = "SELECT user_id, full_name, email, role, token_expires_at, is_verified
            FROM users
            WHERE verification_token = ? AND is_verified = 0";

    $stmt = $db->prepare($sql);
    $user = $db->getSingle($stmt, [$token]);

    error_log("[VERIFY] User found: " . ($user ? "YES" : "NO"));
    if ($user) {
        error_log("[VERIFY] User: " . $user['email'] . ", expires: " . $user['token_expires_at']);
    }

    if ($user) {
        // Check if token is expired
        $now = new DateTime();
        $expires_at = new DateTime($user['token_expires_at']);

        if ($now > $expires_at) {
            $message = "This verification link has expired. Please request a new verification email.\n ይህ የማረጋገጫ አገናኝ የተሳካ አይደለም። እባክዎ አዲስ የማረጋገጫ ኢሜል ይጠይቁ።";
            $message_type = "danger";
        } else {
            // Update user as verified
            $status_update = ($user['role'] === 'tenant') ? ", status = 'active'" : "";
            $update_sql = "UPDATE users SET is_verified = 1{$status_update}, verification_token = NULL, token_expires_at = NULL WHERE user_id = ?";
            $update_stmt = $db->prepare($update_sql);

            if ($db->execute($update_stmt, [$user['user_id']])) {
                // Log activity
                try {
                    $session->logActivity($user['user_id'], 'email_verified', 'users', $user['user_id']);
                } catch (Exception $e) {
                    // Ignore logging errors
                }

                // Send success notification
                try {
                    // English notification
                    $notification_sql_en = "INSERT INTO notifications (user_id, title, message, type)
                                        VALUES (?, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success')";
                    $notification_stmt_en = $db->prepare($notification_sql_en);
                    $db->execute($notification_stmt_en, [$user['user_id']]);

                    // Amharic notification
                    $notification_sql_am = "INSERT INTO notifications (user_id, title, message, type)
                                        VALUES (?, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success')";
                    $notification_stmt_am = $db->prepare($notification_sql_am);
                    $db->execute($notification_stmt_am, [$user['user_id']]);
                } catch (Exception $e) {
                    // Ignore notification errors
                }

                $message = "Your email has been successfully verified! You can now log in to your account.\n ኢሜልዎ በትክክል ተረጋግጧል! አሁን ወደ መለያዎ መግባት ይችላሉ።";
                $message_type = "success";
            } else {
                $message = "Verification failed. Please try again or contact support.\n ማረጋገጫ አልተሳካም። እባክዎ ደግመው ይሞክሩ ወይም ደጋግሞ ያግኙ ድጋፍ።";
                $message_type = "danger";
            }
        }
    } else {
        $message = "Invalid verification token. Please check your email for the correct link.\n ልክ ያልሆነ የማረጋገጫ ማስመሰያ እባክዎ ለትክክለኛው አገናኝ ኢሜልዎን ያረጋግጡ";
        $message_type = "danger";
    }
} else {
    $message = "No verification token provided.\n ምንም የማረጋገጫ ማስመሰያ አልተሰጠም።";
    $message_type = "warning";
}
?>

<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/favicon.ico">
    <style>
        :root {
            color-scheme: light;
            font-family: 'Inter', 'Roboto', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        html, body {
            min-height: 100%;
            margin: 0;
            padding: 0;
        }
        body.auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem 1rem;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 480px;
            display: flex;
            flex-direction: column;
            margin: auto;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            width: 100%;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        .verification-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }
        .verification-icon.success { background: #f0fdf4; color: #166534; }
        .verification-icon.danger { background: #fef2f2; color: #991b1b; }
        .verification-icon.warning { background: #fffbeb; color: #92400e; }
        .verification-icon.info { background: #eff6ff; color: #1e40af; }

        .auth-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .btn-primary {
            background: #2563eb;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        @media (max-height: 750px) {
            body.auth-container { padding: 0.5rem; }
            .auth-card { padding: 1.5rem; }
            .auth-header { margin-bottom: 1.25rem; }
            .verification-icon { width: 54px; height: 54px; font-size: 1.5rem; margin-bottom: 1rem; }
            .auth-title { font-size: 1.4rem; }
            .auth-footer { margin-top: 1rem; }
        }

        @media (max-width: 480px) {
            body.auth-container {
                padding: 0;
                background: #ffffff;
                align-items: center;
                overflow: hidden;
            }
            .auth-wrapper {
                height: 100vh;
                max-width: 100%;
                margin: 0;
                display: flex;
                flex-direction: column;
            }
            .auth-card {
                border-radius: 0;
                box-shadow: none;
                border: none;
                padding: 1.5rem;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .auth-footer {
                margin-top: 0;
                padding: 1rem;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body class="auth-container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="text-center">
                <div class="auth-header">
                    <?php if ($message_type === 'success'): ?>
                        <div class="verification-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    <?php elseif ($message_type === 'danger'): ?>
                        <div class="verification-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    <?php elseif ($message_type === 'warning'): ?>
                        <div class="verification-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    <?php else: ?>
                        <div class="verification-icon info">
                            <i class="fas fa-info-circle"></i>
                        </div>
                    <?php endif; ?>

                    <h1 class="auth-title"><?php echo __('email_verification'); ?></h1>
                </div>

                <div class="alert alert-<?php echo $message_type; ?> py-3 small border-0 mb-4" style="border-radius: 12px; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($message)); ?>
                </div>

                <div class="d-grid">
                    <?php if ($message_type === 'success'): ?>
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i> <?php echo __('login_now'); ?>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> <?php echo __('back_to_login'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="auth-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>