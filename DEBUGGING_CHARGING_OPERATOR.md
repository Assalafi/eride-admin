# 🔍 Debugging Charging Operator API

## Comprehensive Logging Added

All ChargingOperatorController endpoints now have detailed logging at every step.

## 📝 Log Locations

### Development (Laravel Logs):
```bash
tail -f storage/logs/laravel.log
```

### XAMPP Logs:
```bash
# Apache Error Log
tail -f /Applications/XAMPP/xamppfiles/logs/error_log

# PHP Error Log  
tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log
```

## 🔎 What's Being Logged

### 1. **GET /api/charging-operator/requests**
```
=== GET APPROVED REQUESTS START ===
→ User authenticated (ID, name, email, role, branch_id)
→ Fetching charging requests for branch
→ Charging requests retrieved (count)
```

### 2. **GET /api/charging-operator/requests/{id}**
```
=== GET CHARGING DETAILS ===
→ User requesting charging details
→ Charging request details lookup (found: yes/no)
→ Returning charging request details
```

### 3. **POST /api/charging-operator/requests/{id}/start**
```
=== START CHARGING REQUEST ===
→ User attempting to start charging
→ Charging request lookup (found: yes/no)
→ Checking branch access (driver_branch vs user_branch)
→ Checking request status
→ Charging session started successfully (with timestamp)
```

### 4. **POST /api/charging-operator/requests/{id}/complete**
```
=== COMPLETE CHARGING REQUEST ===
→ User attempting to complete charging + Input data
→ Charging request lookup for completion
→ Checking branch access for completion
→ Checking status for completion
→ Validation errors (if any)
→ Charging session completed successfully (duration, battery level)
```

## 🚨 Error Logs Include:

- **Unauthorized access attempts** (wrong role)
- **Request not found** (invalid ID)
- **Branch mismatch** (operator trying to access other branch requests)
- **Invalid status** (trying to start non-approved or complete non-in-progress)
- **Validation failures** (battery level out of range, etc.)

## 📊 How to Debug

### Step 1: Open Log File
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/eRide\ system/eRide_admin_web
tail -f storage/logs/laravel.log
```

### Step 2: Try Login in App
Watch for:
```
=== GET APPROVED REQUESTS START ===
User authenticated: {user_id, name, email, role, branch_id}
```

### Step 3: Check Role
Look for `role` in the log. Should be:
```
"role": "Charging Station Operator"
```

If you see:
```
"role": "Driver"  ❌
"role": "Admin"   ❌
"role": "Manager" ❌
```
Then the user doesn't have the correct role!

### Step 4: Check Branch ID
```
"branch_id": 1  ← Operator's branch
```

Should match with:
```
driver_branch: 1  ← Driver's branch
```

If they don't match, the operator won't see that request.

## 🐛 Common Issues & Solutions

### Issue 1: "Connection error: FormatException"
**Cause**: API returning HTML instead of JSON  
**Check**: Look for PHP errors in log before the API call  
**Solution**: Fix the PHP error (syntax, missing class, etc.)

### Issue 2: No requests showing
**Check log for**:
```
Fetching charging requests for branch: {"branch_id": X}
Charging requests retrieved: {"count": 0}
```

**Possible causes**:
- No approved requests in the system
- Branch mismatch (operator branch ≠ driver branch)
- Requests have wrong status

**Solution**:
```sql
-- Check if there are approved requests
SELECT * FROM charging_requests WHERE status = 'approved';

-- Check driver's branch
SELECT d.id, d.full_name, d.branch_id 
FROM charging_requests cr
JOIN drivers d ON cr.driver_id = d.id
WHERE cr.status = 'approved';
```

### Issue 3: "Unauthorized" error
**Check log for**:
```
Unauthorized access attempt: {"user_role": "Driver"}
```

**Cause**: User role is not "Charging Station Operator"  
**Solution**: Update user role in database:
```sql
UPDATE users SET role = 'Charging Station Operator' WHERE email = 'charging@gmail.com';
```

### Issue 4: Can't start charging
**Check log for**:
```
Invalid status for starting: {
    "current_status": "pending",
    "required_status": "approved"
}
```

**Cause**: Request status is not "approved"  
**Solution**: Manager must approve the request first

### Issue 5: Can't complete charging
**Check log for**:
```
Invalid status for completion: {
    "current_status": "approved",
    "required_status": "in_progress"
}
```

**Cause**: Request status is not "in_progress"  
**Solution**: Start the charging session first

## 📋 Test Checklist

- [ ] Login shows user details in log
- [ ] User role is "Charging Station Operator"
- [ ] User has valid branch_id
- [ ] GET requests shows count > 0
- [ ] Can view request details
- [ ] Can start charging (status: approved → in_progress)
- [ ] Can complete charging (status: in_progress → completed)

## 🔧 Quick Fixes

### Fix 1: Create Test Operator User
```sql
INSERT INTO users (name, email, password, role, branch_id, created_at, updated_at)
VALUES (
    'Test Operator',
    'operator@test.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'Charging Station Operator',
    1,
    NOW(),
    NOW()
);
```

### Fix 2: Create Test Approved Request
```sql
UPDATE charging_requests 
SET status = 'approved',
    approved_by = 1,
    approved_at = NOW()
WHERE id = 1;
```

### Fix 3: Reset Request Status
```sql
-- Reset to approved (for testing start)
UPDATE charging_requests 
SET status = 'approved', 
    charging_start = NULL
WHERE id = 1;

-- Reset to in_progress (for testing complete)
UPDATE charging_requests 
SET status = 'in_progress', 
    charging_start = NOW()
WHERE id = 1;
```

## 📞 Still Having Issues?

Check the logs and look for:
1. The **=== HEADER ===** markers to identify which endpoint is being called
2. **Error** or **Warning** level logs
3. Any **PHP Fatal Errors** or **Exceptions**
4. **Validation failed** messages with specific error details

The logs will tell you exactly what's going wrong! 🎯
