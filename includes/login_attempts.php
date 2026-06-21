<?php
/**
 * Login Attempt Limiting Functions
 * Prevents brute force attacks by limiting failed login attempts
 */

const LOGIN_ATTEMPT_LIMIT = 5;
const LOGIN_LOCKOUT_MINUTES = 5;
const LOGIN_LOCKOUT_SECONDS = LOGIN_LOCKOUT_MINUTES * 60;

/**
 * Ensure login attempt columns exist in the users table.
 */
function ensureLoginAttemptColumnsExist($db) {
    static $checked = false;
    if ($checked) {
        return true;
    }

    $checked = true;
    $sql = "SHOW COLUMNS FROM users LIKE 'login_attempts'";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $existing = $db->getSingle($stmt);
    if ($existing) {
        return true;
    }

    $sql = "ALTER TABLE `users`
            ADD COLUMN `login_attempts` int(11) DEFAULT 0 COMMENT 'Number of failed login attempts',
            ADD COLUMN `last_attempt_time` timestamp NULL DEFAULT NULL COMMENT 'Time of last failed login attempt',
            ADD COLUMN `lockout_until` timestamp NULL DEFAULT NULL COMMENT 'Time until account is locked out'";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    if ($db->execute($stmt) === false) {
        return false;
    }

    $sql = "ALTER TABLE `users` ADD KEY `idx_login_attempts` (`login_attempts`, `last_attempt_time`)";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return true;
    }

    $db->execute($stmt);
    return true;
}

/**
 * Get the current login attempt row for a user.
 */
function getUserLoginAttemptRow($db, $email) {
    if (!ensureLoginAttemptColumnsExist($db)) {
        return null;
    }

    $sql = "SELECT login_attempts, last_attempt_time, lockout_until FROM users WHERE email = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }

    return $db->getSingle($stmt, [$email]);
}

/**
 * Check if lockout is currently active.
 */
function isLockoutActive(array $user) {
    return !empty($user['lockout_until']) && strtotime($user['lockout_until']) > time();
}

/**
 * Reset attempt count when a previous lockout has expired.
 */
function clearExpiredLockout($db, $email, array $user = null) {
    if ($user === null) {
        $user = getUserLoginAttemptRow($db, $email);
    }

    if (!$user || empty($user['lockout_until'])) {
        return false;
    }

    if (strtotime($user['lockout_until']) <= time()) {
        $sql = "UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE email = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $db->execute($stmt, [$email]);
        return true;
    }

    return false;
}

/**
 * Check if user is currently locked out from login attempts.
 */
function isUserLockedOut($db, $email) {
    $user = getUserLoginAttemptRow($db, $email);
    if (!$user) {
        return false; // User doesn't exist, not locked out
    }

    return isLockoutActive($user);
}

/**
 * Get remaining lockout time in minutes.
 */
function getRemainingLockoutTime($db, $email) {
    $user = getUserLoginAttemptRow($db, $email);
    if (!$user || empty($user['lockout_until'])) {
        return 0;
    }

    $remaining = strtotime($user['lockout_until']) - time();
    return max(1, ceil($remaining / 60));
}

/**
 * Record a failed login attempt.
 */
function recordFailedLoginAttempt($db, $email) {
    $user = getUserLoginAttemptRow($db, $email);
    if (!$user) {
        return false; // User doesn't exist, don't record attempt
    }

    if (!empty($user['lockout_until']) && strtotime($user['lockout_until']) <= time()) {
        // Lockout expired, start count over
        $user['login_attempts'] = 0;
        $user['lockout_until'] = null;
    }

    $newAttempts = (int)$user['login_attempts'] + 1;
    $lockoutUntil = null;

    if ($newAttempts >= LOGIN_ATTEMPT_LIMIT) {
        $lockoutUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_SECONDS);
    }

    $sql = "UPDATE users SET login_attempts = ?, last_attempt_time = NOW(), lockout_until = ? WHERE email = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $result = $db->execute($stmt, [$newAttempts, $lockoutUntil, $email]);
    return $result !== false;
}

/**
 * Reset login attempts after successful login.
 */
function resetLoginAttempts($db, $email) {
    $sql = "UPDATE users SET login_attempts = 0, last_attempt_time = NULL, lockout_until = NULL WHERE email = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $result = $db->execute($stmt, [$email]);
    return $result !== false;
}

/**
 * Get current login attempt status for display.
 */
function getLoginAttemptStatus($db, $email) {
    $user = getUserLoginAttemptRow($db, $email);
    if (!$user) {
        return ['attempts' => 0, 'remaining_time' => 0];
    }

    $remainingTime = 0;
    if (!empty($user['lockout_until'])) {
        $remaining = strtotime($user['lockout_until']) - time();
        $remainingTime = max(0, ceil($remaining / 60));
    }

    return [
        'attempts' => (int)$user['login_attempts'],
        'remaining_time' => $remainingTime
    ];
}

/**
 * Get remaining attempts before lockout.
 */
function getRemainingAttempts($db, $email) {
    $status = getLoginAttemptStatus($db, $email);
    if ($status['remaining_time'] > 0) {
        return 0;
    }

    return max(0, LOGIN_ATTEMPT_LIMIT - $status['attempts']);
}
