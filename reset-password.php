<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$title = "Reset Password - Aksum Rental System";

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($token)) {
        $error = "Invalid reset token";
    } elseif (empty($password)) {
        $error = "Please enter a new password";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        // Validate token and update password
        $tokenHash = hash('sha256', $token);
        $sql = "SELECT prt.*, u.full_name, u.status, u.is_verified FROM password_reset_tokens prt
                JOIN users u ON prt.user_id = u.user_id
                WHERE prt.token = ? AND prt.used = FALSE AND prt.expires_at > NOW()";
        $stmt = $db->prepare($sql);
        $tokenData = $db->getSingle($stmt, [$tokenHash]);

        if ($tokenData) {
            // Check user status
            if ($tokenData['status'] !== 'active') {
                error_log("RESET ERROR: Attempt to reset password for non-active user: " . $tokenData['user_id']);
                $error = "Your account is not active. Please contact support.";
            } else {
                // Update password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateSql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                $updateStmt = $db->prepare($updateSql);
                $success = $db->execute($updateStmt, [$hashedPassword, $tokenData['user_id']]);

                if ($success) {
                    // Mark token as used
                    $markUsedSql = "UPDATE password_reset_tokens SET used = TRUE WHERE token_id = ?";
                    $markUsedStmt = $db->prepare($markUsedSql);
                    $db->execute($markUsedStmt, [$tokenData['token_id']]);

                    // Create success notification
                    $notificationSql = "INSERT INTO notifications (user_id, title, message, type)
                                      VALUES (?, 'Password Changed', 'Your password has been successfully changed.', 'success')";
                    $notificationStmt = $db->prepare($notificationSql);
                    $db->execute($notificationStmt, [$tokenData['user_id']]);

                    error_log("RESET SUCCESS: Password reset for user_id: " . $tokenData['user_id']);
                    $message = "Password reset successful! You can now login with your new password.";
                } else {
                    error_log("RESET ERROR: Failed to update password for user_id: " . $tokenData['user_id']);
                    $error = "Failed to update password. Please try again.";
                }
            }
        } else {
            error_log("RESET ERROR: Invalid or expired token attempt: " . $token);
            $error = "Invalid or expired reset token. Please request a new password reset.";
        }
    }
} else {
    // Check if token is provided in URL
    $token = $_GET['token'] ?? '';

    if (!empty($token)) {
        // Validate token
        $tokenHash = hash('sha256', $token);
        $sql = "SELECT prt.*, u.full_name, u.status FROM password_reset_tokens prt
                JOIN users u ON prt.user_id = u.user_id
                WHERE prt.token = ? AND prt.used = FALSE AND prt.expires_at > NOW()";
        $stmt = $db->prepare($sql);
        $tokenData = $db->getSingle($stmt, [$tokenHash]);

        if (!$tokenData) {
            $error = "This reset link is invalid or has expired. Please request a new one.";
        } elseif ($tokenData['status'] !== 'active') {
            $error = "Your account is not active. Please contact support.";
        }
    } else {
        $error = "Reset token is required to access this page.";
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
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
        .btn-toggle-pw {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding-right: 1rem;
        }
        .btn-toggle-pw:hover {
            color: #64748b;
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
        }
    </style>
</head>
<body class="auth-container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-lock-open"></i>
                </div>
                <h1 class="auth-title"><?php echo __('reset_password'); ?></h1>
                <p class="auth-subtitle"><?php echo __('enter_new_password_subtitle'); ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #f0fdf4; color: #166534; border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary w-100"><?php echo __('go_to_login'); ?></a>
                    </div>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #fef2f2; color: #991b1b; border-radius: 10px;">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <div class="mt-3">
                        <a href="forgot-password.php" class="btn btn-outline-primary btn-sm w-100"><?php echo __('request_new_reset'); ?></a>
                    </div>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($message) && empty($error) || (!empty($error) && !str_contains($error, 'Invalid'))): ?>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo __('new_password'); ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password"
                                   name="password" required placeholder="<?php echo __('enter_new_password'); ?>" minlength="8"
                                   autocorrect="off" autocapitalize="off" spellcheck="false">
                            <button class="btn-toggle-pw" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text small"><?php echo __('password_min_length_hint'); ?></div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label"><?php echo __('confirm_new_password'); ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="confirm_password"
                                   name="confirm_password" required placeholder="<?php echo __('confirm_new_password'); ?>" minlength="8"
                                   autocorrect="off" autocapitalize="off" spellcheck="false">
                            <button class="btn-toggle-pw" type="button" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?php echo __('reset_password'); ?>
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <a href="login.php" class="text-primary fw-bold text-decoration-none small"><?php echo __('back_to_login'); ?></a>
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
    <script>
        // Toggle password visibility
        function setupPasswordToggle(btnId, inputId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            if (!btn || !input) return;
            
            btn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleConfirmPassword', 'confirm_password');

        // Password confirmation validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.querySelector('button[type="submit"]');

        if (password && confirmPassword) {
            function validatePasswords() {
                if (password.value !== confirmPassword.value && confirmPassword.value !== '') {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }

            password.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        }
    </script>
</body>
</html>