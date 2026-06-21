<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/login_attempts.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectUrl);
    exit;
}

$title = __('login_title');

// Ensure force_password_change column exists - critical for login query
try {
    if (!$db->columnExists('users', 'force_password_change')) {
        $db->getConnection()->exec("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0");
    }
} catch (Exception $e) {
    // Column might already exist or table might be locked, ignore
}

// Redirect if already logged in
if ($session->isLoggedIn()) {
    $session->redirectToDashboard();
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Validate
    if (empty($email) || empty($password)) {
        $error = __('login_error_missing_credentials');
    } else {
        // Check if user is currently locked out
        if (isUserLockedOut($db, $email)) {
            $remainingTime = getRemainingLockoutTime($db, $email);
            $error = sprintf(__('login_error_locked_out'), $remainingTime);
        } else {
            // Check user - now using status field instead of is_active
            $sql = "SELECT user_id, full_name, email, password_hash, role, status, is_verified, force_password_change
                FROM users
                WHERE email = ?";
            $stmt = $db->prepare($sql);
            $user = $db->getSingle($stmt, [$email]);

            if ($user) {
                // Check if account is active (not suspended)
                if ($user['status'] === 'suspended') {
                    $error = __('login_error_suspended');
                } elseif ($user['status'] === 'pending') {
                    $error = __('login_error_pending_approval');
                } elseif ($user['status'] !== 'active') {
                    $error = __('login_error_not_active');
                } elseif ($user['is_verified'] == 0) {
                    $error = __('login_error_verify_email');
                } elseif (password_verify($password, $user['password_hash'])) {
                    // Login successful - reset login attempts
                    resetLoginAttempts($db, $email);

                    if ($session->login($user['user_id'], $user['role'], $remember)) {
                        // Create welcome notification
                        try {
                            $notification_sql = "INSERT INTO notifications (user_id, title, message, type)
                                                VALUES (?, 'Welcome Back!', 'You have successfully logged in.', 'success')";
                            $notification_stmt = $db->prepare($notification_sql);
                            $db->execute($notification_stmt, [$user['user_id']]);
                        } catch (Exception $e) {
                            // Notification table might not exist, ignore error
                        }

                        // Check for redirect URL
                        $redirect = $_REQUEST['redirect'] ?? '';
                        
                        // Force password change if required
                        if ($user['force_password_change'] == 1) {
                            $_SESSION['force_change'] = true;
                            header("Location: change-password.php");
                            exit;
                        }

                        if (!empty($redirect)) {
                            header("Location: " . $redirect);
                            exit;
                        }

                        // Redirect to dashboard
                        $session->redirectToDashboard();
                    } else {
                        $error = __('login_error_failed');
                    }
                } else {
                    // Password incorrect - record failed attempt
                    $recorded = recordFailedLoginAttempt($db, $email);

                    if (!$recorded) {
                        $error = __('login_error_invalid_credentials');
                    } elseif (isUserLockedOut($db, $email)) {
                        $remainingTime = getRemainingLockoutTime($db, $email);
                        $error = sprintf(__('login_error_locked_out'), $remainingTime);
                    } else {
                        $remainingAttempts = getRemainingAttempts($db, $email);
                        $attemptText = $remainingAttempts === 1 ? __('attempt') : __('attempts');
                        $error = sprintf(__('login_error_remaining_attempts'), $remainingAttempts, $attemptText);
                    }
                }
            } else {
                // User not found - keep response generic to avoid email enumeration
                $error = __('login_error_invalid_credentials');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(get_current_language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            padding-top: 1.5rem; /* Space for back link and lang switcher */
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
        .lang-switcher {
            position: absolute;
            right: -0.5rem;
            top: -0.5rem;
            z-index: 10;
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
            margin-top: 0.75rem;
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
        .auth-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
        }
        .auth-options a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-options a:hover {
            text-decoration: underline;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.25rem 0;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider:not(:empty)::before { margin-right: 1rem; }
        .divider:not(:empty)::after { margin-left: 1rem; }

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
        .hover-primary:hover {
            color: #2563eb !important;
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
                margin-top: 0.25rem;
            }
            .divider {
                margin: 0.75rem 0;
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
                overflow: hidden; /* Prevent body scroll */
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
                overflow-y: auto; /* Allow card to scroll if content is too tall, but try to avoid */
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
            .lang-switcher {
                right: 0.5rem;
                top: 0.5rem;
            }
        }
    </style>
</head>
<body class="auth-container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <a href="public/index.php" class="back-link" title="<?php echo __('back_to_home'); ?>">
                    <i class="fas fa-arrow-left"></i>
                </a>
                
                <div class="lang-switcher">
                    <form method="get" action="login.php" id="langForm">
                        <?php if (isset($_GET['redirect'])): ?>
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                        <?php endif; ?>
                        <select name="lang" class="form-select form-select-sm border-0 bg-light rounded-pill px-3" onchange="this.form.submit();" style="cursor: pointer;">
                            <option value="en" <?php echo get_current_language() === 'en' ? 'selected' : ''; ?>>EN</option>
                            <option value="am" <?php echo get_current_language() === 'am' ? 'selected' : ''; ?>>AM</option>
                            <option value="ti" <?php echo get_current_language() === 'ti' ? 'selected' : ''; ?>>TI</option>
                        </select>
                    </form>
                </div>

                <div class="auth-logo">
                    <i class="fas fa-home"></i>
                </div>
                <h1 class="auth-title"><?php echo __('login_title'); ?></h1>
                <p class="auth-subtitle"><?php echo __('sign_in_to_account'); ?></p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #fef2f2; color: #991b1b; border-radius: 10px;">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #f0fdf4; color: #166534; border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                    if ($_GET['success'] == 'registered') echo __('registration_successful');
                    elseif ($_GET['success'] == 'reset') echo __('reset_successful');
                    ?>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? $_POST['redirect'] ?? ''); ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label"><?php echo __('email_address'); ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required 
                               placeholder="<?php echo __('enter_email'); ?>" value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label"><?php echo __('password'); ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required 
                               placeholder="<?php echo __('enter_password'); ?>">
                        <button class="btn-toggle-pw" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="auth-options">
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" style="cursor: pointer;">
                        <label class="form-check-label" for="remember" style="cursor: pointer;"><?php echo __('remember_me'); ?></label>
                    </div>
                    <a href="forgot-password.php"><?php echo __('forgot_password'); ?></a>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <?php echo __('sign_in'); ?>
                    </button>
                </div>
            </form>
            
            <div class="divider"><?php echo __('dont_have_account'); ?></div>
            
            <div class="d-grid mb-4">
                <a href="register.php" class="btn btn-outline-primary">
                    <?php echo __('register_create_account'); ?>
                </a>
            </div>

            <div class="text-center small text-muted border-top pt-3 mt-2">
                <p class="mb-2">&copy; <?php echo date('Y'); ?> <?php echo __('site_name'); ?></p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="public/about.php" class="text-decoration-none text-muted hover-primary"><?php echo __('about'); ?></a>
                    <a href="public/terms.php" class="text-decoration-none text-muted hover-primary"><?php echo __('terms'); ?></a>
                    <a href="public/privacy.php" class="text-decoration-none text-muted hover-primary"><?php echo __('privacy'); ?></a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        // Clear sensitive inputs when alert is dismissed (for security)
        function clearLoginInputs() {
            var email = document.getElementById('email');
            var password = document.getElementById('password');
            var remember = document.getElementById('remember');

            if (email) {
                email.value = '';
                email.autocomplete = 'off';
                email.blur();
            }
            if (password) {
                password.value = '';
                password.autocomplete = 'new-password';
                password.blur();
            }
            if (remember) remember.checked = false;
        }

        // fallback for dismissible alerts if Bootstrap doesn't handle click
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.alert-dismissible .btn-close').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var al = btn.closest('.alert');
                    if (al) {
                        al.classList.remove('show');
                        setTimeout(function(){ al.remove(); }, 350);
                        clearLoginInputs();
                    }
                });
            });

            // Also auto-clear the fields after a short delay for security.
            setTimeout(clearLoginInputs, 120000); // 2 minutes
        });
    </script>
</body>
</html>