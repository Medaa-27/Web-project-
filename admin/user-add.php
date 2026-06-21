<?php
require_once '../includes/config.php';
$title = "Add User";
$session->requireRole('admin');

// Ensure force_password_change column exists
if (!$db->columnExists('users', 'force_password_change')) {
    $db->getConnection()->exec("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'tenant';
    $status = $_POST['status'] ?? 'active';

    $errors = [];
    $field_errors = [];

    // Validation
    if (empty($full_name)) {
        $field_errors['full_name'] = 'Full name is required';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $field_errors['full_name'] = 'Full name must contain only letters and spaces';
    } elseif (strlen($full_name) < 3) {
        $field_errors['full_name'] = 'Full name must be at least 3 characters long';
    }

    if (empty($email)) {
        $field_errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors['email'] = 'Please enter a valid email address';
    }

    if (empty($phone)) {
        $field_errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^(09|07)\d{8}$/', $phone)) {
        $field_errors['phone'] = 'Phone must be 10 digits starting with 09 or 07';
    }

    if (!empty($field_errors)) {
        $_SESSION['error'] = 'Please correct the highlighted errors.';
    } else {
        // Auto-generate password if empty or to ensure it matches the rule
        // Rule: first 3 letters of name (lowercase, no spaces/special chars) + @HRMS
        $cleanName = preg_replace('/[^a-zA-Z]/', '', $full_name);
        $prefix = strtolower(substr($cleanName, 0, 3));
        $generatedPassword = $prefix . '@HRMS';
        
        // If password was manually provided, we might still want to use the generated one 
        // as per "Implement automatic default password generation"
        $finalPassword = $generatedPassword;
        
        $check = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        if ($db->getSingle($check, [$email])) {
            $_SESSION['error'] = 'Email already exists';
        } else {
            $hash = password_hash($finalPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, role, password_hash, status, force_password_change, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
            
            if ($db->execute($stmt, [$full_name, $email, $phone, $role, $hash, $status])) {
                $uid = (int)$db->lastInsertId();
                
                // Send welcome email with credentials
                $subject = "Welcome to " . SITE_NAME;
                $emailSent = sendEmailTemplate($email, $subject, 'user_created', [
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => $finalPassword,
                    'login_link' => rtrim(SITE_URL, '/') . "/login.php",
                    'site_name' => SITE_NAME,
                    'year' => date('Y')
                ]);

                if (!$emailSent) {
                    error_log("Failed to send welcome email to $email");
                }

                $_SESSION['success'] = "Your account has been created successfully. Your default password is: <strong>$finalPassword</strong>. Please change your password after logging in for security purposes.";
                header("Location: users.php");
                exit;
            } else {
                $_SESSION['error'] = 'Failed to create user';
            }
        }
    }
}
include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">Add User</h1>
                <a href="users.php" class="btn btn-outline-secondary"><i class="fas fa-users me-2"></i>Users</a>
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="post" class="row g-3" novalidate>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control <?php echo isset($field_errors['full_name']) ? 'is-invalid' : ''; ?>" name="full_name" id="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required minlength="3" pattern="[a-zA-Z\s]+" title="Only letters and spaces are allowed">
                            <?php if (isset($field_errors['full_name'])): ?>
                                <div class="invalid-feedback"><?php echo $field_errors['full_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control <?php echo isset($field_errors['email']) ? 'is-invalid' : ''; ?>" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="example@domain.com">
                            <?php if (isset($field_errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $field_errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control <?php echo isset($field_errors['phone']) ? 'is-invalid' : ''; ?>" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="09... or 07..." required pattern="(09|07)\d{8}" maxlength="10" title="Phone must be 10 digits starting with 09 or 07">
                            <?php if (isset($field_errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $field_errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Password</label>
                            <div class="input-group has-validation">
                                <input type="text" class="form-control <?php echo isset($field_errors['password']) ? 'is-invalid' : ''; ?>" name="password" id="password" readonly tabindex="-1">
                                <button class="btn btn-outline-secondary" type="button" disabled>
                                    <i class="fas fa-magic"></i>
                                </button>
                                <?php if (isset($field_errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $field_errors['password']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">Auto-generated based on name. Format: <code>3-letters@HRMS</code></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="admin">Admin</option>
                                <option value="employee">Employee</option>
                                <option value="owner">Owner</option>
                                <option value="tenant" selected>Tenant</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('full_name').addEventListener('input', function() {
    const fullName = this.value;
    const passwordInput = document.getElementById('password');
    
    // Clean name: only letters
    const cleanName = fullName.replace(/[^a-zA-Z]/g, '');
    
    if (cleanName.length > 0) {
        // Take first 3 letters and convert to lowercase
        const prefix = cleanName.substring(0, 3).toLowerCase();
        passwordInput.value = prefix + '@HRMS';
    } else {
        passwordInput.value = '';
    }
});
</script>
<?php include '../includes/footer.php'; ?>
