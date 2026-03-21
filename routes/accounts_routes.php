<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Account Management Routes
|--------------------------------------------------------------------------
|
| Routes for company account management, debit requests, and transactions
| All routes require authentication
|
*/

Route::middleware(['auth'])->prefix('accounts')->name('accounts.')->group(function () {
    
    // Dashboard - View company account balance and summary
    Route::get('/', [AccountController::class, 'index'])->name('index');
    
    // Debit Request Management
    Route::prefix('debit-requests')->name('debit-requests.')->group(function () {
        // View all debit requests (with filters)
        Route::get('/', [AccountController::class, 'debitRequests'])->name('index');
        
        // Create new debit request form
        Route::get('/create', [AccountController::class, 'createDebitRequest'])->name('create');
        
        // Store new debit request
        Route::post('/', [AccountController::class, 'storeDebitRequest'])->name('store');
        
        // View specific debit request details
        Route::get('/{debitRequest}', [AccountController::class, 'showDebitRequest'])->name('show');
        
        // Approve or reject debit request (Admin/CEO only)
        Route::post('/{debitRequest}/review', [AccountController::class, 'reviewDebitRequest'])->name('review');
    });
    
    // Transaction History
    Route::get('/transactions', [AccountController::class, 'transactions'])->name('transactions');
});
