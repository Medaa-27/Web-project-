<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$title = "Forgot Password - Aksum House Rental System";

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email exists
        $sql = "SELECT user_id, full_name, status, is_verified FROM users WHERE email = ?";
        $stmt = $db->prepare($sql);
        $user = $db->getSingle($stmt, [$email]);

        if ($user) {
            // Check status
            if ($user['status'] !== 'active') {
                $error = "Your account is not active. Please contact support.";
            } elseif ($user['is_verified'] == 0) {
                $error = "Please verify your email address before resetting your password.";
            } else {
                // Check if there's already an active reset token for this user
                $checkTokenSql = "SELECT token_id FROM password_reset_tokens
                                 WHERE user_id = ? AND used = FALSE AND expires_at > NOW()";
                $checkTokenStmt = $db->prepare($checkTokenSql);
                $existingToken = $db->getSingle($checkTokenStmt, [$user['user_id']]);

                if ($existingToken) {
                    // Delete existing token and create new one
                    $deleteSql = "DELETE FROM password_reset_tokens WHERE user_id = ? AND used = FALSE";
                    $deleteStmt = $db->prepare($deleteSql);
                    $db->execute($deleteStmt, [$user['user_id']]);
                }

                // Generate secure reset token
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

                // Insert token into database
                $insertSql = "INSERT INTO password_reset_tokens (user_id, token, email, expires_at)
                             VALUES (?, ?, ?, ?)";
                $insertStmt = $db->prepare($insertSql);
                $success = $db->execute($insertStmt, [$user['user_id'], $tokenHash, $email, $expires]);

                if ($success) {
                    // Send password reset email using template
                    $resetLink = rtrim(SITE_URL, '/') . "/reset-password.php?token=" . $token;
                    error_log("RESET DEBUG: Generated link for {$email}: " . $resetLink);
                    
                    $subject = "Password Reset Request - " . SITE_NAME;
                    $emailSent = sendEmailTemplate($email, $subject, 'password_reset', [
                        'full_name' => $user['full_name'],
                        'reset_link' => $resetLink,
                        'site_name' => SITE_NAME,
                        'expires_in' => '1 hour',
                        'year' => date('Y')
                    ]);

                    if ($emailSent) {
                        $message = "Password reset instructions have been sent to your email address. Please check your inbox and spam folder.";
                    } else {
                        error_log("RESET ERROR: Failed to send email to {$email}");
                        $error = "Failed to send password reset email. Please try again later or contact support.";
                    }
                } else {
                    error_log("RESET ERROR: Failed to insert token for user_id: " . $user['user_id']);
                    $error = "Failed to create password reset request. Please try again.";
                }
            }
        } else {
            // Don't reveal if email exists or not for security
            $message = "If your email address is registered with us, you will receive password reset instructions.";
        }
    }
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
            padding-top: 1.5rem;
        }
        .back-link {
            position: absolute;
            left: -0.5rem;
            top: -0.5rem;
            color: #64748b;
            text-decoration: none;
            font-size: 1rem;
            padding: 0.5rem;
            transition: all 0.2s;
            z-index: 10;
        }
        .back-link:hover {
            color: #2563eb;
            transform: translateX(-3px);
        }
        .auth-logo {
            width: 54px;
            height: 54px;
            background: #2563eb;
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        .auth-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        .auth-subtitle {
            color: #64748b;
            font-size: 0.9rem;
        }
        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }
        .input-group {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
        }
        .input-group:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: #ffffff;
        }
        .input-group-text {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding-left: 1rem;
        }
        .form-control {
            background: transparent;
            border: none;
            padding: 0.75rem 1rem 0.75rem 0.5rem;
            font-size: 0.95rem;
            color: #1e293b;
        }
        .form-control:focus {
            background: transparent;
            box-shadow: none;
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
        .btn-outline-primary {
            border-radius: 12px;
            padding: 0.7rem;
            font-weight: 600;
            border-width: 1.5px;
            font-size: 0.9rem;
        }
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: #64748b;
            font-size: 0.85rem;
        }
        .auth-footer a {
            color: #1e293b;
            text-decoration: none;
            font-weight: 600;
            margin: 0 0.5rem;
        }
        .auth-footer a:hover {
            color: #2563eb;
        }

        @media (max-height: 750px) {
            body.auth-container {
                padding: 0.5rem;
            }
            .auth-card {
                padding: 1.25rem 1.5rem;
            }
            .auth-header {
                margin-bottom: 0.75rem;
                padding-top: 1rem;
            }
            .auth-logo {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }
            .auth-title {
                font-size: 1.3rem;
            }
            .auth-subtitle {
                font-size: 0.8rem;
                margin-bottom: 0.5rem;
            }
            .mb-3 {
                margin-bottom: 0.5rem !important;
            }
            .btn-primary {
                padding: 0.6rem;
            }
            .auth-footer {
                margin-top: 1rem;
            }
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
                padding: 1.25rem;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                overflow-y: auto;
            }
            .auth-header {
                padding-top: 2rem;
            }
            .auth-footer {
                margin-top: 0;
                padding: 1rem;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                font-size: 0.75rem;
            }
            .back-link {
                left: 0.5rem;
                top: 0.5rem;
            }
        }
    </style>
</head>
<body class="auth-container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <a href="login.php" class="back-link" title="<?php echo __('back_to_login'); ?>">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="auth-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="auth-title"><?php echo __('forgot_password'); ?></h1>
                <p class="auth-subtitle"><?php echo __('reset_password_subtitle'); ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #f0fdf4; color: #166534; border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary w-100"><?php echo __('back_to_login'); ?></a>
                    </div>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #fef2f2; color: #991b1b; border-radius: 10px;">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($message)): ?>
                <p class="text-muted small text-center mb-4">
                    <?php echo __('enter_email_for_instructions'); ?>
                </p>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="email" class="form-label"><?php echo __('email_address'); ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email"
                                   name="email" required placeholder="<?php echo __('enter_email'); ?>"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i><?php echo __('send_reset_instructions'); ?>
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0 small text-muted"><?php echo __('remember_password'); ?> 
                        <a href="login.php" class="text-primary fw-bold text-decoration-none"><?php echo __('sign_in'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="auth-footer">
            <p class="mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
            <div>
                <a href="public/about.php"><?php echo __('about'); ?></a>
                <a href="public/terms.php"><?php echo __('terms'); ?></a>
                <a href="public/privacy.php"><?php echo __('privacy'); ?></a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>