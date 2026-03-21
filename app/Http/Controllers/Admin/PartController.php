<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PartStock;
use App\Models\PartStockHistory;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PartController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $branchIds = BranchAccessService::getUserBranchIds($user);
        $parts = Part::with(['branch', 'stock' => function ($query) use ($user, $branchIds) {
            if (!$user->hasRole(['Super Admin', 'Accountant'])) {
                $query->whereIn('branch_id', $branchIds);
            }
        }])->paginate(20);

        // Calculate statistics
        $totalParts = Part::count();
        
        // For branch-specific stats
        $stockQuery = PartStock::query();
        if (!$user->hasRole(['Super Admin', 'Accountant'])) {
            $stockQuery->whereIn('branch_id', $branchIds);
        }
        
        $inStockParts = (clone $stockQuery)->where('quantity', '>=', 3)->count();
        $lowStockParts = (clone $stockQuery)->whereBetween('quantity', [1, 2])->count();
        $outOfStockParts = Part::whereDoesntHave('stock', function ($query) use ($user, $branchIds) {
            if (!$user->hasRole(['Super Admin', 'Accountant'])) {
                $query->whereIn('branch_id', $branchIds)->where('quantity', '>', 0);
            } else {
                $query->where('quantity', '>', 0);
            }
        })->count();

        return view('admin.parts.index', compact('parts', 'totalParts', 'inStockParts', 'lowStockParts', 'outOfStockParts'));
    }

    public function create()
    {
        $branches = Branch::all();
        return view('admin.parts.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:parts,sku',
            'picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'initial_quantity' => 'required|integer|min:0',
        ]);

        $picturePath = null;
        if ($request->hasFile('picture')) {
            $picturePath = $request->file('picture')->store('parts', 'public');
        }

        $part = Part::create([
            'branch_id' => $request->branch_id,
            'name' => $request->name,
            'sku' => strtoupper($request->sku),
            'picture' => $picturePath,
            'description' => $request->description,
            'cost' => $request->cost,
        ]);

        // Create initial stock for the branch
        if ($request->initial_quantity > 0) {
            PartStock::create([
                'part_id' => $part->id,
                'branch_id' => $request->branch_id,
                'quantity' => $request->initial_quantity,
            ]);
        }

        return redirect()->route('admin.parts.index')
            ->with('success', 'Part created successfully with initial stock!');
    }

    public function show(Part $part)
    {
        $user = auth()->user();
        
        // Check if user can access this part
        if (!BranchAccessService::canAccessBranch($user, $part->branch_id)) {
            abort(403, 'You do not have permission to access this part.');
        }
        
        $part->load(['branch', 'stock.branch', 'maintenanceRequests.driver', 'maintenanceRequests.approver']);
        
        // Get all stock across branches
        $allStock = $part->stock;
        
        // Calculate total stock
        $totalStock = $allStock->sum('quantity');
        
        // Get maintenance history
        $maintenanceHistory = $part->maintenanceRequests()
            ->with(['driver', 'approver'])
            ->where('status', 'completed')
            ->latest()
            ->limit(10)
            ->get();
        
        // Calculate total parts used
        $totalUsed = $part->maintenanceRequests()
            ->where('status', 'completed')
            ->sum('maintenance_request_parts.quantity');
        
        // Get stock history
        $stockHistory = PartStockHistory::where('part_id', $part->id)
            ->with(['branch', 'user'])
            ->latest()
            ->paginate(20);
        
        return view('admin.parts.show', compact('part', 'allStock', 'totalStock', 'maintenanceHistory', 'totalUsed', 'stockHistory'));
    }

    public function edit(Part $part)
    {
        $user = auth()->user();
        
        // Check if user can edit this part
        if (!BranchAccessService::canAccessBranch($user, $part->branch_id)) {
            abort(403, 'You do not have permission to edit this part.');
        }
        
        $branches = Branch::all();
        return view('admin.parts.edit', compact('part', 'branches'));
    }

    public function update(Request $request, Part $part)
    {
        $user = auth()->user();
        
        // Check if user can update this part
        if (!BranchAccessService::canAccessBranch($user, $part->branch_id)) {
            abort(403, 'You do not have permission to update this part.');
        }
        
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:parts,sku,' . $part->id,
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
        ]);

        $data = [
            'branch_id' => $request->branch_id,
            'name' => $request->name,
            'sku' => strtoupper($request->sku),
            'description' => $request->description,
            'cost' => $request->cost,
        ];

        // Handle picture upload
        if ($request->hasFile('picture')) {
            // Delete old picture
            if ($part->picture) {
                Storage::disk('public')->delete($part->picture);
            }
            $data['picture'] = $request->file('picture')->store('parts', 'public');
        }

        $part->update($data);

        return redirect()->route('admin.parts.index')
            ->with('success', 'Part updated successfully!');
    }

    public function destroy(Part $part)
    {
        $user = auth()->user();
        
        // Check if user can delete this part
        if (!BranchAccessService::canAccessBranch($user, $part->branch_id)) {
            abort(403, 'You do not have permission to delete this part.');
        }
        
        // Delete picture if exists
        if ($part->picture) {
            Storage::disk('public')->delete($part->picture);
        }
        
        $part->delete();

        return redirect()->route('admin.parts.index')
            ->with('success', 'Part deleted successfully!');
    }

    public function stockIn(Request $request, Part $part)
    {
        $user = auth()->user();
        
        // Check if user can manage stock for this part
        if (!BranchAccessService::canAccessBranch($user, $part->branch_id)) {
            abort(403, 'You do not have permission to manage stock for this part.');
        }
        
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string|max:255',
        ]);

        // Determine target branch
        $branchId = $request->branch_id ?? auth()->user()->branch_id ?? $part->branch_id;

        DB::transaction(function () use ($request, $part, $branchId) {
            $stock = PartStock::firstOrCreate(
                ['part_id' => $part->id, 'branch_id' => $branchId],
                ['quantity' => 0]
            );

            // Record quantity before change
            $quantityBefore = $stock->quantity;

            // Update stock
            $stock->increment('quantity', $request->quantity);

            // Refresh to get updated quantity
            $stock->refresh();

            // Log history
            PartStockHistory::create([
                'part_id' => $part->id,
                'branch_id' => $branchId,
                'type' => PartStockHistory::TYPE_IN,
                'quantity' => $request->quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $stock->quantity,
                'reference' => null,
                'notes' => $request->notes ?? 'Stock added via Stock In',
                'user_id' => auth()->id(),
            ]);
        });

        $branch = Branch::find($branchId);
        return redirect()->back()
            ->with('success', "Stock updated successfully! Added {$request->quantity} units to {$branch->name}.");
    }
}
