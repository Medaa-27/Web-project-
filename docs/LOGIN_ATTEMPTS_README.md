# Login Attempts Security Feature

This feature adds brute force protection to your PHP login system by limiting failed login attempts.

## Features

- **5 failed attempts** trigger a lockout
- **10-minute lockout** period after 5 failed attempts
- **Automatic reset** after successful login
- **Real-time feedback** showing remaining attempts
- **Database tracking** of attempts and timestamps

## Implementation Steps

### 1. Database Changes

Run the SQL script to add security columns to your users table:

```sql
-- Run this in your MySQL database
ALTER TABLE `users`
ADD COLUMN `login_attempts` int(11) DEFAULT 0 COMMENT 'Number of failed login attempts',
ADD COLUMN `last_attempt_time` timestamp NULL DEFAULT NULL COMMENT 'Time of last failed login attempt',
ADD COLUMN `lockout_until` timestamp NULL DEFAULT NULL COMMENT 'Time until account is locked out';

ALTER TABLE `users`
ADD KEY `idx_login_attempts` (`login_attempts`, `last_attempt_time`);
```

### 2. Code Integration

The login attempt limiting is already integrated into `../login.php`. The system:

- **Checks lockout status** before processing login
- **Records failed attempts** when password is wrong
- **Resets attempts** when login succeeds
- **Shows helpful error messages** to users

### 3. How It Works

```
User tries to login:
├── Check if account is locked out
│   ├── YES: Show "try again in X minutes" message
│   └── NO: Continue with normal login process
│       ├── Login successful: Reset attempts to 0
│       └── Login failed: Increment attempts counter
│           ├── If attempts >= 5: Lock account for 10 minutes
│           └── Show remaining attempts message
```

## Testing

### Option 1: Use the Test Script

1. Edit `../test_login_attempts.php` and change the email to a real user
2. Visit `../test_login_attempts.php` in your browser
3. Watch how attempts are tracked and lockouts work

### Option 2: Manual Testing

1. Try logging in with wrong passwords 5 times
2. On the 5th attempt, account gets locked for 10 minutes
3. Try logging in with correct password - should work and reset attempts
4. Wait 10 minutes or use test script to reset manually

## Security Benefits

- **Prevents brute force attacks** - Automated password guessing
- **Slows down attackers** - 10-minute delays between attempts
- **User-friendly** - Clear messages about remaining attempts
- **Automatic recovery** - Successful login resets everything
- **Database logging** - Track suspicious activity

## Error Messages

The system shows different messages based on the situation:

- `"Too many failed login attempts. Please try again in X minutes."` - Account locked
- `"Invalid email or password. X attempts remaining."` - Normal failed attempt
- `"Please enter both email and password"` - Missing fields

## Files Modified

- `../login.php` - Added login attempt checking and recording
- `../includes/login_attempts.php` - Core security functions
- `../login_attempts_security.sql` - Database schema changes

## For Students

This is a good example of:
- **Security best practices** in web applications
- **Database schema design** for security features
- **PHP function organization** and reusability
- **User experience** considerations in security features
- **Time-based security** measures

## Troubleshooting

**Problem:** Users can't login even after 10 minutes
**Solution:** Check server time settings and database timezone

**Problem:** Attempts not resetting after successful login
**Solution:** Verify the `resetLoginAttempts()` function is being called

**Problem:** Lockout times seem wrong
**Solution:** Check PHP `time()` function and MySQL `NOW()` consistency
