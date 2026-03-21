<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyAccountTransaction;
use App\Models\Driver;
use App\Models\WalletFundingRequest;
use App\Services\BranchAccessService;
use App\Traits\HasDateFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WalletFundingRequestController extends Controller
{
    use HasDateFilters;

    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get filter parameters
        $timeFilter = $request->get('time_filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status');
        $driverId = $request->get('driver_id');

        // Get date range
        [$start, $end] = $this->getDateRange($timeFilter, $startDate, $endDate);

        $requests = WalletFundingRequest::with(['driver.branch', 'approver'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($driverId, function ($query) use ($driverId) {
                $query->where('driver_id', $driverId);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Statistics with date filter
        $statsQuery = WalletFundingRequest::query()
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            });

        $stats = [
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
            'total_amount_pending' => (clone $statsQuery)->where('status', 'pending')->sum('amount'),
        ];

        // Get drivers for filter
        $drivers = Driver::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
            BranchAccessService::applyBranchFilter($query, $user);
        })->get();

        return view('admin.wallet-funding.index', compact(
            'requests', 
            'stats',
            'drivers',
            'driverId',
            'status',
            'timeFilter',
            'startDate',
            'endDate'
        ));
    }

    public function show(WalletFundingRequest $walletFundingRequest)
    {
        $user = auth()->user();
        
        // Check if user can access this wallet funding request
        if (!BranchAccessService::canAccessBranch($user, $walletFundingRequest->driver->branch_id)) {
            abort(403, 'You do not have permission to access this wallet funding request.');
        }
        
        $walletFundingRequest->load(['driver.branch', 'driver.wallet', 'approver']);
        
        return view('admin.wallet-funding.show', compact('walletFundingRequest'));
    }

    public function approve(Request $request, WalletFundingRequest $walletFundingRequest)
    {
        $user = auth()->user();
        
        // Check if user can approve this wallet funding request
        if (!BranchAccessService::canAccessBranch($user, $walletFundingRequest->driver->branch_id)) {
            abort(403, 'You do not have permission to approve this wallet funding request.');
        }
        
        $request->validate([
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if (!$walletFundingRequest->isPending()) {
            return redirect()->back()
                ->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($request, $walletFundingRequest) {
            // Update request status
            $walletFundingRequest->update([
                'status' => WalletFundingRequest::STATUS_APPROVED,
                'admin_notes' => $request->admin_notes,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Fund the wallet
            $wallet = $walletFundingRequest->driver->wallet;
            $wallet->increment('balance', $walletFundingRequest->amount);

            // Log transaction
            $walletFundingRequest->driver->transactions()->create([
                'type' => 'wallet_funding',
                'amount' => $walletFundingRequest->amount,
                'balance_after' => $wallet->fresh()->balance,
                'description' => "Wallet funded - Request #{$walletFundingRequest->id}",
                'status' => 'completed',
                'processed_by' => auth()->id(),
            ]);

            // NOTE: Wallet funding is NOT company income - it's just driver depositing money
            // Company income is recorded when driver spends wallet balance on services
        });

        return redirect()->route('admin.wallet-funding.index')
            ->with('success', "Wallet funding request approved! ₦" . number_format($walletFundingRequest->amount, 2) . " added to {$walletFundingRequest->driver->full_name}'s wallet.");
    }

    public function reject(Request $request, WalletFundingRequest $walletFundingRequest)
    {
        $user = auth()->user();
        
        // Check if user can reject this wallet funding request
        if (!BranchAccessService::canAccessBranch($user, $walletFundingRequest->driver->branch_id)) {
            abort(403, 'You do not have permission to reject this wallet funding request.');
        }
        
        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        if (!$walletFundingRequest->isPending()) {
            return redirect()->back()
                ->with('error', 'This request has already been processed.');
        }

        $walletFundingRequest->update([
            'status' => WalletFundingRequest::STATUS_REJECTED,
            'admin_notes' => $request->admin_notes,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.wallet-funding.index')
            ->with('success', 'Wallet funding request rejected.');
    }
}
