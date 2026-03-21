# 🔍 API Logging Quick Reference

## Log Monitoring Commands

### View Real-Time Logs
```bash
tail -f storage/logs/laravel.log
```

### View Last 100 Lines
```bash
tail -n 100 storage/logs/laravel.log
```

### Search for Errors
```bash
grep "ERROR" storage/logs/laravel.log | tail -n 20
```

### Search for Specific User
```bash
grep "user_id.*45" storage/logs/laravel.log
```

### Search for Failed Logins
```bash
grep "Login failed" storage/logs/laravel.log
```

### Search by IP Address
```bash
grep "102.89.34.156" storage/logs/laravel.log
```

### Today's API Activity
```bash
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log | grep "API:"
```

---

## Common Log Patterns

### Successful Login
```
[2025-01-09 17:40:00] INFO: API: Login attempt {"email":"driver@example.com","ip":"102.89.34.156"}
[2025-01-09 17:40:01] INFO: API: Login successful {"user_id":45,"email":"driver@example.com","role":"Driver"}
```

### Failed Login
```
[2025-01-09 17:40:00] INFO: API: Login attempt {"email":"driver@example.com","ip":"102.89.34.156"}
[2025-01-09 17:40:01] WARNING: API: Login failed - invalid credentials {"email":"driver@example.com"}
```

### Wallet Funding Request
```
[2025-01-09 18:00:00] INFO: API: Wallet funding request initiated {"user_id":45,"amount":10000}
[2025-01-09 18:00:02] INFO: API: Payment proof uploaded {"driver_id":23,"file_path":"wallet-funding/proofs/abc.jpg"}
[2025-01-09 18:00:03] INFO: API: Wallet funding request created successfully {"request_id":156,"amount":10000}
```

### Maintenance Request
```
[2025-01-09 19:00:00] INFO: API: Maintenance request creation initiated {"user_id":45,"mechanic_id":3}
[2025-01-09 19:00:02] INFO: API: Issue photos uploaded {"driver_id":23,"photo_count":3,"paths":["..."]}
[2025-01-09 19:00:03] INFO: API: Maintenance request created successfully {"request_id":89}
```

### System Error
```
[2025-01-09 20:00:00] ERROR: API: Charging request creation error {"user_id":45,"error":"...","trace":"..."}
```

---

## Log Levels

| Level | Usage | Percentage |
|-------|-------|------------|
| **INFO** | Normal operations | ~65% |
| **WARNING** | Validation failures, not found | ~25% |
| **ERROR** | System exceptions | ~10% |

---

## Key Metrics to Monitor

### Authentication
- Login attempts vs successes
- Failed login patterns
- Logout frequency

### Wallet Operations
- Funding request volume
- Average funding amount
- Approval/rejection rates

### Maintenance Requests
- Request creation rate
- Photo upload success rate
- Average cost per request

### Charging Requests
- Request volume
- Average charging cost
- Completion time

---

## Alert Thresholds

### Critical (Immediate)
- ≥ 5 failed logins in 10 minutes
- Any ERROR level log
- Database connection failure

### Warning (1 hour)
- ≥ 20% validation failure rate
- File upload failures
- Unusual IP patterns

### Info (Daily)
- API usage summary
- Popular endpoints
- User activity trends

---

## Troubleshooting

### Issue: No logs appearing
```bash
# Check log file permissions
ls -la storage/logs/

# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Issue: Logs too large
```bash
# Archive old logs
tar -czf logs-$(date +%Y%m%d).tar.gz storage/logs/*.log

# Clear archived logs
> storage/logs/laravel.log
```

### Issue: Can't find specific event
```bash
# Use more specific grep patterns
grep -A 5 -B 5 "request_id.*156" storage/logs/laravel.log
```

---

## Production Best Practices

1. **Log Rotation**: Configure daily rotation
2. **Retention**: Keep 30-90 days
3. **Monitoring**: Use external service (Sentry, Papertrail)
4. **Alerts**: Configure Slack/email notifications
5. **Analysis**: Schedule daily log analysis

---

## Quick Stats

```bash
# Total API requests today
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log | grep "API:" | wc -l

# Total errors today
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log | grep "ERROR" | wc -l

# Unique users today
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log | grep -oP 'user_id":\K\d+' | sort -u | wc -l

# Most active user
grep "user_id" storage/logs/laravel.log | grep -oP 'user_id":\K\d+' | sort | uniq -c | sort -rn | head -1
```

---

**Last Updated:** January 9, 2025
