<?php

require_once 'includes/config.php';

$title = t('register_title');



// Redirect if already logged in

if ($session->isLoggedIn()) {

    $session->redirectToDashboard();

}



// Get registration type

$type = isset($_GET['type']) && in_array($_GET['type'], ['tenant', 'owner']) ? $_GET['type'] : 'tenant';



// Process registration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);

    $email = trim($_POST['email']);

    $phone = trim($_POST['phone']);

    $id_number = trim($_POST['id_number']);

    $address = trim($_POST['address']);

    $password = $_POST['password'];

    $confirm_password = $_POST['confirm_password'];

    $role = $_POST['role'];

    $terms = isset($_POST['terms']);
    
    // begin validation/errors array early
    $errors = [];
    
    // handle ID / Business License scan upload
    $id_image_path = null;
    if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['id_image'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('id_', true) . '.' . $ext;
        $dest = 'assets/uploads/ids/' . $filename;
        if (!is_dir(dirname($dest))) {
            @mkdir(dirname($dest), 0755, true);
        }
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $id_image_path = $dest;
        } else {
            $errors[] = t('failed_to_save_uploaded_id_image');
        }
    }
    // Validation rules
    if (empty($full_name)) {
        $errors[] = t('full_name_required');
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $errors[] = "Full name must contain only letters and spaces";
    }

    if (empty($email)) {
        $errors[] = t('email_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if (empty($phone)) {
        $errors[] = t('phone_number_required');
    } elseif (!preg_match('/^(09|07)\d{8}$/', $phone)) {
        $errors[] = "Phone must be 10 digits starting with 09 or 07";
    }

    if (empty($id_image_path)) $errors[] = t('upload_id_or_license_required');

    if (empty($password)) $errors[] = t('password_required');

    if ($password !== $confirm_password) $errors[] = t('passwords_do_not_match');

    if (strlen($password) < 8) $errors[] = t('password_minimum_length');

    if (!$terms) $errors[] = t('agree_terms_required');

    

    // Check if email exists

    $check_sql = "SELECT user_id FROM users WHERE email = ?";

    $check_stmt = $db->prepare($check_sql);

    $exists = $db->getSingle($check_stmt, [$email]);

    

    if ($exists) $errors[] = "Email already registered";

    

    if (empty($errors)) {

        // Hash password

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        

        // Ensure verification columns exist (helps if schema wasn't updated)
        try {
            $checkSql = "SHOW COLUMNS FROM users LIKE 'verification_token'";
            $checkStmt = $db->prepare($checkSql);
            $exists = $db->getSingle($checkStmt);
            if (!$exists) {
                $db->execute($db->prepare("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL AFTER is_verified"));
            }

            $checkSql = "SHOW COLUMNS FROM users LIKE 'token_expires_at'";
            $checkStmt = $db->prepare($checkSql);
            $exists = $db->getSingle($checkStmt);
            if (!$exists) {
                $db->execute($db->prepare("ALTER TABLE users ADD COLUMN token_expires_at DATETIME NULL AFTER verification_token"));
            }

            $checkSql = "SHOW COLUMNS FROM users LIKE 'id_image'";
            $checkStmt = $db->prepare($checkSql);
            $exists = $db->getSingle($checkStmt);
            if (!$exists) {
                $db->execute($db->prepare("ALTER TABLE users ADD COLUMN id_image VARCHAR(255) NULL AFTER address"));
            }
        } catch (Exception $e) {
            // Ignore if alter fails (e.g., lacking permissions). We'll still attempt registration.
        }

        

        // Generate verification token

        $verification_token = bin2hex(random_bytes(32));

        $token_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        

        // Set initial status based on role

        $initial_status = 'pending';  // All users start pending

        

        // Insert user

        $sql = "INSERT INTO users (full_name, email, phone, password_hash, role, id_number, address, id_image, status, verification_token, token_expires_at, created_at)

                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $db->prepare($sql);

        
        if ($db->execute($stmt, [$full_name, $email, $phone, $password_hash, $role, $id_number, $address, $id_image_path, $initial_status, $verification_token, $token_expires_at])) {

            $user_id = $db->lastInsertId();

            

            // Log activity (with error handling)

            try {

                $session->logActivity($user_id, 'register', 'users', $user_id);

            } catch (Exception $e) {

                // Activity logging failed, but registration was successful

                // Continue without logging to avoid blocking the registration

            }

            

            // Send welcome notification (with error handling)

            try {

                $notification_sql = "INSERT INTO notifications (user_id, title, message, type) 

                                    VALUES (?, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success')";

                $notification_stmt = $db->prepare($notification_sql);

                $db->execute($notification_stmt, [$user_id]);

            } catch (Exception $e) {

                // Notification failed, but registration was successful

                // Continue without notification to avoid blocking the registration

            }

            // Send email verification

            try {

                require_once 'includes/functions.php';

                $subject = "Verify Your Email - " . SITE_NAME;

                // Ensure the verification link uses an IP/hostname reachable from mobile devices
                $baseUrl = SITE_URL;
                // For local development, try to use the current request host
                if (isset($_SERVER['HTTP_HOST'])) {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    // If it's localhost or 127.0.0.1, try to get the local IP
                    if ($host === 'localhost' || $host === '127.0.0.1') {
                        // Get the local IP address
                        $localIP = getHostByName(getHostName());
                        if (!filter_var($localIP, FILTER_VALIDATE_IP)) {
                            // If local IP is not valid, use the configured SITE_URL
                            $baseUrl = SITE_URL;
                        } else {
                            $baseUrl = $protocol . '://' . $localIP . '/aksum-rental/';
                        }
                    } else {
                        $baseUrl = $protocol . '://' . $host . '/aksum-rental/';
                    }
                }
                $verification_link = rtrim($baseUrl, '/') . '/verify.php?token=' . $verification_token;

                sendEmailTemplate($email, $subject, 'email_verification', [

                    'full_name' => $full_name,

                    'verification_link' => $verification_link

                ]);

            } catch (Exception $e) {

                // ignore email errors

            }

            

            // instead of immediate redirect, show message and then use JS to forward
            $success = t('registration_successful');
            // later in HTML we will output a small script that navigates after a delay

        } else {

            $error = t('registration_failed');
            $dbError = $db->getLastError();
            if ($dbError) {
                $error .= " (" . htmlspecialchars($dbError) . ")";
            }

        }

    } else {

        $error = implode("<br>", $errors);

    }

}

?>



<!DOCTYPE html>

<html lang="en">

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
            max-width: 600px;
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
            padding: 2rem;
            width: 100%;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
            padding-top: 1rem;
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
            width: 50px;
            height: 50px;
            background: #2563eb;
            color: white;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        .auth-title {
            font-size: 1.5rem;
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
            border-radius: 10px;
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
            padding-left: 0.85rem;
        }
        .form-control {
            background: transparent;
            border: none;
            padding: 0.65rem 0.85rem 0.65rem 0.4rem;
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
            padding-right: 0.85rem;
        }
        .btn-primary {
            background: #2563eb;
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.15);
        }
        .btn-secondary, .btn-outline-primary {
            border-radius: 10px;
            padding: 0.7rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s;
        }
        .step-dot.active {
            width: 24px;
            border-radius: 4px;
            background: #2563eb;
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

        .invalid-feedback {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: none;
            width: 100%;
            color: #dc3545;
        }
        .is-invalid ~ .invalid-feedback,
        .input-group.is-invalid ~ .invalid-feedback,
        .has-validation .is-invalid ~ .invalid-feedback,
        .mb-3:has(.is-invalid) .invalid-feedback,
        .mb-4:has(.is-invalid) .invalid-feedback {
            display: block;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: #64748b;
            font-size: 0.8rem;
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
                width: 36px;
                height: 36px;
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }
            .auth-title {
                font-size: 1.25rem;
            }
            .auth-subtitle {
                font-size: 0.8rem;
                margin-bottom: 0.5rem;
            }
            .step-indicator {
                margin-bottom: 1rem;
            }
            .mb-3 {
                margin-bottom: 0.4rem !important;
            }
            .mb-4 {
                margin-bottom: 0.6rem !important;
            }
            .form-label {
                margin-bottom: 0.2rem;
                font-size: 0.8rem;
            }
            .form-control {
                padding: 0.5rem 0.75rem 0.5rem 0.35rem;
                font-size: 0.9rem;
            }
            .btn-primary, .btn-secondary, .btn-outline-primary {
                padding: 0.6rem;
                font-size: 0.85rem;
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
                overflow-y: auto; /* Allow card to scroll if content is too tall */
            }
            .auth-header {
                padding-top: 2rem;
            }
            .auth-footer {
                margin-top: 0;
                padding: 0.75rem 1rem;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                font-size: 0.8rem;
            }
            .auth-footer .badge {
                font-size: 0.75rem;
                padding: 0.4rem 0.8rem !important;
            }
            .auth-footer p {
                margin-bottom: 0.5rem !important;
            }
            .auth-footer .gap-2 {
                gap: 0.5rem !important;
                margin-bottom: 0.5rem !important;
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
                <a href="public/index.php" class="back-link" title="<?php echo t('back_to_home'); ?>">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="lang-switcher">
                    <form method="get" action="register.php" id="langForm">
                        <input type="hidden" name="type" value="<?php echo $type; ?>">
                        <select name="lang" class="form-select form-select-sm border-0 bg-light rounded-pill px-3" onchange="this.form.submit();" style="cursor: pointer;">
                            <option value="en" <?php echo get_current_language() === 'en' ? 'selected' : ''; ?>>EN</option>
                            <option value="am" <?php echo get_current_language() === 'am' ? 'selected' : ''; ?>>AM</option>
                            <option value="ti" <?php echo get_current_language() === 'ti' ? 'selected' : ''; ?>>TI</option>
                        </select>
                    </form>
                </div>

                <div class="auth-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="auth-title"><?php echo sprintf(t('create_account_as'), ucfirst($type)); ?></h1>
                <p class="auth-subtitle"><?php echo t('join_today'); ?></p>
            </div>

            <div class="step-indicator">
                <div class="step-dot active" data-step="1"></div>
                <div class="step-dot" data-step="2"></div>
                <div class="step-dot" data-step="3"></div>
                <div class="step-dot" data-step="4"></div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #fef2f2; color: #991b1b; border-radius: 10px;">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2 small border-0 mb-3" role="alert" style="background: #f0fdf4; color: #166534; border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close small" data-bs-dismiss="alert" style="padding: 0.75rem;"></button>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="role" value="<?php echo $type; ?>">

                <!-- step 1: basic info -->
                <div class="form-step" data-step="1">
                    <div class="mb-3">
                        <label for="full_name" class="form-label"><?php echo t('full_name'); ?> *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name" required 
                                   value="<?php echo $_POST['full_name'] ?? ''; ?>" placeholder="<?php echo t('enter_full_name'); ?>">
                            <div class="invalid-feedback"><?php echo t('error_full_name'); ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo t('email_address'); ?> *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="<?php echo t('enter_email'); ?>">
                            <div class="invalid-feedback"><?php echo t('error_email_gmail'); ?></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="form-label"><?php echo t('phone_number'); ?> *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone" required 
                                   value="<?php echo $_POST['phone'] ?? ''; ?>" placeholder="<?php echo t('enter_phone_number'); ?>">
                            <div class="invalid-feedback"><?php echo t('error_phone_10'); ?></div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-primary next-step"><?php echo t('next_step'); ?></button>
                    </div>
                </div>

                <!-- step 2: ID and address -->
                <div class="form-step d-none" data-step="2">
                    <div class="mb-3">
                        <label for="id_number" class="form-label"><?php echo t('id_number'); ?> (<?php echo t('optional'); ?>)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="id_number" name="id_number"
                                   value="<?php echo $_POST['id_number'] ?? ''; ?>" placeholder="<?php echo t('enter_id_number_optional'); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="id_image" class="form-label"><?php echo t('id_or_business_license'); ?> *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fas fa-file-upload"></i></span>
                            <input type="file" class="form-control" id="id_image" name="id_image" accept="image/*" required>
                            <div class="invalid-feedback"><?php echo t('error_id_image'); ?></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label"><?php echo t('address'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea class="form-control" id="address" name="address" rows="2" 
                                      placeholder="<?php echo t('enter_address'); ?>"><?php echo $_POST['address'] ?? ''; ?></textarea>
                        </div>
                        <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i><?php echo t('address_helper'); ?></small>
                    </div>

                    <div class="row g-2">
                        <div class="col-4">
                            <button type="button" class="btn btn-secondary w-100 prev-step"><?php echo t('back'); ?></button>
                        </div>
                        <div class="col-8">
                            <button type="button" class="btn btn-primary w-100 next-step"><?php echo t('next_step'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- step 3: password -->
                <div class="form-step d-none" data-step="3">
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo t('password'); ?> *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="<?php echo t('enter_password'); ?>">
                            <button class="btn-toggle-pw" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback"><?php echo t('error_password_length'); ?></div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?> *</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="<?php echo t('confirm_password_placeholder'); ?>">
                            <button class="btn-toggle-pw" type="button" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback"><?php echo t('error_password_match'); ?></div>
                    </div>

                    <div class="row g-2">
                        <div class="col-4">
                            <button type="button" class="btn btn-secondary w-100 prev-step"><?php echo t('back'); ?></button>
                        </div>
                        <div class="col-8">
                            <button type="button" class="btn btn-primary w-100 next-step"><?php echo t('next_step'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- step 4: terms & submit -->
                <div class="form-step d-none" data-step="4">
                    <div class="card bg-light border-0 rounded-3 p-3 mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required style="cursor: pointer;">
                            <label class="form-check-label small" for="terms" style="cursor: pointer;">
                                <?php echo t('i_agree_to'); ?> <a href="public/terms.php" target="_blank" class="text-primary fw-bold"><?php echo t('terms'); ?></a> 
                                <?php echo t('and_lower'); ?> <a href="public/privacy.php" target="_blank" class="text-primary fw-bold"><?php echo t('privacy'); ?></a>
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i><?php echo t('create_account'); ?>
                        </button>
                        <button type="button" class="btn btn-secondary prev-step"><?php echo t('back'); ?></button>
                    </div>
                </div>
            </form>

            <div class="divider"><?php echo t('already_have_account'); ?></div>
            
            <div class="d-grid mb-3">
                <a href="login.php" class="btn btn-outline-primary">
                    <?php echo t('sign_in'); ?>
                </a>
            </div>

            <div class="d-flex justify-content-center gap-2 mb-4">
                <a href="register.php?type=tenant" class="badge <?php echo $type == 'tenant' ? 'bg-primary' : 'bg-white border text-secondary'; ?> text-decoration-none px-3 py-2">
                    <?php echo t('tenant'); ?>
                </a>
                <a href="register.php?type=owner" class="badge <?php echo $type == 'owner' ? 'bg-primary' : 'bg-white border text-secondary'; ?> text-decoration-none px-3 py-2">
                    <?php echo t('owner'); ?>
                </a>
            </div>

            <div class="text-center small text-muted border-top pt-3 mt-2">
                <p class="mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="public/about.php" class="text-decoration-none text-muted hover-primary"><?php echo t('about'); ?></a>
                    <a href="public/terms.php" class="text-decoration-none text-muted hover-primary"><?php echo t('terms'); ?></a>
                    <a href="public/privacy.php" class="text-decoration-none text-muted hover-primary"><?php echo t('privacy'); ?></a>
                </div>
            </div>
        </div>
    </div>

    

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            function toggleVisibility(btnId, inputId) {
                $(`#${btnId}`).on('click', function(e) {
                    e.preventDefault();
                    const input = $(`#${inputId}`);
                    const icon = $(this).find('i');
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        input.attr('type', 'password');
                        icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });
            }

            toggleVisibility('togglePassword', 'password');
            toggleVisibility('toggleConfirmPassword', 'confirm_password');

            // Password strength check
            $('#password').on('input', function() {
                const password = $(this).val();
                let strength = 0;
                if (password.length >= 8) strength += 25;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
                if (password.match(/[0-9]/)) strength += 25;
                if (password.match(/[^a-zA-Z0-9]/)) strength += 25;
                
                const progressBar = $('#passwordStrength');
                progressBar.css('width', strength + '%');
                if (strength <= 25) progressBar.removeClass('bg-warning bg-success').addClass('bg-danger');
                else if (strength <= 50) progressBar.removeClass('bg-danger bg-success').addClass('bg-warning');
                else progressBar.removeClass('bg-danger bg-warning').addClass('bg-success');
            });

            // Real-time validation
            $('#full_name').on('input', function() {
                const regex = /^[A-Za-z\u1200-\u137F\s]{3,}$/;
                if (!regex.test($(this).val().trim())) {
                    $(this).addClass('is-invalid');
                    $(this).closest('.mb-3').find('.invalid-feedback').show();
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).closest('.mb-3').find('.invalid-feedback').hide();
                }
            });

            $('#email').on('input', function() {
                const regex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
                if (!regex.test($(this).val().trim())) {
                    $(this).addClass('is-invalid');
                    $(this).closest('.mb-3').find('.invalid-feedback').show();
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).closest('.mb-3').find('.invalid-feedback').hide();
                }
            });

            $('#phone').on('input', function() {
                // Remove non-numeric characters
                let val = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(val);
                
                const regex = /^0[79][0-9]{8}$/;
                if (!regex.test(val)) {
                    $(this).addClass('is-invalid');
                    $(this).closest('.mb-4').find('.invalid-feedback').show();
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).closest('.mb-4').find('.invalid-feedback').hide();
                }
            });

            $('#password').on('input', function() {
                if ($(this).val().length < 8) {
                    $(this).addClass('is-invalid');
                    $(this).closest('.mb-3').find('.invalid-feedback').show();
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).closest('.mb-3').find('.invalid-feedback').hide();
                }
            });

            $('#confirm_password').on('input', function() {
                if ($(this).val() !== $('#password').val()) {
                    $(this).addClass('is-invalid');
                    $(this).closest('.mb-4').find('.invalid-feedback').show();
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).closest('.mb-4').find('.invalid-feedback').hide();
                }
            });

            // Step navigation logic
            let currentStep = 1;
            function showStep(n) {
                $('.form-step').addClass('d-none');
                $(`.form-step[data-step="${n}"]`).removeClass('d-none');
                
                // Update dots
                $('.step-dot').removeClass('active');
                for(let i=1; i<=n; i++) {
                    $(`.step-dot[data-step="${i}"]`).addClass('active');
                }

                // Scroll to top of card on step change for mobile
                $('.auth-wrapper').animate({ scrollTop: 0 }, 'fast');
            }

            $('.next-step').on('click', function() {
                const stepDiv = $(`.form-step[data-step="${currentStep}"]`);
                let stepValid = true;

                // Step-specific custom validation
                if (currentStep === 1) {
                    const fullName = $('#full_name');
                    const email = $('#email');
                    const phone = $('#phone');
                    
                    // Reset invalid states
                    stepDiv.find('.is-invalid').removeClass('is-invalid');

                    if (!/^[A-Za-z\u1200-\u137F\s]{3,}$/.test(fullName.val().trim())) {
                        fullName.addClass('is-invalid');
                        stepValid = false;
                    }
                    
                    if (!/^[a-zA-Z0-9._%+-]+@gmail\.com$/.test(email.val().trim())) {
                        email.addClass('is-invalid');
                        stepValid = false;
                    }

                    if (!/^[0-9]{10}$/.test(phone.val().trim())) {
                        phone.addClass('is-invalid');
                        stepValid = false;
                    }
                } else if (currentStep === 3) {
                    const pwd = $('#password');
                    const conf = $('#confirm_password');
                    
                    if (pwd.val().length < 8) {
                        pwd.addClass('is-invalid');
                        pwd.closest('.mb-3').find('.invalid-feedback').show();
                        stepValid = false;
                    }
                    
                    if (pwd.val() !== conf.val()) {
                        conf.addClass('is-invalid');
                        conf.closest('.mb-4').find('.invalid-feedback').show();
                        stepValid = false;
                    }
                }

                // Native HTML5 validation check
                stepDiv.find('input[required], textarea[required], input[type="file"]').each(function() {
                    if (!this.checkValidity()) {
                        $(this).addClass('is-invalid');
                        $(this).closest('.mb-3, .mb-4').find('.invalid-feedback').show();
                        this.reportValidity();
                        stepValid = false;
                    }
                });

                if (stepValid && currentStep < 4) {
                    currentStep++;
                    showStep(currentStep);
                }
            });

            $('#registerForm').on('submit', function(e) {
                const terms = $('#terms');
                if (!terms.is(':checked')) {
                    e.preventDefault();
                    alert('<?php echo addslashes(t('error_terms')); ?>');
                    return false;
                }
            });

            $('.prev-step').on('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });

            // Handle success redirect
            <?php if (isset($success)): ?>
            setTimeout(function() {
                window.location.href = 'login.php?success=registered';
            }, 2500);
            <?php endif; ?>
        });
    </script>

</body>

</html>
