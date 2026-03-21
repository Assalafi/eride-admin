<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChargingOperatorController;
use App\Http\Controllers\Api\DriverApiController;
use App\Http\Controllers\Api\MechanicController;
use App\Http\Controllers\Api\AccountantApiController;
use App\Http\Controllers\Api\AdminApiController;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::post('/login', [AuthController::class, 'login']);

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Driver routes
    Route::prefix('driver')->group(function () {
        
        // Dashboard & Profile
        Route::get('/dashboard', [DriverApiController::class, 'dashboard']);
        Route::get('/profile', [DriverApiController::class, 'profile']);
        Route::post('/profile/update', [DriverApiController::class, 'updateProfile']);
        Route::post('/profile/change-password', [DriverApiController::class, 'changePassword']);
        
        // Wallet & Transactions
        Route::get('/wallet', [DriverApiController::class, 'wallet']);
        Route::post('/wallet/fund-request', [DriverApiController::class, 'requestWalletFunding']);
        Route::get('/wallet/funding-requests', [DriverApiController::class, 'walletFundingRequests']);
        Route::get('/transactions', [DriverApiController::class, 'transactions']);
        
        // Daily Remittance
        Route::get('/remittance/pending', [DriverApiController::class, 'getPendingRemittances']);
        Route::get('/remittance/all', [DriverApiController::class, 'getAllRemittances']);
        Route::post('/remittance/submit', [DriverApiController::class, 'submitPayment']);
        Route::get('/remittance/ledger-history', [DriverApiController::class, 'ledgerHistory']);
        
        // Maintenance Requests
        Route::post('/maintenance/create', [DriverApiController::class, 'createMaintenanceRequest']);
        Route::get('/maintenance/requests', [DriverApiController::class, 'maintenanceRequests']);
        Route::get('/maintenance/requests/{id}', [DriverApiController::class, 'showMaintenanceRequest']);
        
        // Charging Requests
        Route::get('/charging/cost', [DriverApiController::class, 'getChargingCost']);
        Route::post('/charging/create', [DriverApiController::class, 'createChargingRequest']);
        Route::get('/charging/requests', [DriverApiController::class, 'chargingRequests']);
        Route::get('/charging/requests/{id}', [DriverApiController::class, 'showChargingRequest']);
        
        // Vehicle Assignment
        Route::get('/vehicle/current', [DriverApiController::class, 'currentVehicle']);
        Route::get('/vehicle/history', [DriverApiController::class, 'vehicleHistory']);
        
        // Utilities
        Route::get('/mechanics', [DriverApiController::class, 'mechanics']);
    });
    
    // Charging Station Operator routes
    Route::prefix('charging-operator')->group(function () {
        
        // Get all approved charging requests
        Route::get('/requests', [ChargingOperatorController::class, 'getApprovedRequests']);
        
        // Get charging history with pagination and filters
        Route::get('/history', [ChargingOperatorController::class, 'getHistory']);
        
        // Get single charging request details
        Route::get('/requests/{id}', [ChargingOperatorController::class, 'getChargingDetails']);
        
        // Start charging session
        Route::post('/requests/{id}/start', [ChargingOperatorController::class, 'startCharging']);
        
        // Complete charging session
        Route::post('/requests/{id}/complete', [ChargingOperatorController::class, 'completeCharging']);
    });
    
    // Mechanic routes
    Route::prefix('mechanic')->group(function () {
        
        // Get driver wallet balance
        Route::get('/drivers/{driverId}/wallet', [MechanicController::class, 'getDriverWallet']);
        
        // Get active vehicles with drivers in mechanic's branch
        Route::get('/vehicles/active', [MechanicController::class, 'getActiveVehicles']);
        
        // Get available parts
        Route::get('/parts', [MechanicController::class, 'getAvailableParts']);
        
        // Create maintenance request
        Route::post('/maintenance/create', [MechanicController::class, 'createMaintenanceRequest']);
        
        // Get my maintenance requests
        Route::get('/maintenance/requests', [MechanicController::class, 'getMyRequests']);
        
        // Get maintenance request details
        Route::get('/maintenance/requests/{id}', [MechanicController::class, 'getRequestDetails']);
        
        // Complete maintenance request
        Route::post('/maintenance/requests/{id}/complete', [MechanicController::class, 'completeMaintenanceRequest']);
    });
    
    // Accountant routes
    Route::prefix('accountant')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [AccountantApiController::class, 'dashboard']);
        
        // Transactions
        Route::get('/transactions', [AccountantApiController::class, 'transactions']);
        
        // Debit Requests
        Route::get('/debit-requests', [AccountantApiController::class, 'debitRequests']);
        Route::get('/debit-requests/{id}', [AccountantApiController::class, 'showDebitRequest']);
        Route::post('/debit-requests/create', [AccountantApiController::class, 'createDebitRequest']);
        
        // Branches
        Route::get('/branches', [AccountantApiController::class, 'branches']);
        
        // Profile
        Route::get('/profile', [AccountantApiController::class, 'profile']);
        Route::post('/profile/update', [AccountantApiController::class, 'updateProfile']);
    });
});

// Accountant Login (separate from general login)
Route::post('/accountant/login', [AccountantApiController::class, 'login']);

// Admin Login (separate from general login)
Route::post('/admin/login', [AdminApiController::class, 'login']);

// Admin routes (protected)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    
    // Dashboard with Financial Analytics
    Route::get('/dashboard', [AdminApiController::class, 'dashboard']);
    Route::get('/statistics', [AdminApiController::class, 'getStatistics']);
    
    // Debit Requests Management
    Route::get('/debit-requests', [AdminApiController::class, 'debitRequests']);
    Route::get('/debit-requests/{id}', [AdminApiController::class, 'showDebitRequest']);
    Route::post('/debit-requests/{id}/review', [AdminApiController::class, 'reviewDebitRequest']);
    
    // Branch Income Breakdown
    Route::get('/branch-income', [AdminApiController::class, 'branchIncome']);
    
    // Branches
    Route::get('/branches', [AdminApiController::class, 'branches']);
    
    // Drivers Management
    Route::get('/drivers/active', [AdminApiController::class, 'getActiveDrivers']);
    Route::get('/drivers/overdue', [AdminApiController::class, 'getOverdueDrivers']);
    Route::get('/drivers/{driverId}/activity-summary', [AdminApiController::class, 'getDriverActivitySummary']);
    Route::get('/drivers/{driverId}/activities', [AdminApiController::class, 'getDriverActivities']);
    
    // Vehicles Management
    Route::get('/vehicles/active', [AdminApiController::class, 'getActiveVehicles']);
    
    // Remittance Management
    Route::get('/remittance', [AdminApiController::class, 'getRemittanceOverview']);
    
    // Charging History
    Route::get('/charging/history', [AdminApiController::class, 'getChargingHistory']);
    
    // Maintenance History
    Route::get('/maintenance/history', [AdminApiController::class, 'getMaintenanceHistory']);
    
    // Profile
    Route::get('/profile', [AdminApiController::class, 'profile']);
    Route::post('/change-password', [AdminApiController::class, 'changePassword']);
    
    // Store Inventory
    Route::get('/store/inventory', [AdminApiController::class, 'getStoreInventory']);
});
