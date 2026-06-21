<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

$title = "Change Password - Aksum Rental System";

// Must be logged in and forced to change
if (!$session->isLoggedIn() || !isset($_SESSION['force_change'])) {
    $session->redirectToDashboard();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error = "Please enter a new password.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $user_id = $session->getUserId();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password_hash = ?, force_password_change = 0 WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        
        if ($db->execute($stmt, [$hash, $user_id])) {
            unset($_SESSION['force_change']);
            
            // Log activity
            $session->logActivity($user_id, 'password_change', 'users', $user_id);
            
            // Add notification
            try {
                $notif_sql = "INSERT INTO notifications (user_id, title, message, type) 
                             VALUES (?, 'Password Updated', 'Your password has been successfully updated.', 'success')";
                $notif_stmt = $db->prepare($notif_sql);
                $db->execute($notif_stmt, [$user_id]);
            } catch (Exception $e) {}

            $_SESSION['success'] = "Password changed successfully! You can now access your account.";
            $session->redirectToDashboard();
        } else {
            $error = "Failed to update password. Please try again.";
        }
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
    <style>
        body.auth-container {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        .auth-card {
            background: #ffffff; border-radius: 24px; padding: 2.5rem;
            width: 100%; max-width: 480px; box-shadow: 0 20px 50px rgba(0,0,0,0.08);
        }
        .auth-logo {
            width: 54px; height: 54px; background: #2563eb; color: white;
            border-radius: 16px; display: flex; align-items: center;
            justify-content: center; font-size: 1.35rem; margin: 0 auto 1rem;
        }
    </style>
</head>
<body class="auth-container">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="auth-logo"><i class="fas fa-lock"></i></div>
            <h1 class="h3 fw-bold">Change Password</h1>
            <p class="text-muted small">Please set a new password for your account security.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key text-muted"></i></span>
                    <input type="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Confirm New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check text-muted"></i></span>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8" placeholder="Confirm your password">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Update Password</button>
            <div class="text-center mt-3">
                <a href="logout.php" class="text-muted small">Logout and try later</a>
            </div>
        </form>
    </div>
</body>
</html>
