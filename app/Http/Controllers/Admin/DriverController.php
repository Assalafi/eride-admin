<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Models\Branch;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $drivers = Driver::with(['user', 'branch'])
            ->when(!$user->hasRole('Super Admin'), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->paginate(20);

        return view('admin.drivers.index', compact('drivers'));
    }

    public function create()
    {
        $branches = \App\Models\Branch::all();
        return view('admin.drivers.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validationRules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:drivers,phone_number',
            'password' => 'required|string|min:8|confirmed',
        ];

        // Super Admin and Accountant must select a branch, multi-branch managers can also select
        if (auth()->user()->hasRole(['Super Admin', 'Accountant']) || count(BranchAccessService::getUserBranchIds(auth()->user())) > 1) {
            $validationRules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($validationRules);

        // Determine branch_id: from request for Super Admin/multi-branch, from primary branch for others
        $branchId = $request->branch_id ?? auth()->user()->primary_branch_id ?? auth()->user()->branch_id;

        DB::transaction(function () use ($request, $branchId) {
            // Create user account
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'branch_id' => $branchId,
            ]);

            $user->assignRole('Driver');

            // Create driver record
            $driver = Driver::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'is_hire_purchase' => $request->boolean('is_hire_purchase'),
                'hire_purchase_status' => $request->boolean('is_hire_purchase') ? 'pending' : null,
            ]);

            // Create wallet
            Wallet::create([
                'driver_id' => $driver->id,
                'balance' => 0,
            ]);
        });

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver created successfully!');
    }

    public function show(Driver $driver)
    {
        $user = auth()->user();
        
        // Check if user can access this driver
        if (!BranchAccessService::canAccessBranch($user, $driver->branch_id)) {
            abort(403, 'You do not have permission to access this driver.');
        }
        
        $driver->load(['user', 'wallet', 'vehicleAssignments.vehicle', 'dailyLedgers', 'transactions']);
        
        return view('admin.drivers.show', compact('driver'));
    }

    public function edit(Driver $driver)
    {
        $user = auth()->user();
        
        // Check if user can edit this driver
        if (!BranchAccessService::canAccessBranch($user, $driver->branch_id)) {
            abort(403, 'You do not have permission to edit this driver.');
        }
        
        return view('admin.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $user = auth()->user();
        
        // Check if user can update this driver
        if (!BranchAccessService::canAccessBranch($user, $driver->branch_id)) {
            abort(403, 'You do not have permission to update this driver.');
        }
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $driver->user_id,
            'phone_number' => 'required|string|unique:drivers,phone_number,' . $driver->id,
        ]);

        DB::transaction(function () use ($request, $driver) {
            // Update driver
            $driver->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'is_hire_purchase' => $request->has('is_hire_purchase'),
                'hire_purchase_status' => $request->has('is_hire_purchase') 
                    ? ($driver->is_hire_purchase ? $driver->hire_purchase_status : 'pending') 
                    : null,
            ]);

            // Update user
            $driver->user->update([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
            ]);

            // Update password if provided
            if ($request->filled('password')) {
                $driver->user->update([
                    'password' => Hash::make($request->password),
                ]);
            }
        });

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver updated successfully!');
    }

    public function destroy(Driver $driver)
    {
        $user = auth()->user();
        
        // Check if user can delete this driver
        if (!BranchAccessService::canAccessBranch($user, $driver->branch_id)) {
            abort(403, 'You do not have permission to delete this driver.');
        }
        
        DB::transaction(function () use ($driver) {
            $driver->user->delete();
            $driver->delete();
        });

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver deleted successfully!');
    }

    public function fundWallet(Request $request, Driver $driver)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $driver) {
            // Update wallet balance
            $driver->wallet->increment('balance', $request->amount);

            // Create transaction record
            \App\Models\Transaction::create([
                'driver_id' => $driver->id,
                'type' => 'credit',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Wallet funding by admin',
                'status' => 'successful',
                'reference' => 'FUND-' . strtoupper(uniqid()),
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            // Update today's ledger if exists
            $today = now()->format('Y-m-d');
            $ledger = \App\Models\DailyLedger::where('driver_id', $driver->id)
                ->where('date', $today)
                ->first();

            if ($ledger) {
                $ledger->increment('total_credits', $request->amount);
            }
        });

        return redirect()->route('admin.drivers.show', $driver)
            ->with('success', 'Wallet funded successfully! Amount: ₦' . number_format($request->amount, 2));
    }
}
