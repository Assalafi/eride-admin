# Company Account Transaction Flow

## 💰 COMPANY INCOME SOURCES

### 1. Daily Remittance (Cash Payment)
**Payment Method:** Cash (NO wallet deduction)
**Flow:**
```
Driver gives cash ₦20,000 → Manager receives → Creates transaction → 
Manager approves → Company Account credited ₦20,000
```
**Implementation:**
- Controller: `PaymentController::approve()`
- Category: `daily_remittance`
- Type: `income`
- Wallet: ❌ NOT deducted

---

### 2. EV Charging (Direct Payment)
**Payment Method:** Direct payment with receipt (NO wallet deduction)
**Flow:**
```
Driver pays charging station directly → Takes receipt → 
Uploads receipt with request → Admin starts charging → 
Admin completes charging → Company Account credited ₦5,000
```
**Implementation:**
- Controller: `ChargingRequestController::completeCharging()`
- Category: `charging`
- Type: `income`
- Wallet: ❌ NOT deducted
- Receipt: ✅ Required (uploaded by driver)

---

### 3. Maintenance Parts (Wallet Payment)
**Payment Method:** Wallet deduction (ONLY transaction that deducts from wallet)
**Flow:**
```
Driver requests maintenance → Manager approves → 
Store confirms parts → Parts dispensed → 
Driver wallet debited ₦15,000 → Company Account credited ₦15,000
```
**Implementation:**
- Listener: `ProcessMaintenanceCompletion::handle()`
- Category: `maintenance_income`
- Type: `income`
- Wallet: ✅ DEDUCTED (only this transaction)

---

## ❌ NOT COMPANY INCOME

### Wallet Funding
**Purpose:** Driver depositing money into their wallet
**Flow:**
```
Driver deposits ₦10,000 → Uploads receipt → Manager approves → 
Driver wallet credited ₦10,000 (NO company account transaction)
```
**Why NOT Income:**
- This is driver's money sitting in their account
- Company earns when driver SPENDS wallet balance (maintenance)
- Wallet balance is a LIABILITY to the company

---

## 💸 COMPANY EXPENSES

Expenses are manually recorded via Company Account interface:

### Categories:
1. **Fuel** - External fuel purchases (if applicable)
2. **Maintenance Expense** - Company vehicle maintenance (not driver-paid)
3. **Salary** - Employee salaries
4. **Utilities** - Electricity, water, internet
5. **Rent** - Office/depot rent
6. **Insurance** - Vehicle/business insurance
7. **Loan Payment** - Business loan repayments
8. **Miscellaneous** - Other miscellaneous expenses

**Note:** These are TRUE business expenses, not driver payments

---

## 📊 SUMMARY

| Transaction Type | Payment Method | Wallet Deduction | Company Income | Receipt Required |
|-----------------|----------------|------------------|----------------|------------------|
| Daily Remittance | Cash | ❌ NO | ✅ YES | ❌ NO |
| EV Charging | Direct Payment | ❌ NO | ✅ YES | ✅ YES |
| Maintenance Parts | Wallet | ✅ YES (ONLY ONE) | ✅ YES | ❌ NO |
| Wallet Funding | Deposit | ➕ Adds to wallet | ❌ NO | ✅ YES |

---

## 🔄 WALLET USAGE

**Wallet is used for:**
- ✅ Maintenance parts payment (ONLY transaction that deducts from wallet)

**Wallet is NOT used for:**
- ❌ Daily remittance (cash payment)
- ❌ EV charging (direct payment with receipt)

**Wallet funding:**
- Just increases driver's wallet balance
- NOT recorded as company income
- Company earns when wallet is spent on services

---

## 📋 COMPANY ACCOUNT CATEGORIES

### **Income Categories:**
1. **Daily Remittance** (`daily_remittance`) - Cash remittances from drivers
2. **EV Charging Service** (`charging`) - Charging service payments
3. **Maintenance Payment** (`maintenance_income`) - Driver payments for vehicle maintenance

### **Expense Categories:**
1. **Fuel** (`fuel`) - External fuel purchases
2. **Maintenance Expense** (`maintenance_expense`) - Company-paid maintenance
3. **Salary** (`salary`) - Employee salaries
4. **Utilities** (`utilities`) - Electricity, water, internet
5. **Rent** (`rent`) - Office/depot rent
6. **Insurance** (`insurance`) - Vehicle/business insurance
7. **Loan Payment** (`loan_payment`) - Business loan repayments
8. **Miscellaneous** (`miscellaneous`) - Other expenses

**Note:** All income has specific descriptive names - no generic "other_income" category

---

## ✅ IMPLEMENTATION STATUS

- ✅ Daily Remittance → Company Income (no wallet deduction)
- ✅ EV Charging → Company Income (no wallet deduction, receipt required)
- ✅ Maintenance → Company Income (wallet deducted)
- ✅ Wallet Funding → NO company transaction

All systems correctly integrated with Company Account!
