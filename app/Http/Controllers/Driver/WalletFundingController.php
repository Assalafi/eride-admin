<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\WalletFundingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WalletFundingController extends Controller
{
    public function index()
    {
        $driver = auth()->user()->driver;
        
        $requests = WalletFundingRequest::where('driver_id', $driver->id)
            ->with('approver')
            ->latest()
            ->paginate(20);

        return view('driver.wallet-funding.index', compact('requests', 'driver'));
    }

    public function create()
    {
        $driver = auth()->user()->driver;
        return view('driver.wallet-funding.create', compact('driver'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:1000000',
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'description' => 'nullable|string|max:500',
        ]);

        $driver = auth()->user()->driver;

        // Upload receipt image
        $receiptPath = $request->file('receipt_image')->store('wallet-receipts', 'public');

        WalletFundingRequest::create([
            'driver_id' => $driver->id,
            'amount' => $request->amount,
            'receipt_image' => $receiptPath,
            'description' => $request->description,
            'status' => WalletFundingRequest::STATUS_PENDING,
        ]);

        return redirect()->route('driver.wallet-funding.index')
            ->with('success', 'Wallet funding request submitted successfully! Waiting for admin approval.');
    }

    public function show(WalletFundingRequest $walletFundingRequest)
    {
        // Ensure driver can only view their own requests
        if ($walletFundingRequest->driver_id !== auth()->user()->driver->id) {
            abort(403, 'Unauthorized action.');
        }

        $walletFundingRequest->load('approver');
        
        return view('driver.wallet-funding.show', compact('walletFundingRequest'));
    }
}
