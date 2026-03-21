# ⚡ Quick Start Guide - Daily Remittance Manual Trigger

## 🎯 What Is This?

A one-click button that generates daily remittance transactions for all your active drivers when the automatic system fails.

---

## 🚀 How to Use (3 Simple Steps)

### **Step 1: Go to Payments Page**
```
Dashboard → Click "Payments" in sidebar
```

### **Step 2: Click the Green Button**
Look for this button at the top right of the transactions table:
```
[💰 Generate Daily Remittances]
```

### **Step 3: Confirm and Done!**
- Review the information in the popup
- Click "Generate Remittances"
- Wait for confirmation message
- Done! ✅

---

## ✅ What Happens

### **The System Will:**
1. ✅ Check all your active drivers
2. ✅ Create remittance for drivers who don't have one today
3. ✅ Skip drivers who already have one (safe to run multiple times)
4. ✅ Set all as "Pending" (you still need to approve them)
5. ✅ Show you results: "X created, Y skipped"

### **Example Results:**
```
✅ Success: Daily remittances generation completed: 
   25 created, 0 skipped (already exists).
```

This means 25 drivers got their daily remittance created.

---

## 🔒 Who Can Use This?

**Required Permission:** "Approve Payments"

**Typical Roles:**
- ✅ Super Admin (sees all drivers)
- ✅ Branch Manager (sees only their branch)
- ❌ Other staff (button won't show)

---

## 💡 Common Questions

### **Q: Can I run this multiple times?**
A: Yes! It's 100% safe. Duplicates are automatically prevented.

### **Q: Will it auto-approve transactions?**
A: No. Transactions are created as "Pending" and need manual approval like normal.

### **Q: What amount will be used?**
A: The amount set in Settings → Financial Settings → Daily Remittance Amount (currently ₦5,000.00)

### **Q: What if automatic already ran today?**
A: All drivers will be skipped. Message will say "0 created, X skipped"

### **Q: Can I change the amount?**
A: Yes! Go to Settings and update "Daily Remittance Amount"

---

## ⚠️ Important Notes

### **DO:**
- ✅ Use when automatic system fails
- ✅ Run once per day maximum
- ✅ Check the results message
- ✅ Approve transactions after generating

### **DON'T:**
- ❌ Run multiple times unnecessarily (duplicates prevented but wastes resources)
- ❌ Expect automatic approval (still need to approve manually)
- ❌ Forget to approve the generated transactions

---

## 📊 Visual Guide

### **Before (No Remittances):**
```
Transactions Table
┌──────────────────────────────────────┐
│ No transactions for today            │
│                                      │
│ (Empty or only old transactions)     │
└──────────────────────────────────────┘
```

### **After Clicking Generate:**
```
Transactions Table
┌──────────────────────────────────────┐
│ Transaction #1: John Doe - ₦5,000    │
│ Transaction #2: Jane Smith - ₦5,000  │
│ Transaction #3: Mike Brown - ₦5,000  │
│ ... (25 total transactions)          │
│                                      │
│ All Status: Pending (Yellow badge)   │
└──────────────────────────────────────┘
```

### **Next Step - Approve Them:**
```
For each transaction:
Click [✓ Approve] button
```

---

## 🎨 Button Location

```
┌─────────────────────────────────────────────────────┐
│ Payments Management                    🏠 Dashboard  │
├─────────────────────────────────────────────────────┤
│ [Filters Card]                                      │
├─────────────────────────────────────────────────────┤
│ All Transactions    [💰 Generate Daily Remittances] │ ← HERE!
├─────────────────────────────────────────────────────┤
│ # │ Driver │ Type │ Amount │ Status │ Actions       │
│ 1 │ John   │ ...  │ ₦5,000 │ ...    │ [✓] [✗]      │
└─────────────────────────────────────────────────────┘
```

---

## 📋 Approval Workflow

### **After Generation:**
1. **Generated** - Transactions created as "Pending" ⏳
2. **Review** - Check transactions in the table 👀
3. **Approve** - Click ✓ Approve for each one ✅
4. **Complete** - Transaction becomes "Successful" 🎉
5. **Company Account** - Money recorded as income 💰

---

## 🔍 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Button not visible | Ask admin for "approve payments" permission |
| "0 created, 0 skipped" | No active drivers found - check driver status |
| All skipped | Already generated today (normal if automatic ran) |
| Some failed | Check logs or contact technical support |

---

## 📞 Need Help?

### **Can't See Button:**
Contact your administrator to assign you "approve payments" permission

### **Generation Failed:**
Check the error message and contact technical support with:
- Error message shown
- Time you clicked
- Your username

### **Questions About Amount:**
Contact administrator to change in Settings → Financial Settings

---

## ✨ Pro Tips

### **Best Time to Use:**
- 🕐 Early morning if automatic didn't run overnight
- 🕒 After system maintenance
- 🕓 When starting a new pay period

### **Check First:**
```
Look at transactions table - if you see today's remittances,
you probably don't need to generate again.
```

### **After Generating:**
```
Don't forget to approve! Generated transactions need approval
just like driver-submitted ones.
```

---

## 🎯 Success Checklist

After clicking "Generate Daily Remittances":

- [ ] Saw popup with information
- [ ] Clicked "Generate Remittances"
- [ ] Saw success message with counts
- [ ] Refreshed page to see new transactions
- [ ] Verified transactions show "Pending" status
- [ ] Started approving transactions one by one

---

## 📈 Expected Results

### **For 25 Active Drivers:**
- **Time to generate:** ~5 seconds
- **Transactions created:** 25
- **All with status:** Pending
- **Amount each:** ₦5,000 (or your configured amount)
- **Total value:** ₦125,000

### **If Run Again Same Day:**
- **Time to process:** ~3 seconds
- **Transactions created:** 0
- **Skipped:** 25 (already exists)
- **Message:** "0 created, 25 skipped"

---

## 🎉 You're Ready!

That's all there is to it! The feature is designed to be:
- ✅ Simple (one click)
- ✅ Safe (duplicate prevention)
- ✅ Fast (generates in seconds)
- ✅ Reliable (fully logged)

**Just click the green button and let the system do the work!** 🚀

---

**Quick Start Version:** 1.0  
**For:** Administrators & Branch Managers  
**Last Updated:** October 9, 2025
