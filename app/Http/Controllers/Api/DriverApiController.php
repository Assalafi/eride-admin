<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingRequest;
use App\Models\Driver;
use App\Models\HirePurchaseContract;
use App\Models\HirePurchasePayment;
use App\Models\MaintenanceRequest;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\WalletFundingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverApiController extends Controller
{
    /**
     * Driver Dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('API: Driver dashboard accessed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            $driver = Driver::where('user_id', $user->id)
                ->with(['wallet', 'vehicleAssignments.vehicle', 'branch'])
                ->first();

            if (!$driver) {
                Log::warning('API: Driver profile not found for dashboard', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            $currentAssignment = $driver->vehicleAssignments()
                ->with('vehicle')
                ->whereNull('returned_at')
                ->first();
            
            $todayLedger = $driver->dailyLedgers()
                ->whereDate('date', today())
                ->first();

            // Get pending requests counts
            $pendingCounts = [
                'remittances' => Transaction::where('driver_id', $driver->id)
                    ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                    ->where('status', Transaction::STATUS_PENDING)
                    ->whereNull('payment_proof')
                    ->count(),
                'maintenance' => MaintenanceRequest::where('driver_id', $driver->id)
                    ->whereIn('status', ['pending_manager_approval', 'pending_store_approval'])
                    ->count(),
                'charging' => ChargingRequest::where('driver_id', $driver->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count(),
                'wallet_funding' => WalletFundingRequest::where('driver_id', $driver->id)
                    ->where('status', 'pending')
                    ->count(),
            ];

            // Get total counts
            $totalCounts = [
                'remittances' => Transaction::where('driver_id', $driver->id)
                    ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                    ->count(),
                'maintenance' => MaintenanceRequest::where('driver_id', $driver->id)
                    ->count(),
                'charging' => ChargingRequest::where('driver_id', $driver->id)
                    ->count(),
                'paid_remittances' => Transaction::where('driver_id', $driver->id)
                    ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                    ->whereIn('status', [Transaction::STATUS_SUCCESSFUL, 'completed', 'paid', 'approved'])
                    ->count(),
            ];

            // Get charging fee from system settings (same as charging request page)
            $chargingFee = SystemSetting::get('charging_per_session', '5000');

            // Get hire purchase data if driver is under hire purchase
            $hirePurchaseData = null;
            if ($driver->is_hire_purchase) {
                $activeContract = HirePurchaseContract::where('driver_id', $driver->id)
                    ->where('status', 'active')
                    ->with('vehicle')
                    ->first();

                if ($activeContract) {
                    $nextPayment = HirePurchasePayment::where('hire_purchase_contract_id', $activeContract->id)
                        ->whereIn('status', ['pending', 'overdue'])
                        ->orderBy('due_date')
                        ->first();

                    $overduePayments = HirePurchasePayment::where('hire_purchase_contract_id', $activeContract->id)
                        ->where('status', 'overdue')
                        ->count();

                    $hirePurchaseData = [
                        'has_active_contract' => true,
                        'contract_number' => $activeContract->contract_number,
                        'vehicle' => [
                            'plate_number' => $activeContract->vehicle->plate_number,
                            'make' => $activeContract->vehicle->make,
                            'model' => $activeContract->vehicle->model,
                        ],
                        'vehicle_price' => (float) $activeContract->vehicle_price,
                        'vehicle_price_formatted' => '₦' . number_format($activeContract->vehicle_price),
                        'total_amount' => (float) $activeContract->total_amount,
                        'total_amount_formatted' => '₦' . number_format($activeContract->total_amount),
                        'total_paid' => (float) $activeContract->total_paid,
                        'total_paid_formatted' => '₦' . number_format($activeContract->total_paid),
                        'total_balance' => (float) $activeContract->total_balance,
                        'total_balance_formatted' => '₦' . number_format($activeContract->total_balance),
                        'daily_payment' => (float) $activeContract->daily_payment,
                        'daily_payment_formatted' => '₦' . number_format($activeContract->daily_payment),
                        'progress_percentage' => $activeContract->progress_percentage,
                        'payments_made' => $activeContract->payments_made,
                        'payments_remaining' => $activeContract->payments_remaining,
                        'total_payment_days' => $activeContract->total_payment_days,
                        'start_date' => $activeContract->start_date->format('Y-m-d'),
                        'expected_end_date' => $activeContract->expected_end_date->format('Y-m-d'),
                        'next_payment_due' => $activeContract->next_payment_due ? $activeContract->next_payment_due->format('Y-m-d') : null,
                        'next_payment_amount' => $nextPayment ? (float) $nextPayment->expected_amount : null,
                        'is_overdue' => $activeContract->is_overdue,
                        'overdue_payments_count' => $overduePayments,
                        'days_until_next_payment' => $activeContract->days_until_next_payment,
                        'late_payments' => $activeContract->late_payments,
                        'missed_payments' => $activeContract->missed_payments,
                        'total_penalties' => (float) $activeContract->total_penalties,
                        'total_penalties_formatted' => '₦' . number_format($activeContract->total_penalties),
                    ];
                } else {
                    $hirePurchaseData = [
                        'has_active_contract' => false,
                        'status' => $driver->hire_purchase_status,
                        'message' => 'You are registered for hire purchase but no active contract found.',
                    ];
                }
            }

            // Calculate monthly statistics for richer dashboard
            $monthlyStats = [
                'total_remittances' => Transaction::where('driver_id', $driver->id)
                    ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                    ->where('status', 'successful')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
                'total_transactions' => Transaction::where('driver_id', $driver->id)
                    ->where('status', 'successful')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'charging_sessions' => ChargingRequest::where('driver_id', $driver->id)
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            Log::info('API: Dashboard data loaded successfully', [
                'driver_id' => $driver->id,
                'wallet_balance' => $driver->wallet->balance,
                'has_vehicle' => !is_null($currentAssignment),
                'pending_counts' => $pendingCounts,
                'is_hire_purchase' => $driver->is_hire_purchase,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'driver' => [
                        'id' => $driver->id,
                        'name' => $driver->full_name,
                        'email' => $user->email,
                        'phone' => $driver->phone_number,
                        'branch' => $driver->branch->name,
                        'profile_photo' => $driver->profile_photo ? asset('storage/' . $driver->profile_photo) : null,
                        'is_hire_purchase' => $driver->is_hire_purchase,
                        'hire_purchase_status' => $driver->hire_purchase_status,
                    ],
                    'wallet' => [
                        'balance' => (float) $driver->wallet->balance,
                        'formatted_balance' => '₦' . number_format($driver->wallet->balance, 2),
                    ],
                    'vehicle' => $currentAssignment ? [
                        'assignment_id' => $currentAssignment->id,
                        'plate_number' => $currentAssignment->vehicle->plate_number,
                        'make' => $currentAssignment->vehicle->make,
                        'model' => $currentAssignment->vehicle->model,
                        'year' => $currentAssignment->vehicle->year,
                        'assigned_at' => $currentAssignment->assigned_at->format('Y-m-d H:i:s'),
                    ] : null,
                    'daily_balance' => $todayLedger ? [
                        'required' => (float) $todayLedger->required_payment,
                        'paid' => (float) $todayLedger->amount_paid,
                        'balance' => (float) $todayLedger->balance,
                        'status' => $todayLedger->status,
                    ] : null,
                    'pending_requests' => $pendingCounts,
                    'total_counts' => $totalCounts,
                    'monthly_stats' => $monthlyStats,
                    'hire_purchase' => $hirePurchaseData,
                    'charging_fee' => (float) $chargingFee,
                    'charging_fee_formatted' => '₦' . number_format($chargingFee, 2),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API: Dashboard error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading dashboard',
            ], 500);
        }
    }

    /**
     * Get Driver Profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('API: Driver profile accessed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            $driver = Driver::where('user_id', $user->id)
                ->with(['wallet', 'branch'])
                ->first();

            if (!$driver) {
                Log::warning('API: Driver profile not found', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            Log::info('API: Profile loaded successfully', [
                'driver_id' => $driver->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $driver->id,
                    'full_name' => $driver->full_name,
                    'email' => $user->email,
                    'phone_number' => $driver->phone_number,
                    'date_of_birth' => $driver->date_of_birth ? $driver->date_of_birth->format('Y-m-d') : null,
                    'address' => $driver->address,
                    'license_number' => $driver->license_number,
                    'license_expiry' => $driver->license_expiry ? $driver->license_expiry->format('Y-m-d') : null,
                    'profile_photo' => $driver->profile_photo ? asset('storage/' . $driver->profile_photo) : null,
                    'branch' => $driver->branch->name,
                    'wallet_balance' => (float) $driver->wallet->balance,
                    'created_at' => $driver->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API: Profile error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading profile',
            ], 500);
        }
    }

    /**
     * Update Driver Profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('API: Profile update attempted', [
                'user_id' => $user->id,
                'has_photo' => $request->hasFile('profile_photo'),
                'ip' => $request->ip(),
            ]);

            $validator = Validator::make($request->all(), [
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                Log::warning('API: Profile update validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                Log::warning('API: Driver not found for profile update', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            $changes = [];

            if ($request->phone_number) {
                $changes['phone_number'] = ['old' => $driver->phone_number, 'new' => $request->phone_number];
                $driver->phone_number = $request->phone_number;
            }

            if ($request->address) {
                $changes['address'] = ['old' => $driver->address, 'new' => $request->address];
                $driver->address = $request->address;
            }

            if ($request->hasFile('profile_photo')) {
                // Delete old photo
                if ($driver->profile_photo) {
                    Storage::disk('public')->delete($driver->profile_photo);
                }

                $file = $request->file('profile_photo');
                $extension = $file->getClientOriginalExtension();
                
                // Fallback to jpg if no extension is provided (for web uploads)
                if (empty($extension)) {
                    $extension = 'jpg';
                }
                
                $fileName = 'driver_' . $driver->id . '_' . time() . '.' . $extension;
                $path = $file->storeAs('drivers/photos', $fileName, 'public');
                $changes['profile_photo'] = ['old' => $driver->profile_photo, 'new' => $path];
                $driver->profile_photo = $path;
            }

            $driver->save();

            Log::info('API: Profile updated successfully', [
                'driver_id' => $driver->id,
                'changes' => $changes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'phone_number' => $driver->phone_number,
                    'address' => $driver->address,
                    'profile_photo' => $driver->profile_photo ? asset('storage/' . $driver->profile_photo) : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API: Profile update error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating profile',
            ], 500);
        }
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('API: Password change attempted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                Log::warning('API: Password change validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                Log::warning('API: Password change failed - incorrect current password', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            Log::info('API: Password changed successfully', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('API: Password change error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while changing password',
            ], 500);
        }
    }

    /**
     * =================================
     * WALLET & TRANSACTIONS
     * =================================
     */

    /**
     * Get Wallet Balance & Details
     */
    public function wallet(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('API: Wallet accessed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            $driver = Driver::where('user_id', $user->id)->with('wallet')->first();

            if (!$driver) {
                Log::warning('API: Driver not found for wallet', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            // Recent transactions
            $recentTransactions = Transaction::where('driver_id', $driver->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function($txn) {
                    return [
                        'id' => $txn->id,
                        'type' => $txn->type,
                        'amount' => (float) $txn->amount,
                        'description' => $txn->description,
                        'status' => $txn->status,
                        'created_at' => $txn->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            Log::info('API: Wallet loaded successfully', [
                'driver_id' => $driver->id,
                'balance' => $driver->wallet->balance,
                'transaction_count' => $recentTransactions->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => (float) $driver->wallet->balance,
                    'formatted_balance' => '₦' . number_format($driver->wallet->balance, 2),
                    'recent_transactions' => $recentTransactions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API: Wallet error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading wallet',
            ], 500);
        }
    }

    /**
     * Request Wallet Funding
     */
    public function requestWalletFunding(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            
            Log::info('API: Wallet funding request initiated', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'ip' => $request->ip(),
            ]);

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'payment_proof' => 'required|image|mimes:jpg,jpeg,png,pdf|max:5120',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                Log::warning('API: Wallet funding validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                Log::warning('API: Driver not found for wallet funding', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            // Upload payment proof
            $file = $request->file('payment_proof');
            $extension = $file->getClientOriginalExtension();
            
            // Fallback to jpg if no extension is provided (for web uploads)
            if (empty($extension)) {
                $extension = 'jpg';
            }
            
            $fileName = 'wallet_funding_' . $driver->id . '_' . time() . '.' . $extension;
            $proofPath = $file->storeAs('wallet-funding/proofs', $fileName, 'public');
            
            Log::info('API: Payment proof uploaded', [
                'driver_id' => $driver->id,
                'file_path' => $proofPath,
                'extension' => $extension,
            ]);

            $fundingRequest = WalletFundingRequest::create([
                'driver_id' => $driver->id,
                'amount' => $request->amount,
                'receipt_image' => $proofPath,
                'description' => $request->notes,
                'status' => 'pending',
            ]);
            
            DB::commit();

            Log::info('API: Wallet funding request created successfully', [
                'request_id' => $fundingRequest->id,
                'driver_id' => $driver->id,
                'amount' => $fundingRequest->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wallet funding request submitted successfully. Awaiting approval.',
                'data' => [
                    'id' => $fundingRequest->id,
                    'amount' => (float) $fundingRequest->amount,
                    'status' => $fundingRequest->status,
                    'created_at' => $fundingRequest->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('API: Wallet funding request error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating funding request',
            ], 500);
        }
    }

    /**
     * Get Wallet Funding Requests
     */
    public function walletFundingRequests(Request $request)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $requests = WalletFundingRequest::where('driver_id', $driver->id)
            ->with('approver')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests->map(function($req) {
                return [
                    'id' => $req->id,
                    'amount' => (float) $req->amount,
                    'receipt_image' => $req->receipt_image ? asset('storage/' . $req->receipt_image) : null,
                    'description' => $req->description,
                    'status' => $req->status,
                    'admin_notes' => $req->admin_notes,
                    'approved_by' => $req->approver ? $req->approver->name : null,
                    'approved_at' => $req->approved_at ? $req->approved_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Get Transaction History
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $transactions = Transaction::where('driver_id', $driver->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions->map(function($txn) {
                return [
                    'id' => $txn->id,
                    'type' => $txn->type,
                    'amount' => (float) $txn->amount,
                    'description' => $txn->description,
                    'reference' => $txn->reference,
                    'status' => $txn->status,
                    'created_at' => $txn->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * =================================
     * DAILY REMITTANCE
     * =================================
     */

    /**
     * Get Pending Remittances
     */
    public function getPendingRemittances(Request $request)
    {
        try {
            $user = $request->user();
            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            // Get remittances that are pending and don't have payment proof yet
            $pendingRemittances = Transaction::where('driver_id', $driver->id)
                ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                ->where('status', Transaction::STATUS_PENDING)
                ->whereNull('payment_proof')
                ->latest()
                ->get()
                ->map(function($txn) {
                    return [
                        'id' => $txn->id,
                        'amount' => (float) $txn->amount,
                        'formatted_amount' => '₦' . number_format($txn->amount, 2),
                        'reference' => $txn->reference,
                        'description' => $txn->description,
                        'status' => 'pending',
                        'created_at' => $txn->created_at->format('Y-m-d H:i:s'),
                        'created_date' => $txn->created_at->format('M d, Y'),
                    ];
                });

            Log::info('API: Pending remittances retrieved', [
                'driver_id' => $driver->id,
                'count' => $pendingRemittances->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $pendingRemittances,
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error fetching pending remittances', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching remittances',
            ], 500);
        }
    }

    /**
     * Get All Remittances (History)
     */
    public function getAllRemittances(Request $request)
    {
        try {
            $user = $request->user();
            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            // Get all remittances for this driver with all statuses
            $allRemittances = Transaction::where('driver_id', $driver->id)
                ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                ->latest()
                ->get()
                ->map(function($txn) {
                    // Determine status based on payment proof and transaction status
                    $status = 'pending';
                    if ($txn->payment_proof) {
                        // Has payment proof - check transaction status
                        if ($txn->status === Transaction::STATUS_SUCCESSFUL) {
                            $status = 'approved';
                        } elseif ($txn->status === Transaction::STATUS_REJECTED) {
                            $status = 'rejected';
                        } else {
                            $status = 'submitted'; // Has proof but pending approval
                        }
                    }

                    return [
                        'id' => $txn->id,
                        'amount' => (float) $txn->amount,
                        'formatted_amount' => '₦' . number_format($txn->amount, 2),
                        'reference' => $txn->reference,
                        'description' => $txn->description,
                        'status' => $status,
                        'payment_proof' => $txn->payment_proof ? url('storage/' . $txn->payment_proof) : null,
                        'created_at' => $txn->created_at->format('Y-m-d H:i:s'),
                        'created_date' => $txn->created_at->format('M d, Y'),
                    ];
                });

            Log::info('API: All remittances retrieved', [
                'driver_id' => $driver->id,
                'count' => $allRemittances->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $allRemittances,
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error fetching all remittances', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching remittances',
            ], 500);
        }
    }

    /**
     * Submit Daily Remittance Payment
     */
    public function submitPayment(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            
            Log::info('API: Daily remittance submission initiated', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'ip' => $request->ip(),
            ]);

            $validator = Validator::make($request->all(), [
                'transaction_id' => 'nullable|exists:transactions,id',
                'amount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500',
                'payment_proof' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
            ]);

            if ($validator->fails()) {
                Log::warning('API: Daily remittance validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                Log::warning('API: Driver not found for daily remittance', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            // Upload payment proof
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $extension = $file->getClientOriginalExtension();
                
                // Fallback to jpg if no extension is provided (for web uploads)
                if (empty($extension)) {
                    $extension = 'jpg';
                }
                
                $fileName = 'remittance_' . $driver->id . '_' . time() . '.' . $extension;
                $paymentProofPath = $file->storeAs('payment_proofs', $fileName, 'public');
                
                Log::info('API: Payment proof uploaded', [
                    'driver_id' => $driver->id,
                    'file_path' => $paymentProofPath,
                    'file_size' => $file->getSize(),
                    'extension' => $extension,
                ]);
            }
            
            // Check if updating existing transaction or creating new one
            if ($request->transaction_id) {
                // Update existing transaction with payment proof
                $transaction = Transaction::where('id', $request->transaction_id)
                    ->where('driver_id', $driver->id)
                    ->where('type', 'daily_remittance')
                    ->whereNull('payment_proof')
                    ->firstOrFail();
                
                $updateData = [
                    'payment_proof' => $paymentProofPath,
                    'paid_at' => now(),
                    'description' => $request->notes ?? $transaction->description,
                ];
                
                // Only update amount if provided
                if ($request->has('amount')) {
                    $updateData['amount'] = $request->amount;
                }
                
                $transaction->update($updateData);
                
                $reference = $transaction->reference;
            } else {
                // Create new transaction
                $reference = 'DRP-' . strtoupper(uniqid());
                
                $createData = [
                    'driver_id' => $driver->id,
                    'type' => 'daily_remittance',
                    'description' => $request->notes ?? 'Daily remittance payment',
                    'reference' => $reference,
                    'payment_proof' => $paymentProofPath,
                    'paid_at' => now(),
                    'status' => 'pending',
                ];
                
                // Only add amount if provided
                if ($request->has('amount')) {
                    $createData['amount'] = $request->amount;
                }
                
                $transaction = Transaction::create($createData);
            }
            
            DB::commit();

            Log::info('API: Daily remittance submitted successfully', [
                'transaction_id' => $transaction->id,
                'driver_id' => $driver->id,
                'amount' => $transaction->amount,
                'reference' => $reference,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Daily remittance submitted successfully. Awaiting approval.',
                'data' => [
                    'id' => $transaction->id,
                    'amount' => (float) $transaction->amount,
                    'reference' => $transaction->reference,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('API: Daily remittance submission error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting payment',
            ], 500);
        }
    }

    /**
     * Get Ledger History
     */
    public function ledgerHistory(Request $request)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $ledgers = $driver->dailyLedgers()
            ->latest('date')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $ledgers->map(function($ledger) {
                return [
                    'id' => $ledger->id,
                    'date' => $ledger->date->format('Y-m-d'),
                    'required_payment' => (float) $ledger->required_payment,
                    'amount_paid' => (float) $ledger->amount_paid,
                    'balance' => (float) $ledger->balance,
                    'status' => $ledger->status,
                ];
            }),
            'pagination' => [
                'current_page' => $ledgers->currentPage(),
                'last_page' => $ledgers->lastPage(),
                'per_page' => $ledgers->perPage(),
                'total' => $ledgers->total(),
            ],
        ]);
    }

    /**
     * =================================
     * MAINTENANCE REQUESTS
     * =================================
     */

    /**
     * Create Maintenance Request
     */
    public function createMaintenanceRequest(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            
            Log::info('API: Maintenance request creation initiated', [
                'user_id' => $user->id,
                'mechanic_id' => $request->mechanic_id,
                'has_photos' => $request->hasFile('issue_photos'),
                'ip' => $request->ip(),
            ]);

            $validator = Validator::make($request->all(), [
                'mechanic_id' => 'required|exists:mechanics,id',
                'issue_description' => 'required|string|max:1000',
                'issue_photos' => 'nullable|array|max:5',
                'issue_photos.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            ]);

            if ($validator->fails()) {
                Log::warning('API: Maintenance request validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                Log::warning('API: Driver not found for maintenance request', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            // Check if driver has assigned vehicle
            $currentAssignment = $driver->vehicleAssignments()->whereNull('returned_at')->first();
            if (!$currentAssignment) {
                Log::warning('API: No vehicle assigned for maintenance request', [
                    'driver_id' => $driver->id,
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have an assigned vehicle',
                ], 422);
            }

            $photos = [];
            if ($request->hasFile('issue_photos')) {
                foreach ($request->file('issue_photos') as $index => $photo) {
                    $extension = $photo->getClientOriginalExtension();
                    
                    // Fallback to jpg if no extension is provided (for web uploads)
                    if (empty($extension)) {
                        $extension = 'jpg';
                    }
                    
                    $fileName = 'maintenance_' . $driver->id . '_' . time() . '_' . $index . '.' . $extension;
                    $path = $photo->storeAs('maintenance/issues', $fileName, 'public');
                    $photos[] = $path;
                }
                
                Log::info('API: Issue photos uploaded', [
                    'driver_id' => $driver->id,
                    'photo_count' => count($photos),
                    'paths' => $photos,
                ]);
            }

            $maintenanceRequest = MaintenanceRequest::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $currentAssignment->vehicle_id,
                'mechanic_id' => $request->mechanic_id,
                'issue_description' => $request->issue_description,
                'issue_photos' => !empty($photos) ? json_encode($photos) : null,
                'status' => 'pending_manager_approval',
            ]);
            
            DB::commit();

            Log::info('API: Maintenance request created successfully', [
                'request_id' => $maintenanceRequest->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $currentAssignment->vehicle_id,
                'mechanic_id' => $request->mechanic_id,
                'photo_count' => count($photos),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance request created successfully. Awaiting manager approval.',
                'data' => [
                    'id' => $maintenanceRequest->id,
                    'issue_description' => $maintenanceRequest->issue_description,
                    'status' => $maintenanceRequest->status,
                    'created_at' => $maintenanceRequest->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('API: Maintenance request creation error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating maintenance request',
            ], 500);
        }
    }

    /**
     * Get Maintenance Requests
     */
    public function maintenanceRequests(Request $request)
    {
        try {
            $user = $request->user();
            \Log::info('Maintenance Requests API called', ['user_id' => $user->id]);
            
            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                \Log::warning('Driver profile not found', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            \Log::info('Loading maintenance requests', ['driver_id' => $driver->id]);
            
            $requests = MaintenanceRequest::where('driver_id', $driver->id)
                ->with(['driver.vehicleAssignments.vehicle', 'mechanic', 'parts'])
                ->latest()
                ->paginate(20);

            \Log::info('Maintenance requests loaded', [
                'count' => $requests->count(),
                'total' => $requests->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'requests' => $requests->map(function($req) {
                        $vehicle = $req->vehicle;
                        $issuePhotos = $req->issue_photos ? json_decode($req->issue_photos, true) : [];
                        
                        // Calculate total cost from parts
                        $totalCost = $req->parts->sum(function($part) {
                            $unitCost = (float)($part->pivot->unit_cost ?? 0);
                            $quantity = (float)($part->pivot->quantity ?? 0);
                            $cost = $unitCost * $quantity;
                            
                            \Log::debug('Part cost calculation', [
                                'part' => $part->name,
                                'unit_cost' => $unitCost,
                                'quantity' => $quantity,
                                'total' => $cost
                            ]);
                            
                            return $cost;
                        });
                        
                        \Log::info('Processing maintenance request', [
                            'request_id' => $req->id,
                            'parts_count' => $req->parts->count(),
                            'total_cost' => $totalCost
                        ]);
                        
                        return [
                            'id' => $req->id,
                            'vehicle' => [
                                'id' => $vehicle->id ?? 0,
                                'plate_number' => $vehicle->plate_number ?? 'N/A',
                                'model' => ($vehicle ? trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')) : 'N/A') ?: 'N/A',
                            ],
                            'description' => $req->issue_description ?? '',
                            'photo_url' => !empty($issuePhotos) ? asset('storage/' . $issuePhotos[0]) : null,
                            'parts' => $req->parts->map(function($part) {
                                $photoUrl = null;
                                if ($part->picture) {
                                    // Check if picture already includes 'storage/' prefix
                                    $photoUrl = str_starts_with($part->picture, 'storage/') 
                                        ? asset($part->picture) 
                                        : asset('storage/' . $part->picture);
                                    
                                    \Log::debug('Part photo URL generated', [
                                        'part_id' => $part->id,
                                        'part_name' => $part->name,
                                        'picture_path' => $part->picture,
                                        'generated_url' => $photoUrl
                                    ]);
                                }
                                
                                return [
                                    'id' => $part->id,
                                    'name' => $part->name ?? '',
                                    'quantity' => (int)($part->pivot->quantity ?? 0),
                                    'unit_cost' => (float)($part->pivot->unit_cost ?? 0),
                                    'total_cost' => (float)($part->pivot->total_cost ?? 0),
                                    'photo_url' => $photoUrl,
                                ];
                            })->toArray(),
                            'total_cost' => $totalCost,
                            'status' => $req->status ?? 'pending',
                            'created_at' => $req->created_at ? $req->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                            'completed_at' => $req->completed_at ? $req->completed_at->format('Y-m-d H:i:s') : null,
                            'admin_remarks' => $req->manager_notes ?? null,
                        ];
                    }),
                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                ],
            ],
        ]);
        } catch (\Exception $e) {
            \Log::error('Error loading maintenance requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load maintenance requests',
            ], 500);
        }
    }

    /**
     * Get Single Maintenance Request
     */
    public function showMaintenanceRequest(Request $request, $id)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $maintenanceRequest = MaintenanceRequest::where('id', $id)
            ->where('driver_id', $driver->id)
            ->with(['vehicle', 'mechanic', 'parts'])
            ->first();

        if (!$maintenanceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance request not found',
            ], 404);
        }

        $issuePhotos = $maintenanceRequest->issue_photos ? json_decode($maintenanceRequest->issue_photos, true) : [];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $maintenanceRequest->id,
                'vehicle' => [
                    'plate_number' => $maintenanceRequest->vehicle->plate_number,
                    'make' => $maintenanceRequest->vehicle->make,
                    'model' => $maintenanceRequest->vehicle->model,
                    'year' => $maintenanceRequest->vehicle->year,
                ],
                'mechanic' => [
                    'id' => $maintenanceRequest->mechanic->id,
                    'name' => $maintenanceRequest->mechanic->name,
                    'phone' => $maintenanceRequest->mechanic->phone_number,
                ],
                'issue_description' => $maintenanceRequest->issue_description,
                'issue_photos' => array_map(function($photo) {
                    return asset('storage/' . $photo);
                }, $issuePhotos),
                'parts' => $maintenanceRequest->parts->map(function($part) {
                    return [
                        'id' => $part->id,
                        'name' => $part->name,
                        'quantity' => $part->pivot->quantity,
                        'unit_cost' => (float) $part->pivot->unit_cost,
                        'total_cost' => (float) $part->pivot->total_cost,
                    ];
                }),
                'total_cost' => (float) $maintenanceRequest->total_cost,
                'status' => $maintenanceRequest->status,
                'manager_notes' => $maintenanceRequest->manager_notes,
                'approved_by' => $maintenanceRequest->approvedBy ? $maintenanceRequest->approvedBy->name : null,
                'approved_at' => $maintenanceRequest->approved_at ? $maintenanceRequest->approved_at->format('Y-m-d H:i:s') : null,
                'created_at' => $maintenanceRequest->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * =================================
     * CHARGING REQUESTS
     * =================================
     */

    /**
     * Get Charging Cost Setting
     */
    public function getChargingCost(Request $request)
    {
        try {
            $chargingCost = SystemSetting::get('charging_per_session', '5000');
            
            Log::info('API: Charging cost retrieved', [
                'user_id' => $request->user()->id,
                'charging_cost' => $chargingCost,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'charging_cost' => (float) $chargingCost,
                    'formatted_cost' => '₦' . number_format($chargingCost, 2),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error fetching charging cost', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching charging cost',
            ], 500);
        }
    }

    /**
     * Create Charging Request
     */
    public function createChargingRequest(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            
            Log::info('API: Charging request creation initiated', [
                'user_id' => $user->id,
                'submitted_amount' => $request->input('charging_cost'),
                'has_receipt' => $request->hasFile('payment_receipt'),
                'ip' => $request->ip(),
            ]);

            $validator = Validator::make($request->all(), [
                'charging_cost' => 'required|numeric|min:0.01',
                'payment_receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                Log::warning('API: Charging request validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $driver = Driver::where('user_id', $user->id)->first();

            if (!$driver) {
                Log::warning('API: Driver not found for charging request', [
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Driver profile not found',
                ], 404);
            }

            // Check if driver has assigned vehicle
            $currentAssignment = $driver->vehicleAssignments()->whereNull('returned_at')->first();
            if (!$currentAssignment) {
                Log::warning('API: No vehicle assigned for charging request', [
                    'driver_id' => $driver->id,
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have an assigned vehicle',
                ], 422);
            }

            // Upload payment receipt
            $file = $request->file('payment_receipt');
            $extension = $file->getClientOriginalExtension();
            
            // Fallback to jpg if no extension is provided (for web uploads)
            if (empty($extension)) {
                $extension = 'jpg';
            }
            
            $fileName = 'charging_' . $driver->id . '_' . time() . '.' . $extension;
            $receiptPath = $file->storeAs('charging/receipts', $fileName, 'public');
            
            // Get the charging cost from driver's input
            $chargingCost = $request->input('charging_cost');
            
            Log::info('API: Charging receipt uploaded', [
                'driver_id' => $driver->id,
                'file_path' => $receiptPath,
                'charging_cost' => $chargingCost,
                'extension' => $extension,
            ]);

            $chargingRequest = ChargingRequest::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $currentAssignment->vehicle_id,
                'charging_cost' => $chargingCost,
                'payment_receipt' => $receiptPath,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);
            
            DB::commit();

            Log::info('API: Charging request created successfully', [
                'request_id' => $chargingRequest->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $currentAssignment->vehicle_id,
                'charging_cost' => $chargingRequest->charging_cost,
                'receipt_path' => $receiptPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Charging request created successfully',
                'data' => [
                    'id' => $chargingRequest->id,
                    'charging_cost' => (float) $chargingRequest->charging_cost,
                    'status' => $chargingRequest->status,
                    'created_at' => $chargingRequest->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('API: Charging request creation error', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating charging request',
            ], 500);
        }
    }

    /**
     * Get Charging Requests
     */
    public function chargingRequests(Request $request)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $requests = ChargingRequest::where('driver_id', $driver->id)
            ->with('vehicle')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests->map(function($req) {
                return [
                    'id' => $req->id,
                    'vehicle' => [
                        'plate_number' => $req->vehicle->plate_number,
                        'make' => $req->vehicle->make,
                        'model' => $req->vehicle->model,
                    ],
                    'charging_cost' => (float) $req->charging_cost,
                    'payment_receipt' => asset('storage/' . $req->payment_receipt),
                    'notes' => $req->notes,
                    'status' => $req->status,
                    'started_at' => $req->started_at ? $req->started_at->format('Y-m-d H:i:s') : null,
                    'completed_at' => $req->completed_at ? $req->completed_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Get Single Charging Request
     */
    public function showChargingRequest(Request $request, $id)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $chargingRequest = ChargingRequest::where('id', $id)
            ->where('driver_id', $driver->id)
            ->with(['vehicle', 'approver'])
            ->first();

        if (!$chargingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Charging request not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chargingRequest->id,
                'vehicle' => [
                    'plate_number' => $chargingRequest->vehicle->plate_number,
                    'make' => $chargingRequest->vehicle->make,
                    'model' => $chargingRequest->vehicle->model,
                    'year' => $chargingRequest->vehicle->year,
                ],
                'charging_cost' => (float) $chargingRequest->charging_cost,
                'payment_receipt' => asset('storage/' . $chargingRequest->payment_receipt),
                'notes' => $chargingRequest->notes,
                'status' => $chargingRequest->status,
                'approved_by' => $chargingRequest->approver ? $chargingRequest->approver->name : null,
                'started_at' => $chargingRequest->started_at ? $chargingRequest->started_at->format('Y-m-d H:i:s') : null,
                'completed_at' => $chargingRequest->completed_at ? $chargingRequest->completed_at->format('Y-m-d H:i:s') : null,
                'created_at' => $chargingRequest->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * =================================
     * VEHICLE ASSIGNMENT
     * =================================
     */

    /**
     * Get Current Vehicle Assignment
     */
    public function currentVehicle(Request $request)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $currentAssignment = $driver->vehicleAssignments()
            ->with('vehicle')
            ->whereNull('returned_at')
            ->first();

        if (!$currentAssignment) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No vehicle currently assigned',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assignment_id' => $currentAssignment->id,
                'vehicle' => [
                    'id' => $currentAssignment->vehicle->id,
                    'plate_number' => $currentAssignment->vehicle->plate_number,
                    'make' => $currentAssignment->vehicle->make,
                    'model' => $currentAssignment->vehicle->model,
                    'year' => $currentAssignment->vehicle->year,
                    'vin' => $currentAssignment->vehicle->vin,
                    'color' => $currentAssignment->vehicle->color,
                ],
                'assigned_at' => $currentAssignment->assigned_at->format('Y-m-d H:i:s'),
                'assigned_by' => $currentAssignment->assignedBy ? $currentAssignment->assignedBy->name : null,
            ],
        ]);
    }

    /**
     * Get Vehicle Assignment History
     */
    public function vehicleHistory(Request $request)
    {
        $user = $request->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $assignments = $driver->vehicleAssignments()
            ->with('vehicle')
            ->latest('assigned_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $assignments->map(function($assignment) {
                return [
                    'id' => $assignment->id,
                    'vehicle' => [
                        'plate_number' => $assignment->vehicle->plate_number,
                        'make' => $assignment->vehicle->make,
                        'model' => $assignment->vehicle->model,
                    ],
                    'assigned_at' => $assignment->assigned_at->format('Y-m-d H:i:s'),
                    'returned_at' => $assignment->returned_at ? $assignment->returned_at->format('Y-m-d H:i:s') : null,
                    'is_active' => is_null($assignment->returned_at),
                ];
            }),
            'pagination' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }

    /**
     * =================================
     * UTILITY FUNCTIONS
     * =================================
     */

    /**
     * Get Available Mechanics
     */
    public function mechanics(Request $request)
    {
        $mechanics = \App\Models\Mechanic::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $mechanics->map(function($mechanic) {
                return [
                    'id' => $mechanic->id,
                    'name' => $mechanic->name,
                    'phone_number' => $mechanic->phone_number,
                    'specialization' => $mechanic->specialization,
                ];
            }),
        ]);
    }
}
