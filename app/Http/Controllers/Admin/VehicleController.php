<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Branch;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $vehicles = Vehicle::with('branch')
            ->when(!$user->hasRole('Super Admin'), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->paginate(20);

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $branches = \App\Models\Branch::all();
        return view('admin.vehicles.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validationRules = [
            'plate_number' => 'required|string|unique:vehicles,plate_number',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
        ];

        // Super Admin and multi-branch managers must select a branch
        if (auth()->user()->hasRole(['Super Admin', 'Accountant']) || count(BranchAccessService::getUserBranchIds(auth()->user())) > 1) {
            $validationRules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($validationRules);

        // Determine branch_id: from request for Super Admin/multi-branch, from primary branch for others
        $branchId = $request->branch_id ?? auth()->user()->primary_branch_id ?? auth()->user()->branch_id;

        Vehicle::create([
            'branch_id' => $branchId,
            'plate_number' => strtoupper($request->plate_number),
            'make' => $request->make,
            'model' => $request->model,
        ]);

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Vehicle created successfully!');
    }

    public function show(Vehicle $vehicle)
    {
        $user = auth()->user();
        
        // Check if user can access this vehicle
        if (!BranchAccessService::canAccessBranch($user, $vehicle->branch_id)) {
            abort(403, 'You do not have permission to access this vehicle.');
        }
        
        $vehicle->load(['assignments.driver']);
        
        return view('admin.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $user = auth()->user();
        
        // Check if user can edit this vehicle
        if (!BranchAccessService::canAccessBranch($user, $vehicle->branch_id)) {
            abort(403, 'You do not have permission to edit this vehicle.');
        }
        
        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $user = auth()->user();
        
        // Check if user can update this vehicle
        if (!BranchAccessService::canAccessBranch($user, $vehicle->branch_id)) {
            abort(403, 'You do not have permission to update this vehicle.');
        }
        
        $request->validate([
            'plate_number' => 'required|string|unique:vehicles,plate_number,' . $vehicle->id,
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
        ]);

        $vehicle->update([
            'plate_number' => strtoupper($request->plate_number),
            'make' => $request->make,
            'model' => $request->model,
        ]);

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Vehicle updated successfully!');
    }

    public function destroy(Vehicle $vehicle)
    {
        $user = auth()->user();
        
        // Check if user can delete this vehicle
        if (!BranchAccessService::canAccessBranch($user, $vehicle->branch_id)) {
            abort(403, 'You do not have permission to delete this vehicle.');
        }
        
        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Vehicle deleted successfully!');
    }
}
