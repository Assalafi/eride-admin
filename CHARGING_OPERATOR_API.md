# Charging Station Operator API Documentation

## Base URL
```
https://yourdomain.com/api/charging-operator
```

## Authentication
All endpoints require authentication using Laravel Sanctum token. Include the token in the request header:
```
Authorization: Bearer {your_token}
```

### Login
**Endpoint:** `POST /api/login`
```json
{
  "email": "operator@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Operator",
      "email": "operator@example.com",
      "role": "Charging Station Operator"
    },
    "token": "1|abc123xyz..."
  }
}
```

---

## 1. Get All Approved Charging Requests

Retrieve all charging requests that are approved or in progress for the operator's branch.

**Endpoint:** `GET /api/charging-operator/requests`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response:**
```json
{
  "success": true,
  "message": "Charging requests retrieved successfully",
  "data": [
    {
      "id": 1,
      "status": "approved",
      "driver": {
        "id": 5,
        "name": "John Driver",
        "phone": "08012345678"
      },
      "vehicle": {
        "id": 3,
        "plate_number": "ABC-123-XY",
        "make": "Tesla",
        "model": "Model 3"
      },
      "location": "Main Charging Station",
      "charging_cost": 5000.00,
      "battery_level_before": 20.5,
      "battery_level_after": null,
      "energy_consumed": null,
      "charging_start": null,
      "charging_end": null,
      "duration_minutes": null,
      "approved_by": "Branch Manager",
      "approved_at": "2025-10-19T00:30:00.000000Z",
      "created_at": "2025-10-18T22:15:00.000000Z",
      "payment_receipt": "https://yourdomain.com/storage/receipts/abc123.jpg"
    }
  ]
}
```

---

## 2. Get Charging Request Details

Get detailed information about a specific charging request.

**Endpoint:** `GET /api/charging-operator/requests/{id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response:**
```json
{
  "success": true,
  "message": "Charging request details retrieved successfully",
  "data": {
    "id": 1,
    "status": "approved",
    "driver": {
      "id": 5,
      "name": "John Driver",
      "phone": "08012345678",
      "email": "driver@example.com"
    },
    "vehicle": {
      "id": 3,
      "plate_number": "ABC-123-XY",
      "make": "Tesla",
      "model": "Model 3",
      "year": 2023
    },
    "location": "Main Charging Station",
    "charging_cost": 5000.00,
    "battery_level_before": 20.5,
    "battery_level_after": null,
    "energy_consumed": null,
    "charging_start": null,
    "charging_end": null,
    "duration_minutes": null,
    "approved_by": "Branch Manager",
    "approved_at": "2025-10-19T00:30:00.000000Z",
    "payment_receipt": "https://yourdomain.com/storage/receipts/abc123.jpg",
    "notes": null,
    "created_at": "2025-10-18T22:15:00.000000Z",
    "updated_at": "2025-10-19T00:30:00.000000Z"
  }
}
```

---

## 3. Start Charging Session

Start a charging session for an approved request.

**Endpoint:** `POST /api/charging-operator/requests/{id}/start`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:** None required

**Response Success:**
```json
{
  "success": true,
  "message": "Charging session started successfully!",
  "data": {
    "id": 1,
    "status": "in_progress",
    "charging_start": "2025-10-19T01:00:00.000000Z",
    "driver": {
      "name": "John Driver"
    },
    "vehicle": {
      "plate_number": "ABC-123-XY"
    }
  }
}
```

**Error Responses:**

- **404 Not Found:**
```json
{
  "success": false,
  "message": "Charging request not found."
}
```

- **403 Forbidden:**
```json
{
  "success": false,
  "message": "You can only start charging for requests from your branch."
}
```

- **400 Bad Request:**
```json
{
  "success": false,
  "message": "Can only start charging for approved requests. Current status: in_progress"
}
```

---

## 4. Complete Charging Session

Complete a charging session and record final battery data.

**Endpoint:** `POST /api/charging-operator/requests/{id}/complete`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
  "battery_level_after": 95.5,
  "energy_consumed": 45.2
}
```

**Parameters:**
- `battery_level_after` (required): Final battery percentage (0-100)
- `energy_consumed` (optional): Energy consumed in kWh

**Response Success:**
```json
{
  "success": true,
  "message": "Charging session completed successfully!",
  "data": {
    "id": 1,
    "status": "completed",
    "charging_start": "2025-10-19T01:00:00.000000Z",
    "charging_end": "2025-10-19T02:30:00.000000Z",
    "duration_minutes": 90,
    "battery_level_before": 20.5,
    "battery_level_after": 95.5,
    "energy_consumed": 45.2,
    "charging_cost": 5000.00,
    "driver": {
      "name": "John Driver"
    },
    "vehicle": {
      "plate_number": "ABC-123-XY"
    }
  }
}
```

**Error Responses:**

- **422 Validation Error:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "battery_level_after": [
      "The battery level after field is required."
    ]
  }
}
```

- **400 Bad Request:**
```json
{
  "success": false,
  "message": "Can only complete charging requests that are in progress. Current status: approved"
}
```

---

## Status Flow

```
pending → approved → in_progress → completed
```

1. **pending** - Driver submits request (Operator cannot see)
2. **approved** - Manager approves (Operator can now see and start)
3. **in_progress** - Operator starts charging
4. **completed** - Operator completes with battery data

---

## Error Codes

| Code | Description |
|------|-------------|
| 200  | Success |
| 400  | Bad Request - Invalid status transition |
| 401  | Unauthorized - Invalid or missing token |
| 403  | Forbidden - No permission or wrong branch |
| 404  | Not Found - Resource doesn't exist |
| 422  | Validation Error - Invalid input data |

---

## Notes

- Operators can only see and manage charging requests from their assigned branch
- Payment transactions are recorded when managers approve the request
- Operators only need to start and complete the charging session
- All timestamps are in ISO 8601 format (UTC)
- Payment receipt URLs are fully qualified URLs

---

## Example Workflow

```bash
# 1. Login
curl -X POST https://yourdomain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "operator@example.com", "password": "password"}'

# 2. Get approved requests
curl -X GET https://yourdomain.com/api/charging-operator/requests \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# 3. Start charging
curl -X POST https://yourdomain.com/api/charging-operator/requests/1/start \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# 4. Complete charging
curl -X POST https://yourdomain.com/api/charging-operator/requests/1/complete \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"battery_level_after": 95.5, "energy_consumed": 45.2}'
```
