<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;

class VehicleAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->get('tab', 'active');
        
        // Base query with branch filter
        $baseQuery = VehicleAssignment::with(['driver.branch', 'vehicle'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            });
        
        // Get summary statistics
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('returned_at')->count(),
            'returned' => (clone $baseQuery)->whereNotNull('returned_at')->count(),
            'today' => (clone $baseQuery)->whereDate('assigned_at', today())->count(),
            'this_week' => (clone $baseQuery)->whereBetween('assigned_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $baseQuery)->whereMonth('assigned_at', now()->month)->whereYear('assigned_at', now()->year)->count(),
        ];
        
        // Filter based on tab
        $assignments = (clone $baseQuery)
            ->when($tab === 'active', function ($query) {
                $query->whereNull('returned_at');
            })
            ->when($tab === 'returned', function ($query) {
                $query->whereNotNull('returned_at');
            })
            ->when($tab === 'today', function ($query) {
                $query->whereDate('assigned_at', today());
            })
            ->when($tab === 'week', function ($query) {
                $query->whereBetween('assigned_at', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->latest('assigned_at')
            ->paginate(20)
            ->appends(['tab' => $tab]);

        return view('admin.assignments.index', compact('assignments', 'stats', 'tab'));
    }

    public function create()
    {
        $user = auth()->user();
        
        // Get drivers with their current assignments
        $drivers = Driver::with(['vehicleAssignments.vehicle', 'branch'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Get vehicles with their current assignments
        $vehicles = Vehicle::with(['assignments' => function ($query) {
                $query->whereNull('returned_at')->with('driver');
            }])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->orderBy('plate_number')
            ->get();

        return view('admin.assignments.create', compact('drivers', 'vehicles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'auto_return_previous' => 'nullable|boolean',
        ]);

        $driver = Driver::findOrFail($request->driver_id);
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        // 1. Check if driver and vehicle are in the same branch
        if ($driver->branch_id !== $vehicle->branch_id) {
            return back()->withErrors([
                'error' => 'Driver and vehicle must be in the same branch. Driver is in ' . 
                          $driver->branch->name . ', Vehicle is in ' . $vehicle->branch->name
            ])->withInput();
        }

        // 2. Check if this vehicle is already assigned to another driver
        $vehicleActiveAssignment = VehicleAssignment::where('vehicle_id', $request->vehicle_id)
            ->whereNull('returned_at')
            ->with('driver')
            ->first();

        if ($vehicleActiveAssignment) {
            return back()->withErrors([
                'vehicle_id' => 'This vehicle (' . $vehicle->plate_number . ') is currently assigned to ' . 
                               $vehicleActiveAssignment->driver->full_name . 
                               '. Please return it first before assigning to another driver.'
            ])->withInput();
        }

        // 3. Check if this driver already has an active vehicle assignment
        $driverActiveAssignment = VehicleAssignment::where('driver_id', $request->driver_id)
            ->whereNull('returned_at')
            ->with('vehicle')
            ->first();

        if ($driverActiveAssignment) {
            // If auto-return is enabled, return the previous vehicle automatically
            if ($request->auto_return_previous) {
                $driverActiveAssignment->update([
                    'returned_at' => now(),
                ]);
                
                $message = 'Previous vehicle (' . $driverActiveAssignment->vehicle->plate_number . 
                          ') automatically returned. New vehicle assigned successfully!';
            } else {
                return back()->withErrors([
                    'driver_id' => 'This driver (' . $driver->full_name . ') currently has vehicle ' . 
                                  $driverActiveAssignment->vehicle->plate_number . ' assigned. ' .
                                  'Please return it first, or enable "Auto-return previous vehicle".'
                ])->withInput();
            }
        }

        // 4. Check vehicle status/availability
        if (isset($vehicle->status) && $vehicle->status === 'maintenance') {
            return back()->withErrors([
                'vehicle_id' => 'This vehicle is currently under maintenance and cannot be assigned.'
            ])->withInput();
        }

        // 5. All checks passed - Create the assignment
        VehicleAssignment::create([
            'driver_id' => $request->driver_id,
            'vehicle_id' => $request->vehicle_id,
            'assigned_at' => now(),
        ]);

        return redirect()->route('admin.assignments.index')
            ->with('success', $message ?? 'Vehicle ' . $vehicle->plate_number . ' assigned to ' . 
                   $driver->full_name . ' successfully!');
    }

    public function return(VehicleAssignment $assignment)
    {
        $user = auth()->user();
        
        // Check if user can return this assignment (check driver's branch)
        if (!BranchAccessService::canAccessBranch($user, $assignment->driver->branch_id)) {
            abort(403, 'You do not have permission to return this assignment.');
        }
        
        if ($assignment->returned_at) {
            return back()->withErrors(['error' => 'This vehicle has already been returned.']);
        }

        $assignment->update([
            'returned_at' => now(),
        ]);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'Vehicle returned successfully!');
    }
}
