<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\DriverPaymentController;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PaymentGatewayTransaction;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;

class OpayPaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = strtoupper((string) $request->query('status', ''));
        $environment = strtolower((string) $request->query('environment', ''));
        $driverId = $request->query('driver_id');
        $reference = trim((string) $request->query('reference', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = PaymentGatewayTransaction::with('driver')
            ->where('gateway', 'opay');

        if (!$user->hasRole(['Super Admin', 'Accountant'])) {
            BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
        }

        $query
            ->when(in_array($status, ['INITIAL', 'PENDING', 'SUCCESS', 'FAIL', 'CLOSE'], true), fn ($q) => $q->where('status', $status))
            ->when(in_array($environment, ['live', 'demo'], true), fn ($q) => $q->where('environment', $environment))
            ->when($driverId, fn ($q) => $q->where('driver_id', $driverId))
            ->when($reference !== '', fn ($q) => $q->where('reference', 'like', $reference . '%'))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        $summaryQuery = clone $query;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->whereIn('status', ['INITIAL', 'PENDING'])->count(),
            'successful' => (clone $summaryQuery)->where('status', 'SUCCESS')->count(),
            'failed' => (clone $summaryQuery)->whereIn('status', ['FAIL', 'CLOSE'])->count(),
            'amount' => (float) (clone $summaryQuery)->where('status', 'SUCCESS')->sum('amount'),
        ];

        $payments = $query->latest()->paginate(25)->withQueryString();
        $drivers = Driver::query()
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            })
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('admin.payments.opay', compact(
            'payments',
            'drivers',
            'status',
            'environment',
            'driverId',
            'reference',
            'dateFrom',
            'dateTo',
            'summary',
        ));
    }

    public function verify(Request $request, PaymentGatewayTransaction $payment)
    {
        $payment->loadMissing('driver');
        if ($payment->gateway !== 'opay' || !$payment->driver || !BranchAccessService::canAccessBranch(auth()->user(), $payment->driver->branch_id)) {
            abort(403, 'You do not have permission to verify this payment.');
        }

        $result = app(DriverPaymentController::class)->verifyGateway($payment);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message'],
        );
    }
}
