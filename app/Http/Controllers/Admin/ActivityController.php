<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChargingRequest;
use App\Models\MaintenanceRequest;
use App\Models\Transaction;
use App\Models\VehicleAssignment;
use App\Models\WalletFundingRequest;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $filter = $request->get('filter', 'all');

        // Get maintenance requests
        $maintenanceRequests = MaintenanceRequest::with(['driver', 'mechanic'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($filter !== 'all', function ($query) use ($filter) {
                $query->where('status', $filter);
            })
            ->latest()
            ->limit(20)
            ->get();

        // Get pending payments
        $pendingPayments = Transaction::with(['driver'])
            ->where('type', 'daily_remittance')
            ->where('status', 'pending')
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->latest()
            ->limit(20)
            ->get();

        // Get recent assignments (active = returned_at is NULL)
        $recentAssignments = VehicleAssignment::with(['driver', 'vehicle'])
            ->active()
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->latest()
            ->limit(10)
            ->get();

        // Get charging requests
        $chargingRequests = ChargingRequest::with(['driver', 'vehicle'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->latest()
            ->limit(20)
            ->get();

        // Get wallet funding requests
        $walletFundingRequests = WalletFundingRequest::with(['driver'])
            ->pending()
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->latest()
            ->limit(20)
            ->get();

        // Statistics
        $stats = [
            'pending_maintenance' => MaintenanceRequest::where('status', 'pending')->count(),
            'pending_payments' => Transaction::where('type', 'daily_remittance')
                ->where('status', 'pending')->count(),
            'active_assignments' => VehicleAssignment::active()->count(),
            'approved_maintenance' => MaintenanceRequest::where('status', 'approved')->count(),
            'pending_charging' => ChargingRequest::pending()->count(),
            'in_progress_charging' => ChargingRequest::inProgress()->count(),
            'pending_wallet_funding' => WalletFundingRequest::pending()->count(),
        ];

        return view('admin.activities.index', compact(
            'maintenanceRequests',
            'pendingPayments',
            'recentAssignments',
            'chargingRequests',
            'walletFundingRequests',
            'stats',
            'filter'
        ));
    }
}
