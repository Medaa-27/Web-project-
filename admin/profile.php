<?php
require_once '../includes/config.php';
$title = "Admin Profile - Aksum Rental System";

// Require admin login
$session->requireRole('admin');
$admin_id = $session->getUserId();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        // Update profile in database
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $result = $db->execute($stmt, [$full_name, $email, $phone, $address, $admin_id]);
        
        if ($result) {
            // Update session
            $_SESSION['user_name'] = $full_name;
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile.";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $error_message = "Password must be at least 8 characters and include letters and numbers.";
        } else {
            $sql = "SELECT password_hash FROM users WHERE user_id = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            $user = $db->getSingle($stmt, [$admin_id]);
            $stored_hash = trim($user['password_hash'] ?? '');
            $curr = trim($current_password);
            $verified = false;
            if ($stored_hash && strpos($stored_hash, '$') === 0) {
                $verified = password_verify($curr, $stored_hash);
            }
            if ($verified) {
                if (password_verify($new_password, $stored_hash)) {
                    $error_message = "New password must be different from current password.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $result = $db->execute($stmt, [$hashed_password, $admin_id]);
                    if ($result) {
                        $success_message = "Password changed successfully!";
                    } else {
                        $error_message = "Failed to change password.";
                    }
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    }
}

// Get current user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$current_user = $db->getSingle($stmt, [$admin_id]);

include '../includes/header.php';
?>

<!-- Admin Profile Content -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="rounded-3 p-4 mb-4 text-white" style="background: linear-gradient(90deg, #0d6efd 0%, #6610f2 100%);">
                <div class="d-flex align-items-center">
                    <img src="<?php echo !empty($current_user['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($current_user['profile_picture']) : 'https://via.placeholder.com/80x80?text=' . urlencode(substr($current_user['full_name'], 0, 2)); ?>" 
                         alt="Admin Avatar" 
                         class="rounded-circle border border-3 border-light me-3" 
                         style="width: 80px; height: 80px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <h1 class="h4 mb-0 text-white"><?php echo htmlspecialchars($current_user['full_name'] ?? 'Admin User'); ?></h1>
                            <span class="badge bg-success">Active</span>
                            <span class="badge bg-dark">Administrator</span>
                        </div>
                        <div class="mt-1">
                            <span class="me-3"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($current_user['email'] ?? ''); ?></span>
                            <?php if (!empty($current_user['phone'])): ?>
                                <span class="me-3"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($current_user['phone']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($current_user['address'])): ?>
                                <span class="me-3"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($current_user['address']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <input type="file" id="profilePictureInput" accept="image/*" style="display:none;">
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="document.getElementById('profilePictureInput').click()">
                            <i class="fas fa-camera me-1"></i>Change Avatar
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-xl-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0"><i class="fas fa-user-circle me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label fw-semibold">Full Name</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>" 
                                           placeholder="Enter your full name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" 
                                           placeholder="your.email@example.com" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" 
                                           placeholder="+251 9XX XXX XXX">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label fw-semibold">Address</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?php echo htmlspecialchars($current_user['address'] ?? ''); ?>" 
                                           placeholder="Aksum, Ethiopia">
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end pt-2">
                                <button type="submit" name="update_profile" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save me-1"></i>Update Profile
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-1"></i>Security
                                </button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0"><i class="fas fa-shield-alt me-2"></i>System Permissions</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info py-2 mb-3">
                                <small><i class="fas fa-info-circle me-2"></i>Full administrator access to all features</small>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="d-flex align-items-center p-2 bg-light rounded">
                                        <i class="fas fa-users text-primary me-2" style="font-size: 0.9rem;"></i>
                                        <small><strong>Users</strong></small>
                                        <i class="fas fa-check-circle text-success ms-auto" style="font-size: 0.8rem;"></i>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center p-2 bg-light rounded">
                                        <i class="fas fa-home text-primary me-2" style="font-size: 0.9rem;"></i>
                                        <small><strong>Properties</strong></small>
                                        <i class="fas fa-check-circle text-success ms-auto" style="font-size: 0.8rem;"></i>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center p-2 bg-light rounded">
                                        <i class="fas fa-chart-bar text-primary me-2" style="font-size: 0.9rem;"></i>
                                        <small><strong>Reports</strong></small>
                                        <i class="fas fa-check-circle text-success ms-auto" style="font-size: 0.8rem;"></i>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center p-2 bg-light rounded">
                                        <i class="fas fa-cog text-primary me-2" style="font-size: 0.9rem;"></i>
                                        <small><strong>Settings</strong></small>
                                        <i class="fas fa-check-circle text-success ms-auto" style="font-size: 0.8rem;"></i>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center p-2 bg-light rounded">
                                        <i class="fas fa-credit-card text-primary me-2" style="font-size: 0.9rem;"></i>
                                        <small><strong>Payments</strong></small>
                                        <i class="fas fa-check-circle text-success ms-auto" style="font-size: 0.8rem;"></i>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center p-2 bg-light rounded">
                                        <i class="fas fa-history text-primary me-2" style="font-size: 0.9rem;"></i>
                                        <small><strong>Audit Logs</strong></small>
                                        <i class="fas fa-check-circle text-success ms-auto" style="font-size: 0.8rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Admin Activity</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get recent admin activities
                    $sql = "SELECT * FROM audit_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                    $stmt = $db->prepare($sql);
                    $activities = $db->getMultiple($stmt, [$admin_id]);
                    
                    if (empty($activities)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-history fa-3x mb-3"></i>
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($activities as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                            <div class="text-muted small"><?php echo htmlspecialchars($activity['details'] ?? ''); ?></div>
                                        </div>
                                        <small class="text-muted"><?php echo formatDate($activity['created_at'], 'M d, H:i'); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrentAdmin">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleNewAdmin">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text"><i class="fas fa-shield-alt me-1"></i>Password must be at least 8 characters long</div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar" id="passwordStrengthAdmin" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmAdmin">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text" id="passwordMatchAdmin"></div>
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

<script>
document.getElementById('profilePictureInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgs = document.querySelectorAll('img[alt="Admin Avatar"]');
            imgs.forEach(img => img.src = e.target.result);
        }
        reader.readAsDataURL(file);
        
        const formData = new FormData();
        formData.append('profile_picture', file);
        
        fetch('../api/upload-profile-picture.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>Profile picture updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.container-fluid').firstChild);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error uploading profile picture');
        });
    }
});

// Admin password UI interactions
document.addEventListener('DOMContentLoaded', function() {
  const currentInput = document.getElementById('current_password');
  const newInput = document.getElementById('new_password');
  const confirmInput = document.getElementById('confirm_password');
  const strengthBar = document.getElementById('passwordStrengthAdmin');
  const matchText = document.getElementById('passwordMatchAdmin');
  
  function toggleVisibility(btnId, inputEl) {
    const btn = document.getElementById(btnId);
    btn.addEventListener('click', function() {
      const icon = this.querySelector('i');
      if (inputEl.type === 'password') {
        inputEl.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        inputEl.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  }
  
  toggleVisibility('toggleCurrentAdmin', currentInput);
  toggleVisibility('toggleNewAdmin', newInput);
  toggleVisibility('toggleConfirmAdmin', confirmInput);
  
  function updateStrength() {
    const pwd = newInput.value;
    let strength = 0;
    if (pwd.length >= 8) strength += 25;
    if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength += 25;
    if (/[0-9]/.test(pwd)) strength += 25;
    if (/[^a-zA-Z0-9]/.test(pwd)) strength += 25;
    strengthBar.style.width = strength + '%';
    strengthBar.classList.remove('bg-danger','bg-warning','bg-success');
    if (strength <= 25) strengthBar.classList.add('bg-danger');
    else if (strength <= 50) strengthBar.classList.add('bg-warning');
    else strengthBar.classList.add('bg-success');
  }
  
  function updateMatch() {
    const a = newInput.value;
    const b = confirmInput.value;
    if (!b) { matchText.textContent = ''; return; }
    if (a === b) {
      matchText.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Passwords match';
      matchText.classList.remove('text-danger'); matchText.classList.add('text-success');
    } else {
      matchText.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Passwords do not match';
      matchText.classList.remove('text-success'); matchText.classList.add('text-danger');
    }
  }
  
  newInput.addEventListener('input', function() { updateStrength(); updateMatch(); });
  confirmInput.addEventListener('input', updateMatch);
  
  // Client-side validation
  const form = document.querySelector('#changePasswordModal form');
  form.addEventListener('submit', function(e) {
    if (newInput.value !== confirmInput.value) {
      e.preventDefault();
      alert('New password and confirmation do not match!');
      return false;
    }
    if (newInput.value.length < 8) {
      e.preventDefault();
      alert('Password must be at least 8 characters long!');
      return false;
    }
    return true;
  });
});
</script>

<?php include '../includes/footer.php'; ?>
