# 🚫 Rejection Comment Feature

## Enhanced Payment Rejection Process

Added a comprehensive comment system for rejecting daily remittance payments to improve communication and audit trail.

---

## 🎯 Feature Overview

### Rejection Process Enhancement

-   **Before**: Simple confirmation dialog for rejection
-   **After**: Modal with required comment field and transaction details
-   **Purpose**: Provide clear reasons for payment rejection to drivers
-   **Audit Trail**: Store rejection reasons in transaction description

### User Experience Improvements

-   **Required Comments**: Cannot reject without providing reason
-   **Character Limit**: 500 characters with live counter
-   **Transaction Context**: Shows driver and amount details
-   **Visual Feedback**: Warning alerts and clear UI elements

---

## 🛠️ Implementation Details

### Backend Changes

#### Controller Method Update

```php
public function reject(Request $request, Transaction $transaction)
{
    $user = auth()->user();

    // Permission and status checks...

    $request->validate([
        'rejection_comment' => 'required|string|max:500'
    ]);

    $transaction->update([
        'status' => 'rejected',
        'approved_by' => auth()->id(),
        'description' => $request->rejection_comment, // Store comment
    ]);

    return redirect()->route('admin.payments.index')
        ->with('success', 'Payment rejected successfully!');
}
```

#### Validation Rules

-   **Required Field**: Rejection comment must be provided
-   **Character Limit**: Maximum 500 characters
-   **String Type**: Ensures proper text input
-   **Storage**: Comment saved in transaction `description` field

### Frontend Changes

#### Rejection Modal

```html
<div class="modal fade" id="rejectionModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-close-circle-line text-danger me-2"></i>Reject
                    Payment
                </h5>
            </div>
            <form id="rejectionForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <!-- Warning Alert -->
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-2"></i>
                        <strong>Warning:</strong> You are about to reject this
                        payment. Please provide a reason for the rejection.
                    </div>

                    <!-- Comment Field -->
                    <div class="mb-3">
                        <label for="rejection_comment" class="form-label">
                            <strong
                                >Rejection Reason
                                <span class="text-danger">*</span></strong
                            >
                        </label>
                        <textarea
                            class="form-control"
                            id="rejection_comment"
                            name="rejection_comment"
                            rows="4"
                            maxlength="500"
                            required
                            placeholder="Please explain why this payment is being rejected..."
                        >
                        </textarea>
                        <div class="form-text">
                            Maximum 500 characters. This reason will be visible
                            to the driver.
                        </div>
                    </div>

                    <!-- Transaction Details -->
                    <div class="mb-3">
                        <label class="form-label">Transaction Details:</label>
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Driver:</small
                                        ><br />
                                        <strong id="rejection_driver_name"
                                            >-</strong
                                        >
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Amount:</small
                                        ><br />
                                        <strong id="rejection_amount">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        <i class="ri-close-line me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-close-circle-line me-1"></i>Reject Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

#### Updated Reject Button

```html
<!-- Before -->
<form action="{{ route('admin.payments.reject', $transaction) }}" method="POST">
    @csrf
    <button
        type="submit"
        class="btn btn-sm btn-danger"
        onclick="return confirm('Reject this payment?')"
    >
        <i class="ri-close-line"></i>
    </button>
</form>

<!-- After -->
<button
    type="button"
    class="btn btn-sm btn-danger"
    onclick="openRejectionModal('{{ route('admin.payments.reject', $transaction) }}', 
                                  '{{ $transaction->driver->full_name }}', 
                                  '₦{{ number_format($transaction->amount, 2) }}')"
>
    <i class="ri-close-line"></i>
</button>
```

#### Rejection Reason Display

```html
<td>
    @if ($transaction->status == 'successful')
    <span class="badge bg-success">Success</span>
    @elseif($transaction->status == 'pending')
    <span class="badge bg-warning">Pending</span>
    @else
    <div>
        <span class="badge bg-danger">Rejected</span>
        @if($transaction->description)
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary ms-1"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="{{ $transaction->description }}"
        >
            <i class="ri-message-3-line"></i>
        </button>
        @endif
    </div>
    @endif
</td>
```

### JavaScript Implementation

#### Modal Handler Function

```javascript
function openRejectionModal(rejectUrl, driverName, amount) {
    // Set form action
    document.getElementById("rejectionForm").action = rejectUrl;

    // Set transaction details
    document.getElementById("rejection_driver_name").textContent = driverName;
    document.getElementById("rejection_amount").textContent = amount;

    // Clear previous comment
    document.getElementById("rejection_comment").value = "";

    // Show modal
    const modal = new bootstrap.Modal(
        document.getElementById("rejectionModal")
    );
    modal.show();
}
```

#### Character Counter

```javascript
document
    .getElementById("rejection_comment")
    .addEventListener("input", function () {
        const maxLength = 500;
        const currentLength = this.value.length;

        // Update character count
        let charCount = this.parentNode.querySelector(".char-count");
        if (!charCount) {
            charCount = document.createElement("div");
            charCount.className = "char-count text-muted small mt-1";
            this.parentNode.appendChild(charCount);
        }

        charCount.textContent = `${currentLength}/${maxLength} characters`;

        // Warning when approaching limit
        if (maxLength - currentLength < 50) {
            charCount.classList.add("text-warning");
        } else {
            charCount.classList.remove("text-warning");
        }
    });
```

---

## 📊 User Experience Flow

### 1. **Initiating Rejection**

```
Admin clicks "Reject" button on pending payment
→ Modal opens with transaction details
→ Comment field is empty and focused
→ Warning message displayed
```

### 2. **Providing Reason**

```
Admin types rejection reason
→ Live character counter updates
→ Warning appears when approaching limit
→ Transaction details remain visible
```

### 3. **Submitting Rejection**

```
Admin clicks "Reject Payment" button
→ Form validates required comment
→ Payment is rejected with reason stored
→ Success message displayed
```

### 4. **Viewing Rejection History**

```
Rejected payments show in table
→ "Rejected" badge displayed
→ Message icon shows if reason exists
→ Hover tooltip shows full rejection reason
```

---

## 🎨 Visual Design Features

### Modal Design

-   **Warning Alert**: Yellow alert with icon
-   **Required Field**: Red asterisk on label
-   **Character Counter**: Live feedback below textarea
-   **Transaction Card**: Light background with details
-   **Action Buttons**: Cancel (secondary) + Reject (danger)

### Status Display

-   **Rejected Badge**: Red background
-   **Message Icon**: Shows when reason exists
-   **Tooltip**: Full reason on hover
-   **Responsive**: Works on all screen sizes

### Interactive Elements

-   **Live Validation**: Character count updates
-   **Visual Feedback**: Warning colors
-   **Clear Context**: Driver and amount shown
-   **Professional UI**: Consistent with admin theme

---

## 🧪 Testing Scenarios

### Test Case 1: Successful Rejection with Comment

```
Given: Pending daily remittance payment
When: Admin clicks reject and provides valid comment
Then: Payment is rejected and comment is stored
```

### Test Case 2: Rejection Without Comment

```
Given: Pending payment with rejection modal open
When: Admin tries to submit without comment
Then: Validation error shown, rejection blocked
```

### Test Case 3: Character Limit Enforcement

```
Given: Admin types more than 500 characters
When: Character limit reached
Then: No more characters can be typed
```

### Test Case 4: Rejection Reason Display

```
Given: Previously rejected payment with comment
When: Viewing payments table
Then: Message icon shows comment in tooltip
```

### Test Case 5: Modal Data Accuracy

```
Given: Payment with specific driver and amount
When: Opening rejection modal
Then: Correct driver name and amount displayed
```

---

## 📈 Business Benefits

### Improved Communication

-   **Clear Feedback**: Drivers receive specific rejection reasons
-   **Professional Standards**: Formal rejection process
-   **Documentation**: Written record of all rejections
-   **Transparency**: Open communication about payment issues

### Enhanced Audit Trail

-   **Detailed Records**: Complete rejection history
-   **Accountability**: Admin must provide justification
-   **Compliance**: Better documentation for audits
-   **Dispute Resolution**: Clear reasons for decisions

### Operational Efficiency

-   **Reduced Confusion**: Clear reasons prevent follow-up questions
-   **Faster Resolution**: Drivers can address specific issues
-   **Quality Control**: Ensures proper review process
-   **Training Tool**: Examples for driver education

---

## ✅ Summary

**Key Features**:

1. ✅ Required comment field for payment rejection
2. ✅ 500 character limit with live counter
3. ✅ Transaction details displayed in modal
4. ✅ Rejection reasons stored and displayed
5. ✅ Tooltip display for rejection history
6. ✅ Professional modal design with warnings

**Files Modified**:

-   ✅ `PaymentController.php` - Added comment validation and storage
-   ✅ `payments/index.blade.php` - Added modal and updated UI

**Security & Validation**:

-   ✅ Required comment prevents empty rejections
-   ✅ Character limit prevents excessive text
-   ✅ Proper validation ensures data integrity
-   ✅ Existing permissions maintained

**Result**: Comprehensive rejection system with clear communication and audit trail!

---

## 🚀 Result

**Payment rejection now includes mandatory comments for better communication and documentation!**

Administrators can now:

-   Provide specific reasons for payment rejection
-   See transaction details in rejection modal
-   Track character count with live feedback
-   View rejection history with tooltips
-   Maintain professional audit trail

**Enhanced payment management with clear communication standards!** 🚫💬📋
