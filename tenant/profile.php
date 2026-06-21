<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "My Profile - Aksum Rental System";

$user_id = $session->getUserId();

// Get user information
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$user = $db->getSingle($stmt, [$user_id]);

// Get tenant statistics
$sql = "SELECT COUNT(*) as total FROM rental_agreements WHERE tenant_id = ?";
$stmt = $db->prepare($sql);
$total_agreements = $db->getSingle($stmt, [$user_id])['total'];

$sql = "SELECT COUNT(*) as total FROM rental_requests WHERE tenant_id = ?";
$stmt = $db->prepare($sql);
$total_requests = $db->getSingle($stmt, [$user_id])['total'];

$sql = "SELECT COUNT(*) as total FROM payments WHERE tenant_id = ?";
$stmt = $db->prepare($sql);
$total_payments = $db->getSingle($stmt, [$user_id])['total'];

$sql = "SELECT COUNT(*) as total FROM feedback WHERE user_id = ?";
$stmt = $db->prepare($sql);
$total_feedback = $db->getSingle($stmt, [$user_id])['total'];

// Load one-time flash messages from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['password_changed'])) {
    $password_changed = true;
    unset($_SESSION['password_changed']);
}

// Handle profile update or password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_after_post = false;
    $action = $_POST['action'] ?? (isset($_POST['current_password'], $_POST['new_password']) ? 'change_password' : 'update_profile');
    try {
        if ($action === 'update_profile') {
            $full_name = $_POST['full_name'] ?? $user['full_name'];
            $email = $_POST['email'] ?? $user['email'];
            $phone = $_POST['phone'] ?? ($user['phone'] ?? '');
            $address = $_POST['address'] ?? ($user['address'] ?? '');
            $id_number = $_POST['id_number'] ?? ($user['id_number'] ?? '');
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, id_number = ?, updated_at = NOW() 
                    WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$full_name, $email, $phone, $address, $id_number, $user_id]);
            $_SESSION['success'] = 'Profile updated successfully!';
            $redirect_after_post = true;
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            if ($new_password !== $confirm_password) {
                $password_error = 'New password and confirmation do not match';
            } elseif (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                $password_error = 'Password must be at least 8 characters and include letters and numbers';
            } else {
                $stored_hash = $user['password_hash'] ?? ($user['password'] ?? '');
                if (!$stored_hash || !password_verify($current_password, $stored_hash)) {
                    $password_error = 'Current password is incorrect';
                } elseif (password_verify($new_password, $stored_hash)) {
                    $password_error = 'New password must be different from current password';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $db->execute($stmt, [$hashed_password, $user_id]);
                    $_SESSION['password_changed'] = true;
                    $redirect_after_post = true;
                }
            }
        }
        
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    $sql = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $db->execute($stmt, [$file_name, $user_id]);
                    $user['profile_picture'] = $file_name;
                }
            }
        }
        
        // Handle ID image upload
        if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/ids/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['id_image']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions, true)) {
                $file_name = 'id_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['id_image']['tmp_name'], $upload_path)) {
                    $sql = "UPDATE users SET id_image = ? WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $db->execute($stmt, ['assets/uploads/ids/' . $file_name, $user_id]);
                    $user['id_image'] = 'assets/uploads/ids/' . $file_name;
                }
            }
        }
        
        // Refresh user data
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $user = $db->getSingle($stmt, [$user_id]);

        if ($redirect_after_post && empty($password_error)) {
            header('Location: profile.php');
            exit;
        }
    } catch (Exception $e) {
        $error = 'Error updating profile. Please try again.';
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">My Profile</h1>
                            <p class="text-muted mb-0">Manage your personal information and account settings</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-end">
                                <small class="text-muted">Member Since</small>
                                <h6 class="mb-0"><?php echo date('F Y', strtotime($user['created_at'])); ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Profile Updated!</h6>
                            <p class="mb-0"><?php echo $success; ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Error!</h6>
                            <p class="mb-0"><?php echo $error; ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($password_error)): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Password Error!</h6>
                            <p class="mb-0"><?php echo $password_error; ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($password_changed)): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-key fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Password Changed!</h6>
                            <p class="mb-0">Your password has been updated successfully.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Card -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if ($user['profile_picture']): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         class="rounded-circle" alt="Profile Picture" style="width: 120px; height: 120px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 120px; height: 120px; margin: 0 auto;">
                                        <i class="fas fa-user fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <p class="text-muted mb-2"><?php echo ucfirst($user['role']); ?></p>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                            <?php if (!empty($user['id_image']) || !empty($user['profile_image'])): ?>
                                <?php $idImagePath = !empty($user['id_image']) ? $user['id_image'] : $user['profile_image']; ?>
                                <p class="mb-3">
                                    <a href="../<?php echo htmlspecialchars($idImagePath); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-id-card me-2"></i>View uploaded ID
                                    </a>
                                </p>
                            <?php endif; ?>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changeAvatarModal">
                                    <i class="fas fa-camera me-2"></i>Change Avatar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePictureModal">
                                    <i class="fas fa-id-card me-2"></i>Upload ID
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="address" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['address']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ID Number</label>
                                        <input type="text" name="id_number" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['id_number'] ?? ''); ?>" 
                                               placeholder="Enter your ID number">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Profile Picture</label>
                                        <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                        <div class="form-text">Upload a profile picture for your account</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ID Image</label>
                                        <input type="file" name="id_image" class="form-control" accept="image/*">
                                        <div class="form-text">Upload your ID card image</div>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <input type="hidden" name="action" value="update_profile">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="stat-item">
                                <i class="fas fa-file-contract fa-2x text-primary mb-2"></i>
                                <h4 class="mb-1"><?php echo $total_agreements; ?></h4>
                                <p class="text-muted mb-0">Rental Agreements</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-item">
                                <i class="fas fa-paper-plane fa-2x text-warning mb-2"></i>
                                <h4 class="mb-1"><?php echo $total_requests; ?></h4>
                                <p class="text-muted mb-0">Rental Requests</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-item">
                                <i class="fas fa-credit-card fa-2x text-success mb-2"></i>
                                <h4 class="mb-1"><?php echo $total_payments; ?></h4>
                                <p class="text-muted mb-0">Payments Made</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-item">
                                <i class="fas fa-comment fa-2x text-info mb-2"></i>
                                <h4 class="mb-1"><?php echo $total_feedback; ?></h4>
                                <p class="text-muted mb-0">Feedback Given</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Settings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6>Account Information</h6>
                            <p class="text-muted small mb-1">User ID: <?php echo $user['user_id']; ?></p>
                            <p class="text-muted small mb-1">Role: <?php echo ucfirst($user['role']); ?></p>
                            <p class="text-muted small mb-1">Joined: <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                            <p class="text-muted small mb-1">Last Updated: <?php echo $user['updated_at'] ? date('F d, Y H:i', strtotime($user['updated_at'])) : 'Never'; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6>Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                                <a href="search.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-search me-2"></i>Search Properties
                                </a>
                                <a href="notifications.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-bell me-2"></i>Notifications
                                </a>
                                <a href="../includes/logout.php" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload ID Modal -->
<div class="modal fade" id="changePictureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-id-card me-2"></i>Upload ID Image
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="uploadIdForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Upload your ID card image to use as your profile picture. This will replace your current profile picture.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select ID Image *</label>
                        <input type="file" name="id_image" class="form-control" accept="image/*" required>
                        <div class="form-text">
                            <i class="fas fa-file-image me-1"></i>
                            Allowed formats: JPG, PNG, GIF. Maximum size: 2MB
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmId" required>
                            <label class="form-check-label" for="confirmId">
                                I confirm this is my valid ID card image
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload ID
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Avatar Modal -->
<div class="modal fade" id="changeAvatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-camera me-2"></i>Change Avatar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="changeAvatarForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Upload a new profile picture to update your avatar.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Profile Picture *</label>
                        <input type="file" name="profile_picture" class="form-control" accept="image/*" required>
                        <div class="form-text">
                            <i class="fas fa-file-image me-1"></i>
                            Allowed formats: JPG, PNG, GIF. Maximum size: 2MB
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-upload me-2"></i>Upload Avatar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="changePasswordForm">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        For security reasons, please enter your current password to set a new password.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" name="current_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrent">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" name="new_password" class="form-control" minlength="8" required id="newPassword">
                            <button class="btn btn-outline-secondary" type="button" id="toggleNew">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-shield-alt me-1"></i>
                            Password must be at least 8 characters long
                        </div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required id="confirmPassword">
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text" id="passwordMatch"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="action" value="change_password">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Password visibility toggles
    $('#toggleCurrent').click(function() {
        const input = $('input[name="current_password"]');
        const icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    $('#toggleNew').click(function() {
        const input = $('#newPassword');
        const icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    $('#toggleConfirm').click(function() {
        const input = $('#confirmPassword');
        const icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Password strength checker
    $('#newPassword').on('input', function() {
        const password = $(this).val();
        let strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
        if (password.match(/[0-9]/)) strength += 25;
        if (password.match(/[^a-zA-Z0-9]/)) strength += 25;
        
        const progressBar = $('#passwordStrength');
        progressBar.css('width', strength + '%');
        
        if (strength <= 25) {
            progressBar.removeClass('bg-warning bg-success').addClass('bg-danger');
        } else if (strength <= 50) {
            progressBar.removeClass('bg-danger bg-success').addClass('bg-warning');
        } else {
            progressBar.removeClass('bg-danger bg-warning').addClass('bg-success');
        }
        
        checkPasswordMatch();
    });
    
    // Password match checker
    function checkPasswordMatch() {
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();
        const matchText = $('#passwordMatch');
        
        if (confirmPassword === '') {
            matchText.text('');
            return;
        }
        
        if (newPassword === confirmPassword) {
            matchText.html('<i class="fas fa-check-circle text-success me-1"></i>Passwords match').removeClass('text-danger').addClass('text-success');
        } else {
            matchText.html('<i class="fas fa-times-circle text-danger me-1"></i>Passwords do not match').removeClass('text-success').addClass('text-danger');
        }
    }
    
    $('#confirmPassword').on('input', checkPasswordMatch);
    
    // Form validation
    $('#changePasswordForm').submit(function(e) {
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New password and confirmation do not match!');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
    });
    
    // ID upload form validation
    $('#uploadIdForm').submit(function(e) {
        const fileInput = $('input[name="id_image"]')[0];
        const file = fileInput.files[0];
        
        if (file) {
            const maxSize = 2 * 1024 * 1024; // 2MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            if (file.size > maxSize) {
                e.preventDefault();
                alert('File size must be less than 2MB!');
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                e.preventDefault();
                alert('Only JPG, PNG, and GIF files are allowed!');
                return false;
            }
        }
    });
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
