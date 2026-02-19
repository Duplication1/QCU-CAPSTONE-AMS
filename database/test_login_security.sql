-- Test the login security system
-- Run these queries to test the functionality

-- 1. Check current status of a test user (e.g., Student One with ID 22-0305)
SELECT id_number, full_name, failed_login_attempts, account_locked_until 
FROM users 
WHERE id_number = '22-0305';

-- 2. Manually test by setting failed attempts
-- UPDATE users SET failed_login_attempts = 1, account_locked_until = NULL WHERE id_number = '22-0305';

-- 3. Check if account is locked
SELECT 
    id_number, 
    failed_login_attempts,
    account_locked_until,
    CASE 
        WHEN account_locked_until IS NOT NULL AND account_locked_until > NOW() 
        THEN CONCAT('Locked for ', CEIL(TIMESTAMPDIFF(MINUTE, NOW(), account_locked_until)), ' minutes')
        ELSE 'Not locked'
    END as lock_status
FROM users 
WHERE id_number = '22-0305';

-- 4. Reset the failed attempts (for testing)
-- UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id_number = '22-0305';
