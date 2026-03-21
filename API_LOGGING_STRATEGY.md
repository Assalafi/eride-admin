# eRide Driver API - Logging Strategy

## 📋 Overview

All API endpoints now include comprehensive logging for monitoring, debugging, and audit trails.

---

## 🎯 Logging Levels

### **INFO** - Normal Operations
- API endpoint access
- Successful operations
- Data retrieval
- User actions

### **WARNING** - Recoverable Issues
- Validation failures
- Invalid credentials
- Resource not found
- Business rule violations

### **ERROR** - Critical Failures
- System exceptions
- Database errors
- File upload failures
- Unexpected crashes

---

## 📊 Log Structure

### **Standard Log Entry**
```php
Log::info('API: Operation description', [
    'user_id' => $user->id,
    'driver_id' => $driver->id,
    'relevant_data' => $value,
    'ip' => $request->ip(),
    'timestamp' => now(),
]);
```

---

## 🔐 Authentication & Profile Logs

### **Login Attempts**
```
INFO: API: Login attempt
WARNING: API: Login failed - invalid credentials
INFO: API: Login successful
```

### **Profile Operations**
```
INFO: API: Driver dashboard accessed
INFO: API: Driver profile accessed
INFO: API: Profile update attempted
INFO: API: Profile updated successfully
INFO: API: Password change attempted
WARNING: API: Password change failed - incorrect current password
INFO: API: Password changed successfully
```

---

## 💰 Wallet & Transaction Logs

### **Wallet Operations**
```
INFO: API: Wallet accessed
INFO: API: Wallet loaded successfully
INFO: API: Wallet funding request initiated
INFO: API: Payment proof uploaded (file_path logged)
INFO: API: Wallet funding request created successfully
```

### **Transaction Operations**
```
INFO: API: Daily remittance submitted
INFO: API: Transaction recorded (reference, amount logged)
```

**Key Data Logged:**
- Amount
- Payment method
- Transaction reference
- File upload paths
- Status changes

---

## 🔧 Maintenance Request Logs

### **Request Lifecycle**
```
INFO: API: Maintenance request creation initiated
INFO: API: Issue photos uploaded (count, paths logged)
INFO: API: Maintenance request created successfully
WARNING: API: No vehicle assigned for maintenance
ERROR: API: Maintenance request creation failed
```

**Key Data Logged:**
- Mechanic ID
- Vehicle ID
- Issue description length
- Number of photos
- Total cost (if applicable)
- Status transitions

---

## ⚡ Charging Request Logs

### **Request Lifecycle**
```
INFO: API: Charging request creation initiated
INFO: API: Charging receipt uploaded
INFO: API: Charging request created successfully
WARNING: API: No vehicle assigned for charging
ERROR: API: Charging request creation failed
```

**Key Data Logged:**
- Charging cost
- Payment receipt path
- Vehicle plate number
- Status transitions
- Completion timestamps

---

## 🚗 Vehicle Assignment Logs

### **Vehicle Operations**
```
INFO: API: Current vehicle accessed
INFO: API: Vehicle assignment history accessed
```

**Key Data Logged:**
- Vehicle plate number
- Assignment ID
- Assignment dates

---

## 🛡️ Security & Audit Logs

### **Failed Actions**
```
WARNING: API: Driver profile not found (user_id logged)
WARNING: API: Validation failed (errors logged)
WARNING: API: Unauthorized access attempt
```

### **File Uploads**
```
INFO: API: File uploaded successfully (path, size, type logged)
ERROR: API: File upload failed (error details logged)
```

### **Database Transactions**
```
INFO: API: Transaction started
INFO: API: Transaction committed
ERROR: API: Transaction rolled back (error logged)
```

---

## 📍 IP Address Tracking

All endpoint access logs include:
```php
'ip' => $request->ip()
```

This helps identify:
- Geographic access patterns
- Suspicious activity
- Multiple device usage
- API abuse

---

## 🔍 Error Tracking

### **Exception Handling**
```php
catch (\Exception $e) {
    Log::error('API: Operation error', [
        'user_id' => $request->user()->id ?? null,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
```

---

## 📈 Performance Monitoring

### **Query Logging**
- Database query counts
- Execution time for critical operations
- API response times

### **File Operations**
- Upload durations
- File sizes
- Storage paths

---

## 🔐 Sensitive Data Protection

### **NOT Logged** (Security)
- Passwords (plain or hashed)
- API tokens
- Full credit card numbers
- Personal identification numbers

### **Logged** (Audit Trail)
- User IDs
- Driver IDs
- Transaction references
- Status changes
- IP addresses
- Timestamps

---

## 📊 Log Analysis & Monitoring

### **Key Metrics to Track**

1. **API Usage**
   - Requests per endpoint
   - Most active users
   - Peak usage times

2. **Error Rates**
   - Failed requests by endpoint
   - Validation error patterns
   - System error frequency

3. **User Behavior**
   - Login frequency
   - Feature usage patterns
   - Transaction volumes

4. **Performance**
   - Response times
   - Slow queries
   - File upload speeds

---

## 🛠️ Log Storage & Rotation

### **Laravel Log Configuration**
```env
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_SLACK_WEBHOOK_URL=your-slack-webhook
```

### **Log Files**
- `storage/logs/laravel.log` - Main application log
- Daily rotation recommended
- Keep logs for 30-90 days minimum

### **Production Recommendations**
- Use external logging service (Papertrail, Loggly, Sentry)
- Set up log aggregation
- Configure alerts for ERROR level logs
- Monitor disk space for log files

---

## 🔔 Alert Configuration

### **Critical Alerts** (Immediate Notification)
- Multiple login failures
- System errors (500 responses)
- Database connection failures
- File storage issues

### **Warning Alerts** (Daily Summary)
- High validation error rates
- Suspicious IP patterns
- Unusual transaction patterns

### **Info Alerts** (Weekly Summary)
- API usage statistics
- User activity reports
- Performance metrics

---

## 📝 Log Examples

### **Successful Wallet Funding Request**
```
[2025-01-09 17:40:00] INFO: API: Wallet funding request initiated
{
    "user_id": 45,
    "amount": 10000.00,
    "payment_method": "bank_transfer",
    "ip": "102.89.34.156"
}

[2025-01-09 17:40:02] INFO: API: Payment proof uploaded
{
    "driver_id": 23,
    "file_path": "wallet-funding/proofs/abc123.jpg"
}

[2025-01-09 17:40:03] INFO: API: Wallet funding request created successfully
{
    "request_id": 156,
    "driver_id": 23,
    "amount": 10000.00,
    "payment_method": "bank_transfer"
}
```

### **Failed Maintenance Request**
```
[2025-01-09 17:45:00] INFO: API: Maintenance request creation initiated
{
    "user_id": 52,
    "mechanic_id": 3,
    "ip": "102.89.34.201"
}

[2025-01-09 17:45:01] WARNING: API: No vehicle assigned for maintenance
{
    "driver_id": 31,
    "user_id": 52
}
```

### **System Error**
```
[2025-01-09 18:00:00] ERROR: API: Charging request creation failed
{
    "user_id": 67,
    "error": "SQLSTATE[HY000]: General error: 1364 Field 'vehicle_id' doesn't have a default value",
    "trace": "...",
    "file": "/path/to/DriverApiController.php",
    "line": 756
}
```

---

## 🎯 Best Practices

### **DO**
✅ Log all data modifications  
✅ Include context (user_id, driver_id, etc.)  
✅ Use appropriate log levels  
✅ Log before and after critical operations  
✅ Include IP addresses for security  
✅ Log file operations with paths  
✅ Use structured logging (arrays)  

### **DON'T**
❌ Log sensitive data (passwords, tokens)  
❌ Log excessive data in production  
❌ Use generic error messages  
❌ Ignore exception stack traces  
❌ Log at DEBUG level in production  
❌ Store logs indefinitely without rotation  

---

## 🔄 Maintenance Tasks

### **Daily**
- Check ERROR logs
- Monitor disk space
- Review critical alerts

### **Weekly**
- Analyze API usage patterns
- Review validation error trends
- Check performance metrics

### **Monthly**
- Archive old logs
- Update alerting rules
- Review logging strategy effectiveness

---

## 📞 Integration with Monitoring Tools

### **Recommended Tools**

1. **Sentry** - Error tracking
2. **Papertrail** - Log aggregation
3. **New Relic** - APM & performance
4. **Datadog** - Infrastructure monitoring
5. **Slack/Email** - Alert notifications

### **Setup Example (Sentry)**
```env
SENTRY_LARAVEL_DSN=your-sentry-dsn
SENTRY_TRACES_SAMPLE_RATE=0.2
```

---

## 🎓 Training & Documentation

### **For Developers**
- Review this logging strategy
- Follow naming conventions
- Use consistent log formats
- Test logging in development

### **For Operations**
- Know where logs are stored
- Understand alert configurations
- Have access to monitoring tools
- Know escalation procedures

---

**Version:** 1.0  
**Last Updated:** January 9, 2025  
**Maintained by:** eRide Development Team
