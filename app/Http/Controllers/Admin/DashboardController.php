<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyAccountTransaction;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Financial stats for this month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Calculate income using same logic as PaymentController
        $monthlyIncomeSummary = $this->calculateIncomeSummary($user, $startOfMonth, $endOfMonth, null, null, null, null, null);
        $totalIncomeSummary = $this->calculateIncomeSummary($user, null, null, null, null, null, null, null);

        $incomeThisMonth = $monthlyIncomeSummary['total'];
        $totalIncome = $totalIncomeSummary['total'];

        // For expenses, still use CompanyAccountTransaction (assuming expenses are only recorded there)
        $expensesThisMonth = CompanyAccountTransaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->sum('amount');

        $totalExpenses = CompanyAccountTransaction::where('type', 'expense')
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->sum('amount');

        $pendingPaymentsAmount = Transaction::where('status', 'pending')
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->sum('amount');

        $totalWalletBalance = Driver::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->get()
            ->sum(function($driver) {
                return $driver->wallet ? $driver->wallet->balance : 0;
            });

        $stats = [
            'total_drivers' => Driver::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })->count(),

            'total_vehicles' => Vehicle::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })->count(),

            'active_assignments' => VehicleAssignment::whereNull('returned_at')
                ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                    BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
                })->count(),

            'pending_payments' => Transaction::where('status', 'pending')
                ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                    BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
                })->count(),

            // Financial metrics
            'income_this_month' => $incomeThisMonth,
            'expenses_this_month' => $expensesThisMonth,
            'net_this_month' => $incomeThisMonth - $expensesThisMonth,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'company_balance' => $totalIncome - $totalExpenses,
            'pending_payments_amount' => $pendingPaymentsAmount,
            'total_wallet_balance' => $totalWalletBalance,
        ];

        $recentTransactions = Transaction::with(['driver', 'approver'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->latest()
            ->limit(10)
            ->get();

        $activeAssignments = VehicleAssignment::with(['driver', 'vehicle'])
            ->whereNull('returned_at')
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentTransactions', 'activeAssignments'));
    }

    /**
     * Calculate income summary using same logic as PaymentController
     */
    private function calculateIncomeSummary($user, $start = null, $end = null, $status = null, $type = null, $driverId = null, $chargingStatus = null, $branchId = null)
    {
        $incomeSummary = [
            'daily_remittance' => 0,
            'charging' => 0,
            'maintenance' => 0,
            'total' => 0
        ];

        // Daily Remittance from transactions
        $dailyRemittanceQuery = Transaction::where('type', 'daily_remittance')
            ->where('status', 'successful')
            ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                if ($branchId) {
                    $query->whereHas('driver', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                }
            }, function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->when($driverId, function ($query) use ($driverId) {
                $query->where('driver_id', $driverId);
            });
            
        $incomeSummary['daily_remittance'] = $dailyRemittanceQuery->sum('amount');

        // Charging from charging requests
        $chargingQuery = \App\Models\ChargingRequest::with(['driver'])
            ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                if ($branchId) {
                    $query->whereHas('driver', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                }
            }, function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->when($driverId, function ($query) use ($driverId) {
                $query->where('driver_id', $driverId);
            })
            ->when($chargingStatus, function ($query) use ($chargingStatus) {
                $query->where('status', $chargingStatus);
            })
            ->whereIn('status', ['approved', 'completed']);
            
        $incomeSummary['charging'] = $chargingQuery->sum('charging_cost');

        // Maintenance from transactions
        $maintenanceQuery = Transaction::where('type', 'maintenance_debit')
            ->where('status', 'successful')
            ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                if ($branchId) {
                    $query->whereHas('driver', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                }
            }, function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->when($driverId, function ($query) use ($driverId) {
                $query->where('driver_id', $driverId);
            });
            
        $incomeSummary['maintenance'] = $maintenanceQuery->sum('amount');

        $incomeSummary['total'] = $incomeSummary['daily_remittance'] + $incomeSummary['charging'] + $incomeSummary['maintenance'];

        return $incomeSummary;
    }
}
