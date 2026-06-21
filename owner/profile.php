<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = 'My Profile - Aksum Rental System';

$user_id = $session->getUserId();

$stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
$user = $db->getSingle($stmt, [$user_id]);

// Handle profile update or password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? (isset($_POST['current_password'], $_POST['new_password']) ? 'change_password' : 'update_profile');
    try {
        if ($action === 'update_profile') {
            $full_name = trim($_POST['full_name'] ?? $user['full_name']);
            $phone = trim($_POST['phone'] ?? ($user['phone'] ?? ''));
            $address = trim($_POST['address'] ?? ($user['address'] ?? ''));
            $id_number = trim($_POST['id_number'] ?? ($user['id_number'] ?? ''));

            $sql = 'UPDATE users SET full_name = ?, phone = ?, address = ?, id_number = ?, updated_at = NOW() WHERE user_id = ?';
            $stmt = $db->prepare($sql);
            if ($db->execute($stmt, [$full_name, $phone, $address, $id_number, $user_id])) {
                $_SESSION['success'] = 'Profile updated successfully.';
            }

            // Profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed, true)) {
                    $file_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                    $dest = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
                        $stmt = $db->prepare('UPDATE users SET profile_picture = ? WHERE user_id = ?');
                        $db->execute($stmt, [$file_name, $user_id]);
                    }
                }
            }

            // ID image upload
            if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/uploads/ids/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $ext = strtolower(pathinfo($_FILES['id_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed, true)) {
                    $file_name = 'id_' . $user_id . '_' . time() . '.' . $ext;
                    $dest = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['id_image']['tmp_name'], $dest)) {
                        $stmt = $db->prepare('UPDATE users SET id_image = ? WHERE user_id = ?');
                        $db->execute($stmt, ['assets/uploads/ids/' . $file_name, $user_id]);
                    }
                }
            }

            header('Location: profile.php');
            exit;
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = 'New password and confirmation do not match';
            } elseif (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                $_SESSION['error'] = 'Password must be at least 8 characters and include letters and numbers';
            } else {
                $stored_hash = $user['password_hash'] ?? ($user['password'] ?? '');
                if (!$stored_hash || !password_verify($current_password, $stored_hash)) {
                    $_SESSION['error'] = 'Current password is incorrect';
                } elseif (password_verify($new_password, $stored_hash)) {
                    $_SESSION['error'] = 'New password must be different from current password';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $db->execute($stmt, [$hashed_password, $user_id]);
                    $_SESSION['success'] = 'Password changed successfully.';
                }
            }
            header('Location: profile.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
        header('Location: profile.php');
        exit;
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
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-0">My Profile</h1>
                    <p class="text-muted mb-0">Manage your personal information and account security</p>
                </div>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="rounded-circle" alt="Profile" style="width: 120px; height: 120px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; margin: 0 auto;">
                                        <i class="fas fa-user fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                            <p class="text-muted small">Role: Owner</p>
                            <?php if (!empty($user['id_image']) || !empty($user['profile_image'])): ?>
                                <?php $ownerIdImage = !empty($user['id_image']) ? $user['id_image'] : $user['profile_image']; ?>
                                <p class="mt-3">
                                    <a href="../<?php echo htmlspecialchars($ownerIdImage); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-id-card me-2"></i>View uploaded ID
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Profile Information</h5></div>
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ID Number</label>
                                        <input type="text" name="id_number" class="form-control" value="<?php echo htmlspecialchars($user['id_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Profile Picture</label>
                                        <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">ID Image</label>
                                        <input type="file" name="id_image" class="form-control" accept="image/*">
                                        <div class="form-text">Upload your ID image to keep your registration document up to date.</div>
                                    </div>
                                </div>
                                <div class="mt-4 d-flex gap-2">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="changePasswordForm">
                <input type="hidden" name="action" value="change_password">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="currentPassword" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrent">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" id="toggleNew">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text mt-1"><i class="fas fa-shield-alt me-1"></i>Min 8 characters, letters & numbers</div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" class="form-text mt-1"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Password visibility toggles
    $('#toggleCurrent').click(function() {
        const input = $('#currentPassword');
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
});
</script>

<?php include '../includes/footer.php'; ?>
