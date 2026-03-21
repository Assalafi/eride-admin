# eRide Transport Operations Management System

A comprehensive Laravel-based Transport Operations Management System for managing daily financial remittances, vehicle assignments, and maintenance operations with automated workflows.

## System Overview

The eRide Admin Web system manages:
- **Driver Management** - CRUD operations for drivers with user accounts and wallets
- **Vehicle Management** - Fleet management with assignment tracking
- **Daily Remittances** - Automated ledger creation and payment approval workflows
- **Maintenance Requests** - Parts request workflow with inventory management
- **Inventory Management** - Parts catalog with branch-specific stock tracking
- **Role-Based Access Control** - Granular permissions for different user roles

## Technology Stack

- **Framework**: Laravel 11.x
- **Authentication**: Laravel Sanctum (for API)
- **Permissions**: Spatie Laravel Permission
- **Database**: MySQL/PostgreSQL
- **Frontend**: Bootstrap 5, Font Awesome

## Installation & Setup

### 1. Database Configuration

Edit `.env` file and configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eride_db
DB_USERNAME=root
DB_PASSWORD=your_password

DAILY_REQUIRED_PAYMENT=5000.00
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Seed Database

```bash
php artisan db:seed
```

This creates:
- 3 branches (Maiduguri, Abuja, Lagos)
- 5 user roles with permissions
- 4 test users (Super Admin, Branch Manager, Mechanic, Storekeeper)

### 4. Default Login Credentials

```
Super Admin:
Email: admin@eride.com
Password: password

Branch Manager (Maiduguri):
Email: manager@maiduguri.eride.com
Password: password

Mechanic:
Email: mechanic@maiduguri.eride.com
Password: password

Storekeeper:
Email: storekeeper@maiduguri.eride.com
Password: password
```

### 5. Set Up Scheduled Tasks

Add to your crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or run manually for testing:

```bash
php artisan ledgers:create-daily
```

## System Architecture

### User Roles & Permissions

1. **Super Admin**
   - Full system access across all branches
   - Can manage all resources

2. **Branch Manager**
   - Manages single branch operations
   - CRUD for Drivers, Vehicles, Users
   - Assigns vehicles to drivers
   - Approves/Rejects payments
   - Financial approval for maintenance requests
   - Manages branch inventory

3. **Mechanic**
   - Views available parts
   - Creates maintenance requests
   - Cannot approve or dispense parts

4. **Storekeeper**
   - Views financially-approved maintenance requests
   - Dispenses parts and confirms collection
   - Can manage inventory (if granted permission)

5. **Driver**
   - Submits daily remittance payments via mobile app
   - Views payment history and wallet balance

### Database Schema

**Core Tables:**
- `branches` - Branch locations
- `users` - System users with role assignments
- `drivers` - Driver records linked to users
- `vehicles` - Fleet vehicles
- `wallets` - Driver wallet balances
- `vehicle_assignments` - Assignment tracking
- `daily_ledgers` - Daily payment requirements
- `transactions` - All financial transactions
- `parts` - Parts catalog
- `part_stock` - Branch-specific inventory
- `maintenance_requests` - Maintenance workflow
- `maintenance_request_parts` - Pivot table for parts in requests

### Key Workflows

#### Workflow 1: Daily Operations

1. **Morning - Vehicle Assignment**
   - Manager assigns vehicle to driver
   - System records in `vehicle_assignments`

2. **Morning - Automated Ledger Creation** (6:00 AM)
   - Scheduled task runs: `php artisan ledgers:create-daily`
   - Creates `daily_ledgers` entry for each active assignment
   - Status: 'due', Amount: configured daily payment

3. **Evening - Payment Submission**
   - Driver submits payment via mobile app
   - Creates `transactions` record with status 'pending'

4. **Evening - Payment Approval**
   - Manager approves transaction
   - Event `PaymentApproved` is fired
   - Listener `UpdateDailyLedger` updates ledger automatically

#### Workflow 2: Maintenance Parts Request

1. **Request Creation (Mechanic)**
   - Selects driver/vehicle and required parts
   - Status: 'pending_manager_approval'

2. **Financial Check (Manager)**
   - Reviews total cost vs driver wallet balance
   - If sufficient: Status → 'pending_store_approval'
   - If insufficient: Request denied

3. **Parts Dispensing (Storekeeper)**
   - Views approved requests
   - Physically dispenses parts
   - Clicks "Confirm Collection"

4. **Automated Completion**
   - Event `MaintenanceCompleted` fires
   - Listener `ProcessMaintenanceCompletion` executes DB transaction:
     - Deducts from driver wallet
     - Decrements part stock
     - Creates maintenance_debit transaction
     - Updates status to 'completed'

## API Endpoints (Mobile App)

### Authentication
```
POST /api/login
POST /api/logout
GET  /api/user
```

### Driver Operations
```
GET  /api/driver/dashboard
POST /api/driver/submit-payment
GET  /api/driver/payment-history
GET  /api/driver/ledger-history
```

All protected routes require `Authorization: Bearer {token}` header.

## Events & Listeners

### PaymentApproved Event
- **Triggered**: When manager approves a payment transaction
- **Listener**: `UpdateDailyLedger`
- **Action**: Updates driver's daily ledger with payment amount and status

### MaintenanceCompleted Event
- **Triggered**: When storekeeper confirms parts collection
- **Listener**: `ProcessMaintenanceCompletion`
- **Action**: Atomic transaction that deducts wallet, updates inventory, creates transaction record

## Configuration

### Daily Required Payment

Set in `config/eride.php` or `.env`:

```php
'daily_required_payment' => env('DAILY_REQUIRED_PAYMENT', 5000.00),
```

## Development Notes

### Running Queue Workers

For production, ensure queue workers are running:

```bash
php artisan queue:work
```

### Testing Scheduled Commands

```bash
# Test daily ledger creation
php artisan ledgers:create-daily

# View scheduled tasks
php artisan schedule:list
```

### Creating Additional Admin Users

```php
$user = User::create([
    'name' => 'New Manager',
    'email' => 'manager@branch.com',
    'password' => Hash::make('password'),
    'branch_id' => 1, // or null for Super Admin
]);

$user->assignRole('Branch Manager');
```

## Security Features

- CSRF protection on all forms
- Role-based access control with granular permissions
- Database transactions for critical operations
- Password hashing with bcrypt
- API authentication with Laravel Sanctum
- SQL injection protection via Eloquent ORM

## License

Proprietary - eRide Transport Management System
