<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ChargingRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\MaintenanceRequestController;
use App\Http\Controllers\Admin\PartController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleAssignmentController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\HirePurchaseController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // System Activities & Requests
    Route::get('admin/activities', [ActivityController::class, 'index'])->name('admin.activities.index');
    
    // Driver Management
    Route::middleware(['permission:view drivers'])->group(function () {
        Route::resource('admin/drivers', DriverController::class)->names([
            'index' => 'admin.drivers.index',
            'create' => 'admin.drivers.create',
            'store' => 'admin.drivers.store',
            'show' => 'admin.drivers.show',
            'edit' => 'admin.drivers.edit',
            'update' => 'admin.drivers.update',
            'destroy' => 'admin.drivers.destroy',
        ]);
        
        // Wallet funding
        Route::post('admin/drivers/{driver}/fund-wallet', [DriverController::class, 'fundWallet'])->name('admin.drivers.fund-wallet');
    });
    
    // Vehicle Management
    Route::middleware(['permission:view vehicles'])->group(function () {
        Route::resource('admin/vehicles', VehicleController::class)->names([
            'index' => 'admin.vehicles.index',
            'create' => 'admin.vehicles.create',
            'store' => 'admin.vehicles.store',
            'show' => 'admin.vehicles.show',
            'edit' => 'admin.vehicles.edit',
            'update' => 'admin.vehicles.update',
            'destroy' => 'admin.vehicles.destroy',
        ]);
    });
    
    // Vehicle Assignments
    Route::middleware(['permission:assign vehicles'])->group(function () {
        Route::get('admin/assignments', [VehicleAssignmentController::class, 'index'])->name('admin.assignments.index');
        Route::get('admin/assignments/create', [VehicleAssignmentController::class, 'create'])->name('admin.assignments.create');
        Route::post('admin/assignments', [VehicleAssignmentController::class, 'store'])->name('admin.assignments.store');
        Route::post('admin/assignments/{assignment}/return', [VehicleAssignmentController::class, 'return'])->name('admin.assignments.return');
    });
    
    // Payment Management
    Route::middleware(['permission:view payments'])->group(function () {
        Route::get('admin/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
        
        Route::middleware(['permission:approve payments'])->group(function () {
            Route::post('admin/payments/{transaction}/approve', [PaymentController::class, 'approve'])->name('admin.payments.approve');
            Route::post('admin/payments/{transaction}/reject', [PaymentController::class, 'reject'])->name('admin.payments.reject');
            Route::post('admin/payments/{transaction}/restore', [PaymentController::class, 'restore'])->name('admin.payments.restore');
            Route::post('admin/payments/{transaction}/skip', [PaymentController::class, 'skipPayment'])->name('admin.payments.skip');
            Route::post('admin/payments/generate-daily-remittances', [PaymentController::class, 'generateDailyRemittances'])->name('admin.payments.generate-daily-remittances');
            Route::get('admin/payments/drivers-without-remittance', [PaymentController::class, 'getDriversWithoutRemittance'])->name('admin.payments.drivers-without-remittance');
        });
        Route::get('admin/payments/pdf', [PaymentController::class, 'generatePdf'])->name('admin.payments.pdf');
    });
    
    // Hire Purchase Management
    Route::middleware(['permission:view hire purchase'])->prefix('admin/hire-purchase')->name('admin.hire-purchase.')->group(function () {
        Route::get('/', [HirePurchaseController::class, 'index'])->name('index');
        Route::get('/create', [HirePurchaseController::class, 'create'])->name('create');
        Route::post('/', [HirePurchaseController::class, 'store'])->name('store');
        Route::get('/export', [HirePurchaseController::class, 'export'])->name('export');
        Route::get('/{hirePurchase}', [HirePurchaseController::class, 'show'])->name('show');
        Route::get('/{hirePurchase}/edit', [HirePurchaseController::class, 'edit'])->name('edit');
        Route::put('/{hirePurchase}', [HirePurchaseController::class, 'update'])->name('update');
        Route::get('/{hirePurchase}/calendar', [HirePurchaseController::class, 'calendar'])->name('calendar');
        Route::post('/{hirePurchase}/payment', [HirePurchaseController::class, 'recordPayment'])->name('payment');
        Route::post('/mark-overdue', [HirePurchaseController::class, 'markOverduePayments'])->name('mark-overdue');
        
        // Contract Management Routes
        Route::middleware(['permission:approve hire purchase contract'])->group(function () {
            Route::put('/{hirePurchase}/approve', [HirePurchaseController::class, 'approveContract'])->name('approve');
        });
        
        Route::middleware(['permission:reject hire purchase contract'])->group(function () {
            Route::put('/{hirePurchase}/reject', [HirePurchaseController::class, 'rejectContract'])->name('reject');
        });
        
        Route::middleware(['permission:terminate hire purchase contract'])->group(function () {
            Route::put('/{hirePurchase}/terminate', [HirePurchaseController::class, 'terminateContract'])->name('terminate');
        });
        
        Route::middleware(['permission:delete hire purchase'])->group(function () {
            Route::delete('/{hirePurchase}', [HirePurchaseController::class, 'destroy'])->name('destroy');
        });
        
        // Payment Management Routes
        Route::middleware(['permission:approve hire purchase payment'])->group(function () {
            Route::put('/{hirePurchase}/payments/{payment}/approve', [HirePurchaseController::class, 'approvePayment'])->name('payments.approve');
        });
        
        Route::middleware(['permission:reject hire purchase payment'])->group(function () {
            Route::put('/{hirePurchase}/payments/{payment}/reject', [HirePurchaseController::class, 'rejectPayment'])->name('payments.reject');
        });
        
        // Schedule Management Routes
        Route::middleware(['permission:edit hire purchase schedule'])->group(function () {
            Route::put('/{hirePurchase}/schedule/regenerate', [HirePurchaseController::class, 'regenerateSchedule'])->name('schedule.regenerate');
        });
        
        // Penalty Management Routes
        Route::middleware(['permission:add hire purchase penalty'])->group(function () {
            Route::post('/{hirePurchase}/penalty', [HirePurchaseController::class, 'addPenalty'])->name('penalty.add');
        });
        
        Route::middleware(['permission:waive hire purchase penalty'])->group(function () {
            Route::post('/{hirePurchase}/penalty/waive', [HirePurchaseController::class, 'waivePenalty'])->name('penalty.waive');
        });
    });
    
    // Maintenance Requests
    Route::middleware(['permission:view maintenance requests'])->group(function () {
        Route::get('admin/maintenance', [MaintenanceRequestController::class, 'index'])->name('admin.maintenance.index');
        Route::get('admin/maintenance/{maintenanceRequest}', [MaintenanceRequestController::class, 'show'])->name('admin.maintenance.show');
        
        Route::middleware(['permission:create maintenance requests'])->group(function () {
            Route::get('admin/maintenance-create', [MaintenanceRequestController::class, 'create'])->name('admin.maintenance.create');
            Route::post('admin/maintenance', [MaintenanceRequestController::class, 'store'])->name('admin.maintenance.store');
        });
        
        Route::middleware(['permission:approve maintenance requests'])->group(function () {
            Route::post('admin/maintenance/{maintenanceRequest}/approve', [MaintenanceRequestController::class, 'approve'])->name('admin.maintenance.approve');
            Route::post('admin/maintenance/{maintenanceRequest}/deny', [MaintenanceRequestController::class, 'deny'])->name('admin.maintenance.deny');
        });
        
        Route::middleware(['permission:complete maintenance requests'])->group(function () {
            Route::post('admin/maintenance/{maintenanceRequest}/complete', [MaintenanceRequestController::class, 'complete'])->name('admin.maintenance.complete');
        });
    });
    
    // Parts & Inventory
    Route::middleware(['permission:view inventory'])->group(function () {
        Route::resource('admin/parts', PartController::class)->names([
            'index' => 'admin.parts.index',
            'create' => 'admin.parts.create',
            'store' => 'admin.parts.store',
            'show' => 'admin.parts.show',
            'edit' => 'admin.parts.edit',
            'update' => 'admin.parts.update',
            'destroy' => 'admin.parts.destroy',
        ]);
        
        Route::middleware(['permission:manage inventory'])->group(function () {
            Route::post('admin/parts/{part}/stock-in', [PartController::class, 'stockIn'])->name('admin.parts.stock-in');
        });
    });
    
    // Charging Requests
    Route::get('admin/charging', [ChargingRequestController::class, 'index'])->name('admin.charging.index');
    Route::get('admin/charging/create', [ChargingRequestController::class, 'create'])->name('admin.charging.create');
    Route::post('admin/charging', [ChargingRequestController::class, 'store'])->name('admin.charging.store');
    Route::get('admin/charging/{chargingRequest}', [ChargingRequestController::class, 'show'])->name('admin.charging.show');
    Route::post('admin/charging/{chargingRequest}/start', [ChargingRequestController::class, 'startCharging'])->name('admin.charging.start');
    Route::post('admin/charging/{chargingRequest}/operator-start', [ChargingRequestController::class, 'operatorStart'])->name('admin.charging.operator-start');
    Route::post('admin/charging/{chargingRequest}/complete', [ChargingRequestController::class, 'completeCharging'])->name('admin.charging.complete');
    Route::post('admin/charging/{chargingRequest}/cancel', [ChargingRequestController::class, 'cancel'])->name('admin.charging.cancel');
    
    // Wallet Funding Requests - Admin
    Route::middleware(['permission:approve payments'])->group(function () {
        Route::get('admin/wallet-funding', [App\Http\Controllers\Admin\WalletFundingRequestController::class, 'index'])->name('admin.wallet-funding.index');
        Route::get('admin/wallet-funding/{walletFundingRequest}', [App\Http\Controllers\Admin\WalletFundingRequestController::class, 'show'])->name('admin.wallet-funding.show');
        Route::post('admin/wallet-funding/{walletFundingRequest}/approve', [App\Http\Controllers\Admin\WalletFundingRequestController::class, 'approve'])->name('admin.wallet-funding.approve');
        Route::post('admin/wallet-funding/{walletFundingRequest}/reject', [App\Http\Controllers\Admin\WalletFundingRequestController::class, 'reject'])->name('admin.wallet-funding.reject');
    });
    
    // Wallet Funding Requests - Driver
    Route::middleware(['role:Driver'])->prefix('driver')->name('driver.')->group(function () {
        Route::get('wallet-funding', [App\Http\Controllers\Driver\WalletFundingController::class, 'index'])->name('wallet-funding.index');
        Route::get('wallet-funding/create', [App\Http\Controllers\Driver\WalletFundingController::class, 'create'])->name('wallet-funding.create');
        Route::post('wallet-funding', [App\Http\Controllers\Driver\WalletFundingController::class, 'store'])->name('wallet-funding.store');
        Route::get('wallet-funding/{walletFundingRequest}', [App\Http\Controllers\Driver\WalletFundingController::class, 'show'])->name('wallet-funding.show');
    });
    
    // Company Account
    Route::middleware(['permission:view payments'])->group(function () {
        Route::get('admin/company-account', [App\Http\Controllers\Admin\CompanyAccountController::class, 'index'])->name('admin.company-account.index');
        Route::get('admin/company-account/create', [App\Http\Controllers\Admin\CompanyAccountController::class, 'create'])->name('admin.company-account.create');
        Route::post('admin/company-account', [App\Http\Controllers\Admin\CompanyAccountController::class, 'store'])->name('admin.company-account.store');
        Route::get('admin/company-account/{companyAccountTransaction}', [App\Http\Controllers\Admin\CompanyAccountController::class, 'show'])->name('admin.company-account.show');
    });

    // Account Management & Debit Requests
    Route::middleware(['permission:view company account'])->prefix('accounts')->name('accounts.')->group(function () {
        // Dashboard - View company account balance and summary
        Route::get('/', [AccountController::class, 'index'])->name('index');
        
        // Debit Request Management
        Route::prefix('debit-requests')->name('debit-requests.')->group(function () {
            // View all debit requests (with filters)
            Route::get('/', [AccountController::class, 'debitRequests'])->name('index');
            
            // Create new debit request form
            Route::middleware(['permission:create debit request'])->group(function () {
                Route::get('/create', [AccountController::class, 'createDebitRequest'])->name('create');
                Route::post('/', [AccountController::class, 'storeDebitRequest'])->name('store');
            });
            
            // View specific debit request details
            Route::get('/{debitRequest}', [AccountController::class, 'showDebitRequest'])->name('show');
            
            // Approve or reject debit request (Admin/CEO only)
            Route::middleware(['permission:approve debit requests'])->group(function () {
                Route::post('/{debitRequest}/review', [AccountController::class, 'reviewDebitRequest'])->name('review');
            });
        });
        
        // Transaction History
        Route::get('/transactions', [AccountController::class, 'transactions'])->name('transactions');
    });

    // Branch Management
    Route::resource('admin/branches', BranchController::class)->names([
        'index' => 'admin.branches.index',
        'create' => 'admin.branches.create',
        'store' => 'admin.branches.store',
        'show' => 'admin.branches.show',
        'edit' => 'admin.branches.edit',
        'update' => 'admin.branches.update',
        'destroy' => 'admin.branches.destroy',
    ]);
    
    // User Management (Admin only)
    Route::middleware(['role:Super Admin'])->group(function () {
        Route::resource('admin/users', UserController::class)->names([
            'index' => 'admin.users.index',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            'show' => 'admin.users.show',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);
        
        // Role Management (Admin only)
        Route::resource('admin/roles', RoleController::class)->names([
            'index' => 'admin.roles.index',
            'create' => 'admin.roles.create',
            'store' => 'admin.roles.store',
            'show' => 'admin.roles.show',
            'edit' => 'admin.roles.edit',
            'update' => 'admin.roles.update',
            'destroy' => 'admin.roles.destroy',
        ]);
        
        // Role Users
        Route::get('admin/roles/{role}/users', [RoleController::class, 'users'])->name('admin.roles.users');
        
        // Role Statistics
        Route::get('admin/roles/stats', [RoleController::class, 'stats'])->name('admin.roles.stats');
        
        // System Settings
        Route::get('admin/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::put('admin/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    });
});
