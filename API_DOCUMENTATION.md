# eRide Driver App - API Documentation

## Base URL
```
https://your-domain.com/api
```

## Authentication
All API requests (except login) require authentication using Laravel Sanctum.

### Headers Required:
```
Accept: application/json
Authorization: Bearer {token}
Content-Type: application/json (or multipart/form-data for file uploads)
```

---

## 📱 AUTHENTICATION

### 1. Login
**POST** `/login`

**Request Body:**
```json
{
  "email": "driver@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "driver@example.com",
    "role": "Driver"
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### 2. Logout
**POST** `/logout`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 3. Get Current User
**GET** `/user`

**Success Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "driver@example.com"
  }
}
```

---

## 🏠 DASHBOARD & PROFILE

### 4. Get Dashboard Data
**GET** `/driver/dashboard`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "driver": {
      "id": 1,
      "name": "John Doe",
      "email": "driver@example.com",
      "phone": "+2348012345678",
      "branch": "Lagos Branch",
      "profile_photo": "https://domain.com/storage/drivers/photos/photo.jpg"
    },
    "wallet": {
      "balance": 15000.00,
      "formatted_balance": "₦15,000.00"
    },
    "vehicle": {
      "assignment_id": 5,
      "plate_number": "ABC-123-XY",
      "make": "Tesla",
      "model": "Model 3",
      "year": 2023,
      "assigned_at": "2025-01-15 10:30:00"
    },
    "daily_balance": {
      "required": 5000.00,
      "paid": 3000.00,
      "balance": 2000.00,
      "status": "partial"
    },
    "pending_requests": {
      "maintenance": 1,
      "charging": 0,
      "wallet_funding": 1
    }
  }
}
```

---

### 5. Get Driver Profile
**GET** `/driver/profile`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "email": "driver@example.com",
    "phone_number": "+2348012345678",
    "date_of_birth": "1990-05-15",
    "address": "123 Main Street, Lagos",
    "license_number": "LOS12345678",
    "license_expiry": "2026-12-31",
    "profile_photo": "https://domain.com/storage/drivers/photos/photo.jpg",
    "branch": "Lagos Branch",
    "wallet_balance": 15000.00,
    "created_at": "2024-01-01 00:00:00"
  }
}
```

---

### 6. Update Profile
**POST** `/driver/profile/update`

**Content-Type:** `multipart/form-data`

**Request Body:**
```
phone_number: +2348012345678 (optional)
address: 123 Main Street (optional)
profile_photo: [file] (optional, max 2MB, jpg/jpeg/png)
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "phone_number": "+2348012345678",
    "address": "123 Main Street, Lagos",
    "profile_photo": "https://domain.com/storage/drivers/photos/new-photo.jpg"
  }
}
```

---

### 7. Change Password
**POST** `/driver/profile/change-password`

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword123",
  "new_password_confirmation": "newpassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Current password is incorrect"
}
```

---

## 💰 WALLET & TRANSACTIONS

### 8. Get Wallet Details
**GET** `/driver/wallet`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "balance": 15000.00,
    "formatted_balance": "₦15,000.00",
    "recent_transactions": [
      {
        "id": 1,
        "type": "daily_remittance",
        "amount": 5000.00,
        "description": "Daily remittance payment",
        "status": "successful",
        "created_at": "2025-01-09 10:30:00"
      }
    ]
  }
}
```

---

### 9. Request Wallet Funding
**POST** `/driver/wallet/fund-request`

**Content-Type:** `multipart/form-data`

**Request Body:**
```
amount: 10000.00 (required, numeric, min: 1)
payment_method: bank_transfer (required, enum: bank_transfer, cash, mobile_money)
payment_proof: [file] (required, max 5MB, jpg/jpeg/png/pdf)
notes: Additional notes (optional, max 500 chars)
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Wallet funding request submitted successfully. Awaiting approval.",
  "data": {
    "id": 15,
    "amount": 10000.00,
    "payment_method": "bank_transfer",
    "status": "pending",
    "created_at": "2025-01-09 14:30:00"
  }
}
```

---

### 10. Get Wallet Funding Requests
**GET** `/driver/wallet/funding-requests?page=1`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "amount": 10000.00,
      "payment_method": "bank_transfer",
      "payment_proof": "https://domain.com/storage/wallet-funding/proofs/proof.jpg",
      "status": "pending",
      "notes": "Bank transfer from GTBank",
      "admin_notes": null,
      "approved_by": null,
      "approved_at": null,
      "created_at": "2025-01-09 14:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

---

### 11. Get Transaction History
**GET** `/driver/transactions?page=1`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "daily_remittance",
      "amount": 5000.00,
      "description": "Daily remittance payment",
      "reference": "DRP-ABC123",
      "status": "successful",
      "created_at": "2025-01-09 10:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 195
  }
}
```

---

## 💵 DAILY REMITTANCE

### 12. Submit Daily Remittance Payment
**POST** `/driver/remittance/submit`

**Request Body:**
```json
{
  "amount": 5000.00,
  "notes": "Daily remittance for January 9, 2025"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Daily remittance submitted successfully. Awaiting approval.",
  "data": {
    "id": 45,
    "amount": 5000.00,
    "reference": "DRP-ABC123XYZ",
    "status": "pending",
    "created_at": "2025-01-09 18:00:00"
  }
}
```

---

### 13. Get Ledger History
**GET** `/driver/remittance/ledger-history?page=1`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "date": "2025-01-09",
      "required_payment": 5000.00,
      "amount_paid": 5000.00,
      "balance": 0.00,
      "status": "paid"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 30,
    "total": 75
  }
}
```

---

## 🔧 MAINTENANCE REQUESTS

### 14. Create Maintenance Request
**POST** `/driver/maintenance/create`

**Content-Type:** `multipart/form-data`

**Request Body:**
```
mechanic_id: 3 (required, exists in mechanics table)
issue_description: Engine making strange noise (required, max 1000 chars)
issue_photos[]: [file1] (optional, max 5 photos, each max 5MB, jpg/jpeg/png)
issue_photos[]: [file2]
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Maintenance request created successfully. Awaiting manager approval.",
  "data": {
    "id": 10,
    "issue_description": "Engine making strange noise",
    "status": "pending_manager_approval",
    "created_at": "2025-01-09 12:00:00"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "You do not have an assigned vehicle"
}
```

---

### 15. Get Maintenance Requests
**GET** `/driver/maintenance/requests?page=1`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "vehicle": {
        "plate_number": "ABC-123-XY",
        "make": "Tesla",
        "model": "Model 3"
      },
      "mechanic": "John Mechanic",
      "issue_description": "Engine making strange noise",
      "issue_photos": [
        "https://domain.com/storage/maintenance/issues/photo1.jpg"
      ],
      "parts": [
        {
          "name": "Brake Pad",
          "quantity": 2,
          "unit_cost": 5000.00,
          "total_cost": 10000.00
        }
      ],
      "total_cost": 10000.00,
      "status": "completed",
      "manager_notes": "Approved",
      "created_at": "2025-01-09 12:00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 35
  }
}
```

---

### 16. Get Single Maintenance Request
**GET** `/driver/maintenance/requests/{id}`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 10,
    "vehicle": {
      "plate_number": "ABC-123-XY",
      "make": "Tesla",
      "model": "Model 3",
      "year": 2023
    },
    "mechanic": {
      "id": 3,
      "name": "John Mechanic",
      "phone": "+2348012345678"
    },
    "issue_description": "Engine making strange noise",
    "issue_photos": [
      "https://domain.com/storage/maintenance/issues/photo1.jpg"
    ],
    "parts": [
      {
        "id": 5,
        "name": "Brake Pad",
        "quantity": 2,
        "unit_cost": 5000.00,
        "total_cost": 10000.00
      }
    ],
    "total_cost": 10000.00,
    "status": "completed",
    "manager_notes": "Approved, parts confirmed",
    "approved_by": "Manager Name",
    "approved_at": "2025-01-09 13:00:00",
    "created_at": "2025-01-09 12:00:00"
  }
}
```

---

## ⚡ CHARGING REQUESTS

### 17. Create Charging Request
**POST** `/driver/charging/create`

**Content-Type:** `multipart/form-data`

**Request Body:**
```
charging_cost: 5000.00 (required, numeric, min: 0)
payment_receipt: [file] (required, max 5MB, jpg/jpeg/png/pdf)
notes: Charged at XYZ Station (optional, max 500 chars)
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Charging request created successfully",
  "data": {
    "id": 25,
    "charging_cost": 5000.00,
    "status": "pending",
    "created_at": "2025-01-09 15:00:00"
  }
}
```

---

### 18. Get Charging Requests
**GET** `/driver/charging/requests?page=1`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 25,
      "vehicle": {
        "plate_number": "ABC-123-XY",
        "make": "Tesla",
        "model": "Model 3"
      },
      "charging_cost": 5000.00,
      "payment_receipt": "https://domain.com/storage/charging/receipts/receipt.jpg",
      "notes": "Charged at XYZ Station",
      "status": "completed",
      "started_at": "2025-01-09 15:10:00",
      "completed_at": "2025-01-09 17:30:00",
      "created_at": "2025-01-09 15:00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 20,
    "total": 72
  }
}
```

---

### 19. Get Single Charging Request
**GET** `/driver/charging/requests/{id}`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 25,
    "vehicle": {
      "plate_number": "ABC-123-XY",
      "make": "Tesla",
      "model": "Model 3",
      "year": 2023
    },
    "charging_cost": 5000.00,
    "payment_receipt": "https://domain.com/storage/charging/receipts/receipt.jpg",
    "notes": "Charged at XYZ Station",
    "status": "completed",
    "approved_by": "Admin Name",
    "started_at": "2025-01-09 15:10:00",
    "completed_at": "2025-01-09 17:30:00",
    "created_at": "2025-01-09 15:00:00"
  }
}
```

---

## 🚗 VEHICLE ASSIGNMENT

### 20. Get Current Vehicle
**GET** `/driver/vehicle/current`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "assignment_id": 5,
    "vehicle": {
      "id": 10,
      "plate_number": "ABC-123-XY",
      "make": "Tesla",
      "model": "Model 3",
      "year": 2023,
      "vin": "5YJ3E1EA8KF123456",
      "color": "White"
    },
    "assigned_at": "2025-01-05 10:00:00",
    "assigned_by": "Manager Name"
  }
}
```

**No Vehicle Response (200):**
```json
{
  "success": true,
  "data": null,
  "message": "No vehicle currently assigned"
}
```

---

### 21. Get Vehicle Assignment History
**GET** `/driver/vehicle/history?page=1`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "vehicle": {
        "plate_number": "ABC-123-XY",
        "make": "Tesla",
        "model": "Model 3"
      },
      "assigned_at": "2025-01-05 10:00:00",
      "returned_at": null,
      "is_active": true
    },
    {
      "id": 4,
      "vehicle": {
        "plate_number": "XYZ-789-AB",
        "make": "Nissan",
        "model": "Leaf"
      },
      "assigned_at": "2024-12-01 09:00:00",
      "returned_at": "2025-01-04 18:00:00",
      "is_active": false
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 25
  }
}
```

---

## 🛠️ UTILITY ENDPOINTS

### 22. Get Available Mechanics
**GET** `/driver/mechanics`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Mechanic",
      "phone_number": "+2348012345678",
      "specialization": "EV Specialist"
    },
    {
      "id": 2,
      "name": "Jane Technician",
      "phone_number": "+2348087654321",
      "specialization": "General Maintenance"
    }
  ]
}
```

---

## 📋 STATUS ENUMS

### Transaction Status
- `pending` - Awaiting approval
- `successful` - Approved and processed
- `rejected` - Denied

### Maintenance Request Status
- `pending_manager_approval` - Waiting for manager approval
- `manager_denied` - Manager rejected
- `pending_store_approval` - Waiting for store keeper to confirm parts
- `completed` - Completed and paid

### Charging Request Status
- `pending` - Just created
- `in_progress` - Charging started
- `completed` - Charging completed
- `cancelled` - Request cancelled

### Daily Ledger Status
- `unpaid` - No payment yet
- `partial` - Partially paid
- `paid` - Fully paid

### Payment Methods
- `bank_transfer`
- `cash`
- `mobile_money`

---

## 🔒 Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Access denied. You must be a driver."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Driver profile not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount field is required."],
    "payment_receipt": ["The payment receipt must be a file."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "An error occurred. Please try again later."
}
```

---

## 📌 NOTES

1. **File Uploads:** Use `multipart/form-data` content type for endpoints with file uploads
2. **Pagination:** All list endpoints support pagination with `?page=` parameter
3. **Date Format:** All dates are in `Y-m-d H:i:s` format (e.g., 2025-01-09 14:30:00)
4. **Amounts:** All monetary amounts are in Nigerian Naira (₦) as float values
5. **Images:** Profile photos and receipts should be JPG, JPEG, or PNG format
6. **Token:** Store the authentication token securely in the Flutter app
7. **Offline Support:** Consider implementing offline caching for critical data

---

## 🚀 TESTING

**Base URL (Development):**
```
http://localhost:8000/api
```

**Base URL (Production):**
```
https://eride.ng/api
```

**Postman Collection:** Available upon request

---

## 📞 SUPPORT

For API issues or questions:
- **Email:** dev@eride.ng
- **Phone:** +234 xxx xxx xxxx
- **Documentation:** https://eride.ng/api/docs

---

**Version:** 1.0  
**Last Updated:** January 9, 2025  
**Maintained by:** eRide Development Team
