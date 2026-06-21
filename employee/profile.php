<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Employee Profile";

$employee_id = $session->getUserId();

// Get employee details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$employee = $db->getSingle($stmt, [$employee_id]);

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        // Handle profile image upload
        $profile_image_path = $employee['profile_image'] ?? null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            // Validate file
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                // Create upload directory if it doesn't exist
                $upload_dir = '../assets/uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'employee_' . $employee_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if (!empty($employee['profile_image']) && file_exists('../' . $employee['profile_image'])) {
                        unlink('../' . $employee['profile_image']);
                    }
                    
                    // Update database with new image path
                    $profile_image_path = 'assets/uploads/profiles/' . $filename;
                }
            } else {
                $_SESSION['error'] = "Invalid image file. Allowed formats: JPG, PNG, GIF, WEBP. Max size: 2MB";
            }
        }
        
        $sql = "UPDATE users SET full_name = ?, phone = ?, address = ?, profile_image = ? WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        if ($db->execute($stmt, [$full_name, $phone, $address, $profile_image_path, $employee_id])) {
            $_SESSION['success'] = "Profile updated successfully";
            header("Location: profile.php");
            exit();
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New password and confirmation do not match";
        } elseif (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $_SESSION['error'] = "Password must be at least 8 characters and include letters and numbers";
        } else {
            $sql = "SELECT password_hash FROM users WHERE user_id = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            $userRow = $db->getSingle($stmt, [$employee_id]);
            $stored_hash = trim($userRow['password_hash'] ?? '');
            $verified = false;
            if ($stored_hash && strpos($stored_hash, '$') === 0) {
                $verified = password_verify($current_password, $stored_hash);
            }
            if ($verified) {
                if (password_verify($new_password, $stored_hash)) {
                    $_SESSION['error'] = "New password must be different from current password";
                } else {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    if ($db->execute($stmt, [$new_hash, $employee_id])) {
                        $_SESSION['success'] = "Password changed successfully";
                        header("Location: profile.php");
                        exit();
                    } else {
                        $_SESSION['error'] = "Failed to change password";
                    }
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect";
            }
        }
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
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-0">Employee Profile</h1>
                    <p class="text-muted mb-0">Manage your account information</p>
                </div>
            </div>
            
            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>" readonly>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['user_id']); ?>" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Profile Image</label>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0 me-3">
                                                <?php 
                                                $profile_image = $employee['profile_image'] ?? '../assets/images/default-avatar.svg';
                                                if (!empty($employee['profile_image']) && file_exists('../' . $employee['profile_image'])) {
                                                    $profile_image = '../' . $employee['profile_image'];
                                                } else {
                                                    $profile_image = '../assets/images/default-avatar.svg';
                                                }
                                                ?>
                                                <img src="<?php echo $profile_image; ?>" alt="Profile" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="file" name="profile_image" class="form-control" accept="image/*">
                                                <small class="text-muted">Allowed formats: JPG, PNG, GIF. Max size: 2MB</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="Employee" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Registration Date</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo date('M d, Y', strtotime($employee['created_at'] ?? $employee['registration_date'] ?? 'now')); ?>" readonly>
                                    </div>
                                </div>
                                <div class="mt-4 d-flex gap-2">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Account Status -->
                    <div class="card dashboard-card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Account Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm bg-<?php echo ($employee['status'] ?? 'ACTIVE') == 'ACTIVE' ? 'success' : 'danger'; ?> bg-opacity-10 text-<?php echo ($employee['status'] ?? 'ACTIVE') == 'ACTIVE' ? 'success' : 'danger'; ?> rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?php echo $employee['status'] ?? 'ACTIVE'; ?></h6>
                                    <p class="text-muted mb-0">Account Status</p>
                                </div>
                            </div>
                            <div class="d-grid">
                                <a href="../logout.php" class="btn btn-outline-danger">
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

<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="changePasswordForm" autocomplete="off">
                <input type="hidden" name="change_password" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" name="current_password" class="form-control" required id="currentPasswordEmp" autocomplete="current-password" autocapitalize="off" spellcheck="false">
                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrentEmp">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" name="new_password" class="form-control" minlength="8" required id="newPasswordEmp" autocomplete="new-password" autocapitalize="off" spellcheck="false">
                            <button class="btn btn-outline-secondary" type="button" id="toggleNewEmp">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text"><i class="fas fa-shield-alt me-1"></i>Password must be at least 8 characters and include letters and numbers</div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar" id="passwordStrengthEmp" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required id="confirmPasswordEmp" autocomplete="new-password" autocapitalize="off" spellcheck="false">
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmEmp">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text" id="passwordMatchEmp"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-key me-2"></i>Change Password</button>
                </div>
            </form>
        </div>
    </div>
></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  function setupToggle(btnId, inputId) {
    const btn = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    if (!btn || !input) return;
    btn.addEventListener('click', function() {
      const icon = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye');
      }
    });
  }
  setupToggle('toggleCurrentEmp','currentPasswordEmp');
  setupToggle('toggleNewEmp','newPasswordEmp');
  setupToggle('toggleConfirmEmp','confirmPasswordEmp');

  function updateStrength() {
    const pwd = document.getElementById('newPasswordEmp').value || '';
    let strength = 0;
    if (pwd.length >= 8) strength += 25;
    if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength += 25;
    if (/[0-9]/.test(pwd)) strength += 25;
    if (/[^a-zA-Z0-9]/.test(pwd)) strength += 25;
    const bar = document.getElementById('passwordStrengthEmp');
    if (!bar) return;
    bar.style.width = strength + '%';
    bar.classList.remove('bg-danger','bg-warning','bg-success');
    if (strength <= 25) bar.classList.add('bg-danger');
    else if (strength <= 50) bar.classList.add('bg-warning');
    else bar.classList.add('bg-success');
  }
  function updateMatch() {
    const a = document.getElementById('newPasswordEmp').value || '';
    const b = document.getElementById('confirmPasswordEmp').value || '';
    const t = document.getElementById('passwordMatchEmp');
    if (!t) return;
    if (!b) { t.textContent = ''; return; }
    if (a === b && a !== '') {
      t.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i><b>Passwords match</b>';
      t.classList.remove('text-danger'); t.classList.add('text-success');
    } else {
      t.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i><b>Passwords do not match</b>';
      t.classList.remove('text-success'); t.classList.add('text-danger');
    }
  }
  const np = document.getElementById('newPasswordEmp');
  const cp = document.getElementById('confirmPasswordEmp');
  if (np) np.addEventListener('input', function(){ updateStrength(); updateMatch(); });
  if (cp) cp.addEventListener('input', updateMatch);

  const form = document.getElementById('changePasswordForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      const a = document.getElementById('newPasswordEmp').value || '';
      const b = document.getElementById('confirmPasswordEmp').value || '';
      if (a !== b) { e.preventDefault(); alert('New password and confirmation do not match!'); return false; }
      if (a.length < 8) { e.preventDefault(); alert('Password must be at least 8 characters long!'); return false; }
      return true;
    });
  }
});
</script>

<?php include '../includes/footer.php'; ?>
