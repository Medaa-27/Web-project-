<?php
require_once '../includes/config.php';
$title = "System Settings";
$session->requireRole('admin');
function settings_table_init() {
    global $db;
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        category VARCHAR(50) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    try {
        $db->getConnection()->exec($sql);
    } catch (Throwable $e) {
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $db->execute($stmt);
        }
    }
}
function settings_get($key, $default = '', $category = null) {
    global $db;
    $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        settings_table_init();
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return $default;
        }
    }
    $row = $db->getSingle($stmt, [$key]);
    if ($row && isset($row['setting_value'])) return $row['setting_value'];
    if ($category) settings_set($key, $default, $category);
    return $default;
}
function settings_set($key, $value, $category) {
    global $db;
    $sql = "INSERT INTO system_settings (setting_key, setting_value, category)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), category = VALUES(category)";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        settings_table_init();
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }
    }
    return $db->execute($stmt, [$key, $value, $category]) !== false;
}
function enc_key() {
    return hash('sha256', DB_HOST . DB_USER . DB_NAME);
}
function encrypt_value($plain) {
    if ($plain === '') return '';
    $key = enc_key();
    $iv = substr($key, 0, 16);
    return base64_encode(openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv));
}
function decrypt_value($cipher) {
    if ($cipher === '') return '';
    $key = enc_key();
    $iv = substr($key, 0, 16);
    $data = base64_decode($cipher);
    return openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}
settings_table_init();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_general') {
        settings_set('site_name', $_POST['site_name'] ?? 'Aksum Rental System', 'general');
        settings_set('site_email', $_POST['site_email'] ?? 'hagomedhanye85@gmail.com', 'general');
        settings_set('currency', $_POST['currency'] ?? 'ETB', 'general');
        settings_set('timezone', $_POST['timezone'] ?? 'Africa/Addis_Ababa', 'general');
        settings_set('registration_enabled', isset($_POST['registration_enabled']) ? '1' : '0', 'general');
        $_SESSION['success'] = 'General settings saved successfully';
    } elseif ($action === 'save_business') {
        settings_set('advance_payment_percent', $_POST['advance_payment_percent'] ?? '20', 'business');
        settings_set('agreement_period_months', $_POST['agreement_period_months'] ?? '6', 'business');
        settings_set('vacating_notice_days', $_POST['vacating_notice_days'] ?? '14', 'business');
        settings_set('max_rental_requests', $_POST['max_rental_requests'] ?? '5', 'business');
        $_SESSION['success'] = 'Business rules saved successfully';
    } elseif ($action === 'save_payment') {
        $methods = $_POST['payment_methods'] ?? [];
        settings_set('payment_methods', json_encode($methods), 'payment');
        settings_set('payment_ref_format', $_POST['payment_ref_format'] ?? 'AKS-{YEAR}-{RANDOM}', 'payment');
        settings_set('payment_verification', isset($_POST['payment_verification']) ? '1' : '0', 'payment');
        $_SESSION['success'] = 'Payment settings saved successfully';
    } elseif ($action === 'save_email') {
        settings_set('smtp_host', $_POST['smtp_host'] ?? '', 'email');
        settings_set('smtp_port', $_POST['smtp_port'] ?? '', 'email');
        settings_set('smtp_username', $_POST['smtp_username'] ?? '', 'email');
        settings_set('smtp_password', encrypt_value($_POST['smtp_password'] ?? ''), 'email');

        // list of known template keys and corresponding filenames
        $tpls = [
            'new_request' => $_POST['tpl_new_request'] ?? '',
            'request_decision' => $_POST['tpl_request_decision'] ?? '',
            'payment_received' => $_POST['tpl_payment_received'] ?? '',
            'vacating_notice' => $_POST['tpl_vacating_notice'] ?? '',
            'welcome' => $_POST['tpl_welcome'] ?? ''
        ];

        foreach ($tpls as $name => $content) {
            settings_set('tpl_' . $name, $content, 'templates');
            // also mirror to file so administrators can manage via settings or filesystem
            $dir = __DIR__ . '/../templates/emails';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $path = $dir . '/' . $name . '.html';
            if ($content === '') {
                // if emptied, remove file as well
                @unlink($path);
            } else {
                @file_put_contents($path, $content);
            }
        }

        $_SESSION['success'] = 'Email & notification settings saved successfully';
    } elseif ($action === 'create_admin_user') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'employee';
        $phone = trim($_POST['phone'] ?? '');
        if ($full_name && $email) {
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            if (!$db->getSingle($stmt, [$email])) {
                $temp = bin2hex(random_bytes(8));
                $hash = password_hash($temp, PASSWORD_DEFAULT);
                $status = 'active';
                $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, role, password_hash, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                if ($db->execute($stmt, [$full_name, $email, $phone, $role, $hash, $status])) {
                    $uid = (int)$db->lastInsertId();
                    $token = bin2hex(random_bytes(32));
                    $exp = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    try {
                        $db->getConnection()->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
                            token_id INT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT NOT NULL,
                            token VARCHAR(255) NOT NULL UNIQUE,
                            email VARCHAR(100) NOT NULL,
                            expires_at TIMESTAMP NOT NULL,
                            used BOOLEAN DEFAULT FALSE,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )");
                    } catch (Throwable $e) {
                        $tstmt = $db->prepare("CREATE TABLE IF NOT EXISTS password_reset_tokens (token_id INT PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, token VARCHAR(255) NOT NULL UNIQUE, email VARCHAR(100) NOT NULL, expires_at TIMESTAMP NOT NULL, used BOOLEAN DEFAULT FALSE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                        if ($tstmt) $db->execute($tstmt);
                    }
                    $istmt = $db->prepare("INSERT INTO password_reset_tokens (user_id, token, email, expires_at, used) VALUES (?, ?, ?, ?, FALSE)");
                    $db->execute($istmt, [$uid, $token, $email, $exp]);
                    $link = SITE_URL . "reset-password.php?token=" . urlencode($token);
                    sendEmailTemplate($email, "Welcome to " . SITE_NAME, 'welcome', [
                        'full_name' => $full_name,
                        'link' => $link,
                        'site_name' => SITE_NAME
                    ]);
                    $_SESSION['success'] = 'User created successfully. A password setup link has been emailed. You can also use this link now: <a href=\"'.$link.'\">'.$link.'</a>';
                }
            } else {
                $_SESSION['error'] = 'Email already exists';
            }
        } else {
            $_SESSION['error'] = 'Full name and email are required';
        }
    } elseif ($action === 'update_user_status') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? 'inactive';
        if ($uid) {
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $db->execute($stmt, [$status, $uid]);
            $_SESSION['success'] = 'User status updated';
        }
    } elseif ($action === 'change_role') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'employee';
        if ($uid) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $db->execute($stmt, [$role, $uid]);
            $_SESSION['success'] = 'User role updated';
        }
    } elseif ($action === 'add_location') {
        $name = trim($_POST['location_name'] ?? '');
        if ($name) {
            try {
                $db->getConnection()->exec("CREATE TABLE IF NOT EXISTS locations (location_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL UNIQUE)");
            } catch (Throwable $e) {
                $cstmt = $db->prepare("CREATE TABLE IF NOT EXISTS locations (location_id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL UNIQUE)");
                if ($cstmt) $db->execute($cstmt);
            }
            $stmt = $db->prepare("INSERT INTO locations (name) VALUES (?)");
            $db->execute($stmt, [$name]);
            $_SESSION['success'] = 'Location added';
        }
    } elseif ($action === 'update_location') {
        $id = (int)($_POST['location_id'] ?? 0);
        $name = trim($_POST['location_name'] ?? '');
        if ($id && $name) {
            $stmt = $db->prepare("UPDATE locations SET name = ? WHERE location_id = ?");
            $db->execute($stmt, [$name, $id]);
            $_SESSION['success'] = 'Location updated';
        }
    } elseif ($action === 'delete_location') {
        $id = (int)($_POST['location_id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("DELETE FROM locations WHERE location_id = ?");
            $db->execute($stmt, [$id]);
            $_SESSION['success'] = 'Location deleted';
        }
    } elseif ($action === 'clear_cache') {
        $paths = [realpath(__DIR__ . '/../uploads/tmp'), realpath(__DIR__ . '/../uploads/cache'), realpath(__DIR__ . '/../assets/cache')];
        foreach ($paths as $p) {
            if ($p && is_dir($p)) {
                $items = scandir($p);
                foreach ($items as $it) {
                    if ($it === '.' || $it === '..') continue;
                    $fp = $p . DIRECTORY_SEPARATOR . $it;
                    if (is_dir($fp)) {
                        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                        foreach ($rii as $fi) {
                            $fi->isDir() ? rmdir($fi->getPathname()) : unlink($fi->getPathname());
                        }
                        rmdir($fp);
                    } else {
                        unlink($fp);
                    }
                }
            }
        }
        $_SESSION['success'] = 'Cache cleared';
    } elseif ($action === 'upload_branding') {
        if (!is_dir(__DIR__ . '/../uploads/branding')) mkdir(__DIR__ . '/../uploads/branding', 0777, true);
        if (!empty($_FILES['logo']['name'])) {
            $name = time() . '-logo-' . basename($_FILES['logo']['name']);
            $dest = __DIR__ . '/../uploads/branding/' . $name;
            move_uploaded_file($_FILES['logo']['tmp_name'], $dest);
            settings_set('brand_logo', 'uploads/branding/' . $name, 'appearance');
        }
        if (!empty($_FILES['favicon']['name'])) {
            $name = time() . '-favicon-' . basename($_FILES['favicon']['name']);
            $dest = __DIR__ . '/../uploads/branding/' . $name;
            move_uploaded_file($_FILES['favicon']['tmp_name'], $dest);
            settings_set('brand_favicon', 'uploads/branding/' . $name, 'appearance');
        }
        settings_set('custom_css', $_POST['custom_css'] ?? '', 'appearance');
        $_SESSION['success'] = 'Branding updated';
    }
    header("Location: settings.php");
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    $tables = [];
    $res = $db->prepare("SHOW TABLES");
    $list = $db->getMultiple($res);
    foreach ($list as $row) {
        $tables[] = array_values($row)[0];
    }
    $dump = "";
    foreach ($tables as $t) {
        $cs = $db->prepare("SHOW CREATE TABLE `$t`");
        $cr = $db->getSingle($cs);
        $dump .= $cr['Create Table'] . ";\n\n";
        $ds = $db->prepare("SELECT * FROM `$t`");
        $data = $db->getMultiple($ds);
        if ($data) {
            foreach ($data as $r) {
                $cols = array_map(function($c){ return "`$c`"; }, array_keys($r));
                $vals = array_map(function($v){ return isset($v) ? "'" . addslashes($v) . "'" : "NULL"; }, array_values($r));
                $dump .= "INSERT INTO `$t` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
            }
            $dump .= "\n";
        }
    }
    $fn = "aksum_backup_" . date('Ymd_His') . ".sql";
    header("Content-Type: application/sql");
    header("Content-Disposition: attachment; filename=\"$fn\"");
    echo $dump;
    exit;
}
$site_name = settings_get('site_name', 'Aksum Rental System', 'general');
$site_email = settings_get('site_email', 'hagomedhanye85@gmail.com', 'general');
$currency = settings_get('currency', 'ETB', 'general');
$timezone = settings_get('timezone', 'Africa/Addis_Ababa', 'general');
$registration_enabled = settings_get('registration_enabled', '1', 'general');
$advance_payment_percent = settings_get('advance_payment_percent', '20', 'business');
$agreement_period_months = settings_get('agreement_period_months', '6', 'business');
$vacating_notice_days = settings_get('vacating_notice_days', '14', 'business');
$max_rental_requests = settings_get('max_rental_requests', '5', 'business');
$payment_methods = json_decode(settings_get('payment_methods', json_encode(['cash','bank_transfer','telebirr']), 'payment'), true);
$payment_ref_format = settings_get('payment_ref_format', 'AKS-{YEAR}-{RANDOM}', 'payment');
$payment_verification = settings_get('payment_verification', '0', 'payment');
$smtp_host = settings_get('smtp_host', '', 'email');
$smtp_port = settings_get('smtp_port', '', 'email');
$smtp_username = settings_get('smtp_username', '', 'email');
$smtp_password = decrypt_value(settings_get('smtp_password', '', 'email'));
// load templates from settings or fallback to file contents if available
function loadTemplateSetting($name) {
    $val = settings_get('tpl_' . $name, '', 'templates');
    if ($val === '') {
        $path = __DIR__ . '/../templates/emails/' . $name . '.html';
        if (is_file($path)) {
            $val = file_get_contents($path);
        }
    }
    return $val;
}

$tpl_new_request = loadTemplateSetting('new_request');
$tpl_request_decision = loadTemplateSetting('request_decision');
$tpl_payment_received = loadTemplateSetting('payment_received');
$tpl_vacating_notice = loadTemplateSetting('vacating_notice');
$tpl_welcome = loadTemplateSetting('welcome');
$brand_logo = settings_get('brand_logo', '', 'appearance');
$brand_favicon = settings_get('brand_favicon', '', 'appearance');
$custom_css = settings_get('custom_css', '', 'appearance');
$users = [];
try {
    $us = $db->prepare("SELECT user_id, full_name, email, role, status FROM users ORDER BY created_at DESC LIMIT 50");
    $users = $db->getMultiple($us);
} catch (Exception $e) {}
$locations = [];
try {
    $ls = $db->prepare("SELECT location_id, name FROM locations ORDER BY name ASC");
    $locations = $db->getMultiple($ls);
} catch (Exception $e) {}
$health = [
    'php' => phpversion(),
    'mysql' => ($db->getSingle($db->prepare("SELECT VERSION() as v"))['v'] ?? ''),
    'disk_total' => disk_total_space(__DIR__ . '/..'),
    'disk_free' => disk_free_space(__DIR__ . '/..')
];
include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">System Settings</h1>
                <a class="btn btn-outline-secondary" href="settings.php?action=backup">Backup Database</a>
            </div>
            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">General</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#business" type="button">Business Rules</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#payment" type="button">Payment</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#email" type="button">Email & Notifications</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#users" type="button">Users & Roles</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#locations" type="button">Locations</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#maintenance" type="button">Maintenance</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#appearance" type="button">Appearance</button></li>
            </ul>
            <div class="tab-content border border-top-0 p-3">
                <div class="tab-pane fade show active" id="general">
                    <form method="post">
                        <input type="hidden" name="action" value="save_general">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Site Name</label>
                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Site Email</label>
                                <input type="email" class="form-control" name="site_email" value="<?php echo htmlspecialchars($site_email); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency</label>
                                <input type="text" class="form-control" name="currency" value="<?php echo htmlspecialchars($currency); ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Timezone</label>
                                <input type="text" class="form-control" name="timezone" value="<?php echo htmlspecialchars($timezone); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="registration_enabled" <?php echo $registration_enabled === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Enable user registration</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary">Save</button></div>
                    </form>
                </div>
                <div class="tab-pane fade" id="business">
                    <form method="post">
                        <input type="hidden" name="action" value="save_business">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Advance Payment %</label>
                                <input type="number" class="form-control" name="advance_payment_percent" value="<?php echo htmlspecialchars($advance_payment_percent); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Agreement Period (months)</label>
                                <input type="number" class="form-control" name="agreement_period_months" value="<?php echo htmlspecialchars($agreement_period_months); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vacating Notice (days)</label>
                                <input type="number" class="form-control" name="vacating_notice_days" value="<?php echo htmlspecialchars($vacating_notice_days); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max requests per tenant</label>
                                <input type="number" class="form-control" name="max_rental_requests" value="<?php echo htmlspecialchars($max_rental_requests); ?>">
                            </div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary">Save</button></div>
                    </form>
                </div>
                <div class="tab-pane fade" id="payment">
                    <form method="post">
                        <input type="hidden" name="action" value="save_payment">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Payment Methods</label>
                                <?php
                                $allMethods = ['cash','bank_transfer','telebirr','cbe_birr','amole','other'];
                                foreach ($allMethods as $m) {
                                    $checked = in_array($m, $payment_methods ?? []) ? 'checked' : '';
                                    echo '<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="payment_methods[]" value="'.$m.'" '.$checked.'><label class="form-check-label">'.strtoupper(str_replace('_',' ',$m)).'</label></div>';
                                }
                                ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Code Format</label>
                                <input type="text" class="form-control" name="payment_ref_format" value="<?php echo htmlspecialchars($payment_ref_format); ?>">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="payment_verification" <?php echo $payment_verification === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Enable Payment Verification</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary">Save</button></div>
                    </form>
                </div>
                <div class="tab-pane fade" id="email">
                    <form method="post">
                        <input type="hidden" name="action" value="save_email">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">SMTP Port</label>
                                <input type="text" class="form-control" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" name="smtp_username" value="<?php echo htmlspecialchars($smtp_username); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" name="smtp_password" value="<?php echo htmlspecialchars($smtp_password); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Welcome Email</label>
                                <textarea class="form-control" name="tpl_welcome" rows="3"><?php echo htmlspecialchars($tpl_welcome); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Request</label>
                                <textarea class="form-control" name="tpl_new_request" rows="3"><?php echo htmlspecialchars($tpl_new_request); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Request Approved/Rejected</label>
                                <textarea class="form-control" name="tpl_request_decision" rows="3"><?php echo htmlspecialchars($tpl_request_decision); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Received</label>
                                <textarea class="form-control" name="tpl_payment_received" rows="3"><?php echo htmlspecialchars($tpl_payment_received); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vacating Notice</label>
                                <textarea class="form-control" name="tpl_vacating_notice" rows="3"><?php echo htmlspecialchars($tpl_vacating_notice); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary">Save</button></div>
                    </form>
                </div>
                <div class="tab-pane fade" id="users">
                    <div class="mb-3">
                        <form method="post" class="row g-2">
                            <input type="hidden" name="action" value="create_admin_user">
                            <div class="col-md-3"><input type="text" class="form-control" name="full_name" placeholder="Full Name"></div>
                            <div class="col-md-3"><input type="email" class="form-control" name="email" placeholder="Email"></div>
                            <div class="col-md-2"><input type="text" class="form-control" name="phone" placeholder="Phone"></div>
                            <div class="col-md-2">
                                <select class="form-select" name="role">
                                    <option value="admin">Admin</option>
                                    <option value="employee">Employee</option>
                                </select>
                            </div>
                            <div class="col-md-2"><button class="btn btn-success w-100">Create</button></div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light"><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                            <select class="form-select form-select-sm" name="role">
                                                <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                                                <option value="employee" <?php echo $u['role']==='employee'?'selected':''; ?>>Employee</option>
                                                <option value="owner" <?php echo $u['role']==='owner'?'selected':''; ?>>Owner</option>
                                                <option value="tenant" <?php echo $u['role']==='tenant'?'selected':''; ?>>Tenant</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary">Update</button>
                                        </form>
                                    </td>
                                    <td><?php echo ucfirst($u['status']); ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="update_user_status">
                                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $u['status']==='active'?'inactive':'active'; ?>">
                                            <button class="btn btn-sm <?php echo $u['status']==='active'?'btn-outline-warning':'btn-outline-success'; ?>"><?php echo $u['status']==='active'?'Deactivate':'Activate'; ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 small text-muted">Role permissions matrix is read-only for now.</div>
                </div>
                <div class="tab-pane fade" id="locations">
                    <div class="row">
                        <div class="col-md-5">
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="action" value="add_location">
                                <input type="text" class="form-control" name="location_name" placeholder="Add location">
                                <button class="btn btn-primary">Add</button>
                            </form>
                            <ul class="list-group mt-3">
                                <?php foreach ($locations as $loc): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <form method="post" class="d-flex gap-2 w-100">
                                            <input type="hidden" name="action" value="update_location">
                                            <input type="hidden" name="location_id" value="<?php echo $loc['location_id']; ?>">
                                            <input type="text" class="form-control" name="location_name" value="<?php echo htmlspecialchars($loc['name']); ?>">
                                            <button class="btn btn-outline-secondary">Save</button>
                                        </form>
                                        <form method="post" class="ms-2">
                                            <input type="hidden" name="action" value="delete_location">
                                            <input type="hidden" name="location_id" value="<?php echo $loc['location_id']; ?>">
                                            <button class="btn btn-outline-danger">Delete</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="maintenance">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <form method="post">
                                <input type="hidden" name="action" value="clear_cache">
                                <button class="btn btn-warning w-100">Clear Cache</button>
                            </form>
                        </div>
                        <div class="col-md-9">
                            <div class="card">
                                <div class="card-header">System Health</div>
                                <div class="card-body">
                                    <div>PHP: <?php echo htmlspecialchars($health['php']); ?></div>
                                    <div>MySQL: <?php echo htmlspecialchars($health['mysql']); ?></div>
                                    <div>Disk Total: <?php echo number_format($health['disk_total']/1024/1024/1024,2); ?> GB</div>
                                    <div>Disk Free: <?php echo number_format($health['disk_free']/1024/1024/1024,2); ?> GB</div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">Recent System Logs</div>
                                <div class="card-body">
                                    <?php
                                    $logs = [];
                                    try {
                                        $ls = $db->prepare("SELECT user_id, action, entity, entity_id, created_at FROM audit_log ORDER BY created_at DESC LIMIT 50");
                                        $logs = $db->getMultiple($ls);
                                    } catch (Exception $e) {}
                                    if (empty($logs)) {
                                        echo '<div class="text-muted">No logs</div>';
                                    } else {
                                        echo '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>User</th><th>Action</th><th>Entity</th><th>ID</th><th>Time</th></tr></thead><tbody>';
                                        foreach ($logs as $lg) {
                                            echo '<tr><td>'.(int)$lg['user_id'].'</td><td>'.htmlspecialchars($lg['action']).'</td><td>'.htmlspecialchars($lg['entity']).'</td><td>'.(int)$lg['entity_id'].'</td><td>'.htmlspecialchars($lg['created_at']).'</td></tr>';
                                        }
                                        echo '</tbody></table></div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="appearance">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_branding">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Logo</label>
                                <input type="file" class="form-control" name="logo">
                                <?php if ($brand_logo): ?>
                                    <img src="<?php echo '../' . $brand_logo; ?>" class="mt-2" height="40">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Favicon</label>
                                <input type="file" class="form-control" name="favicon">
                                <?php if ($brand_favicon): ?>
                                    <img src="<?php echo '../' . $brand_favicon; ?>" class="mt-2" height="24">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Custom CSS</label>
                                <textarea class="form-control" name="custom_css" rows="5"><?php echo htmlspecialchars($custom_css); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary">Save</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($custom_css)): ?>
        <style><?php echo $custom_css; ?></style>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
