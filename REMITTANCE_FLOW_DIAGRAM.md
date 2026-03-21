# 🔄 Daily Remittance Generation - Visual Flow Diagram

## Complete Process Flow

```
┌────────────────────────────────────────────────────────────────────┐
│                        ADMIN INITIATES                              │
│                                                                     │
│  Admin clicks: [💰 Generate Daily Remittances]                     │
└──────────────────────────┬─────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────────────┐
│                    CONFIRMATION MODAL                               │
│                                                                     │
│  ╔════════════════════════════════════════════════════╗            │
│  ║ 💰 Generate Daily Remittances                  [X]║            │
│  ╠════════════════════════════════════════════════════╣            │
│  ║                                                    ║            │
│  ║ ℹ️ This will create daily remittance transactions ║            │
│  ║   for all active drivers who don't have one today ║            │
│  ║                                                    ║            │
│  ║ Amount: ₦5,000.00 per driver                      ║            │
│  ║ Status: Pending                                   ║            │
│  ║                                                    ║            │
│  ║ [Cancel]  [✓ Generate Remittances]               ║            │
│  ╚════════════════════════════════════════════════════╝            │
└──────────────────────────┬─────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────────────┐
│                     SYSTEM PROCESSING                               │
│                                                                     │
│  1. Start Database Transaction                                     │
│  2. Get all active drivers                                         │
│  3. For each driver:                                               │
│                                                                     │
│     ┌──────────────────────────────────────────┐                  │
│     │  Check: Already has remittance today?    │                  │
│     └───────────┬──────────────────────────────┘                  │
│                 │                                                   │
│        ┌────────┴────────┐                                        │
│        │                 │                                         │
│       YES               NO                                         │
│        │                 │                                         │
│        ▼                 ▼                                         │
│   ┌─────────┐      ┌──────────┐                                  │
│   │  SKIP   │      │ CREATE   │                                   │
│   │ Driver  │      │ Transaction                                  │
│   └─────────┘      └──────────┘                                  │
│        │                 │                                         │
│        │                 ▼                                         │
│        │      ┌───────────────────────┐                           │
│        │      │ Type: daily_remittance│                           │
│        │      │ Amount: ₦5,000.00     │                           │
│        │      │ Status: Pending       │                           │
│        │      │ Reference: REMIT-XXX  │                           │
│        │      └───────────────────────┘                           │
│        │                 │                                         │
│        └────────┬────────┘                                        │
│                 │                                                   │
│                 ▼                                                   │
│           [Log Action]                                             │
│                 │                                                   │
└─────────────────┼───────────────────────────────────────────────┘
                  │
                  ▼
┌────────────────────────────────────────────────────────────────────┐
│                    RESULTS COMPILATION                              │
│                                                                     │
│  Count:                                                            │
│  • Generated: 25 drivers                                           │
│  • Skipped: 0 drivers (already had remittance)                    │
│  • Errors: 0 drivers                                               │
│                                                                     │
│  Action: Commit Database Transaction                               │
└──────────────────────────┬─────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────────────┐
│                    SUCCESS MESSAGE                                  │
│                                                                     │
│  ✅ Daily remittances generation completed:                        │
│     25 created, 0 skipped (already exists).                        │
│                                                                     │
│  [Redirect to Payments Page]                                       │
└──────────────────────────┬─────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────────────┐
│                    PAYMENTS TABLE UPDATED                           │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐ │
│  │ # │ Driver      │ Type      │ Amount   │ Status  │ Actions  │ │
│  ├───┼─────────────┼───────────┼──────────┼─────────┼──────────┤ │
│  │ 1 │ John Doe    │ Remittance│ ₦5,000   │ Pending │ [✓] [✗] │ │
│  │ 2 │ Jane Smith  │ Remittance│ ₦5,000   │ Pending │ [✓] [✗] │ │
│  │ 3 │ Mike Brown  │ Remittance│ ₦5,000   │ Pending │ [✓] [✗] │ │
│  │...│             │           │          │         │          │ │
│  │25 │ Sarah Jones │ Remittance│ ₦5,000   │ Pending │ [✓] [✗] │ │
│  └──────────────────────────────────────────────────────────────┘ │
│                                                                     │
│  Next Step: Admin approves each transaction manually               │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Duplicate Prevention Logic

```
┌──────────────────────────────────────────────────────────┐
│              FOR EACH DRIVER                              │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
           ┌────────────────────────┐
           │ Query Database:        │
           │ Find Transaction WHERE │
           │ • driver_id = X        │
           │ • type = 'daily_rem...'│
           │ • DATE(created) = TODAY│
           └────────────┬───────────┘
                        │
           ┌────────────┴────────────┐
           │                         │
          YES                       NO
  (Transaction Found)        (No Transaction)
           │                         │
           ▼                         ▼
    ┌──────────┐              ┌──────────┐
    │   SKIP   │              │  CREATE  │
    │  Driver  │              │   New    │
    │          │              │Transaction│
    │ Reason:  │              │          │
    │ Already  │              │ Amount:  │
    │ has one  │              │ ₦5,000   │
    │ for today│              │          │
    └────┬─────┘              └────┬─────┘
         │                         │
         ▼                         ▼
    ┌─────────┐              ┌──────────┐
    │ Counter:│              │ Counter: │
    │ Skipped │              │Generated │
    │   + 1   │              │   + 1    │
    └─────────┘              └──────────┘
         │                         │
         └────────────┬────────────┘
                      │
                      ▼
               [Continue to Next Driver]
```

---

## Branch Isolation Flow

```
                    ┌─────────────┐
                    │   USER      │
                    │  LOGS IN    │
                    └──────┬──────┘
                           │
              ┌────────────┴────────────┐
              │                         │
         Super Admin              Branch Manager
              │                         │
              ▼                         ▼
    ┌──────────────────┐      ┌──────────────────┐
    │  Get ALL Drivers │      │ Get ONLY Drivers │
    │  from ALL Branches│      │ from THEIR Branch│
    └─────────┬────────┘      └─────────┬────────┘
              │                         │
              │                         │
              └────────────┬────────────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │ Generate         │
                  │ Remittances for  │
                  │ Retrieved Drivers│
                  └──────────────────┘

Example:
  Super Admin clicks → Generates for 100 drivers (all branches)
  Branch A Manager clicks → Generates for 25 drivers (Branch A only)
  Branch B Manager clicks → Generates for 30 drivers (Branch B only)
```

---

## Error Handling Flow

```
┌──────────────────────────────────────────────┐
│         Processing Driver #5                  │
└────────────────┬─────────────────────────────┘
                 │
                 ▼
        ┌────────────────┐
        │ Try to Create  │
        │  Transaction   │
        └────────┬───────┘
                 │
    ┌────────────┴────────────┐
    │                         │
  SUCCESS                   ERROR
    │                         │
    ▼                         ▼
┌─────────┐            ┌──────────────┐
│Continue │            │ Catch Error  │
│to Next  │            │              │
│Driver   │            │ • Rollback   │
│         │            │   THIS driver│
│Generated│            │ • Log error  │
│  + 1    │            │ • Continue   │
└─────────┘            │   to next    │
                       │              │
                       │ Errors + 1   │
                       └──────┬───────┘
                              │
                              ▼
                    ┌───────────────────┐
                    │ Final Result:     │
                    │ 24 generated      │
                    │ 0 skipped         │
                    │ 1 failed          │
                    │                   │
                    │ ⚠️ Warning message│
                    └───────────────────┘
```

---

## Logging Flow

```
┌────────────────────────────────────────────────┐
│        Every Action is Logged                   │
└────────────────────┬───────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
        ▼                         ▼
┌──────────────┐          ┌──────────────┐
│  INFO Level  │          │ ERROR Level  │
└──────┬───────┘          └──────┬───────┘
       │                         │
       ▼                         ▼
  [Successful Actions]      [Failed Actions]
       │                         │
       ├─ Initiation             ├─ Driver errors
       ├─ Driver processed       ├─ Database errors
       ├─ Duplicate skipped      ├─ Validation errors
       ├─ Transaction created    └─ System errors
       └─ Completion summary

Log Location: storage/logs/laravel.log

Example Entries:
[INFO] Manual daily remittance generation initiated
[INFO] Daily remittance generated {driver_id: 23, amount: 5000}
[INFO] Skipped duplicate {driver_id: 24, existing_id: 145}
[ERROR] Error generating remittance {driver_id: 25, error: "..."}
[INFO] Generation completed {generated: 24, skipped: 0, errors: 1}
```

---

## Approval Workflow (After Generation)

```
┌────────────────────────────────────────────────┐
│     Transactions Created as "Pending"           │
└────────────────────┬───────────────────────────┘
                     │
                     ▼
        ┌─────────────────────────┐
        │   ADMIN REVIEWS          │
        │   Payments Page          │
        └─────────┬───────────────┘
                  │
         ┌────────┴────────┐
         │                 │
      APPROVE            REJECT
         │                 │
         ▼                 ▼
┌──────────────┐    ┌──────────────┐
│ Click [✓]    │    │ Click [✗]    │
│              │    │              │
│ Status:      │    │ Status:      │
│ Successful   │    │ Rejected     │
│              │    │              │
│ Record in:   │    │ No money     │
│ Company      │    │ recorded     │
│ Account      │    │              │
│              │    │ Driver       │
│ Driver       │    │ notified     │
│ credited     │    │              │
└──────────────┘    └──────────────┘
```

---

## Timeline Example

```
Day 1 - October 9, 2025
─────────────────────────────────────────────────

09:00 AM  Automatic system scheduled to run... ❌ FAILED
          (Server maintenance, cron job didn't execute)

09:30 AM  Branch Manager logs in
          Notices no remittances for today

09:31 AM  Clicks "Generate Daily Remittances"
          System processes 25 drivers
          ✅ 25 transactions created as "Pending"

09:32 AM  Manager starts approving transactions
          Transaction #1: John Doe - ✓ Approved
          Transaction #2: Jane Smith - ✓ Approved
          ... continues approving

10:00 AM  All 25 transactions approved
          Company account shows ₦125,000 income
          Drivers can see approved payments

10:05 AM  System Administrator tries to run again
          (Just to be safe)
          ✅ 0 created, 25 skipped (already exists)
          ✓ Duplicate prevention working!
```

---

## Comparison: Manual vs Automatic

```
┌─────────────────────────────────────────────────────────┐
│                    AUTOMATIC                             │
├─────────────────────────────────────────────────────────┤
│  • Scheduled via cron job                               │
│  • Runs at 2:00 AM daily                                │
│  • No user interaction needed                           │
│  • Creates remittances automatically                    │
│  • Logs everything                                      │
│                                                          │
│  ✅ Pros: Hands-off, consistent                         │
│  ❌ Cons: Might fail if server down                     │
└─────────────────────────────────────────────────────────┘

                           VS

┌─────────────────────────────────────────────────────────┐
│                     MANUAL                               │
├─────────────────────────────────────────────────────────┤
│  • Triggered by admin clicking button                   │
│  • Can run anytime                                      │
│  • Requires user interaction                            │
│  • Same result as automatic                             │
│  • Logs everything                                      │
│                                                          │
│  ✅ Pros: Reliable backup, run on demand                │
│  ❌ Cons: Requires human to remember                    │
└─────────────────────────────────────────────────────────┘

Best Practice: Use BOTH
• Let automatic run daily
• Use manual as backup when automatic fails
• Both prevent duplicates automatically
```

---

## System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    WEB INTERFACE                         │
│                                                          │
│  [Payments Page] → [Generate Button] → [Modal] → [POST]│
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│                 ROUTES (web.php)                         │
│                                                          │
│  POST /admin/payments/generate-daily-remittances        │
│  Middleware: auth, permission:approve payments          │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│           CONTROLLER (PaymentController.php)             │
│                                                          │
│  generateDailyRemittances() method                      │
│  • Get active drivers                                   │
│  • Check duplicates                                     │
│  • Create transactions                                  │
│  • Log everything                                       │
└────────────────────────┬────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
         ▼               ▼               ▼
┌──────────────┐  ┌─────────────┐  ┌─────────────┐
│   DATABASE   │  │    LOGS     │  │  SETTINGS   │
│              │  │             │  │             │
│ transactions │  │ laravel.log │  │ sys_settings│
│ drivers      │  │             │  │             │
│ users        │  │ [INFO]      │  │ remit_amt   │
└──────────────┘  │ [ERROR]     │  └─────────────┘
                  └─────────────┘
```

---

## Data Flow

```
INPUT → VALIDATION → PROCESSING → OUTPUT

1. INPUT
   ┌────────────────────┐
   │ • User clicks      │
   │ • CSRF token       │
   │ • User permissions │
   └──────────┬─────────┘
              │
2. VALIDATION
   ┌────────────────────┐
   │ • Check auth       │
   │ • Check permission │
   │ • Validate request │
   └──────────┬─────────┘
              │
3. PROCESSING
   ┌────────────────────┐
   │ • Get drivers      │
   │ • Check duplicates │
   │ • Create trans     │
   │ • Log actions      │
   └──────────┬─────────┘
              │
4. OUTPUT
   ┌────────────────────┐
   │ • Success message  │
   │ • Redirect         │
   │ • Updated table    │
   └────────────────────┘
```

---

**Visual Guide Version:** 1.0  
**Created:** October 9, 2025  
**Purpose:** Help understand the daily remittance generation flow
