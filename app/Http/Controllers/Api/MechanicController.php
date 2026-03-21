<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\MaintenanceRequest;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MechanicController extends Controller
{
    /**
     * Get driver wallet balance
     */
    public function getDriverWallet(Request $request, $driverId)
    {
        $user = auth()->user();
        
        Log::info('=== GET DRIVER WALLET ===', [
            'user_id' => $user->id,
            'driver_id' => $driverId
        ]);

        if ($user->role !== 'Mechanic') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Mechanics can access this endpoint.'
            ], 403);
        }

        $driver = Driver::with('wallet')->find($driverId);

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found'
            ], 404);
        }

        // Check if driver is in same branch
        if (!BranchAccessService::canAccessBranch($user, $driver->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only access drivers in your branch.'
            ], 403);
        }

        Log::info('Driver wallet retrieved', [
            'driver_id' => $driverId,
            'balance' => $driver->wallet->balance ?? 0
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'driver_id' => $driver->id,
                'driver_name' => $driver->full_name,
                'balance' => (float)($driver->wallet->balance ?? 0),
            ]
        ]);
    }

    /**
     * Get active vehicles with their assigned drivers in mechanic's branch
     */
    public function getActiveVehicles(Request $request)
    {
        $user = auth()->user();
        
        Log::info('=== GET ACTIVE VEHICLES ===');
        Log::info('User requesting active vehicles', [
            'user_id' => $user->id,
            'role' => $user->role,
            'branch_id' => $user->branch_id
        ]);

        // Check if user is a mechanic
        if ($user->role !== 'Mechanic') {
            Log::warning('Unauthorized access attempt', ['user_role' => $user->role]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Mechanics can access this endpoint.'
            ], 403);
        }

        // Get active vehicle assignments in mechanic's branch
        $activeAssignments = VehicleAssignment::with(['vehicle.branch', 'driver.branch'])
            ->whereNull('returned_at')
            ->where(function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->get()
            ->map(function ($assignment) {
                return [
                    'assignment_id' => $assignment->id,
                    'vehicle' => [
                        'id' => $assignment->vehicle->id,
                        'plate_number' => $assignment->vehicle->plate_number,
                        'make' => $assignment->vehicle->make,
                        'model' => $assignment->vehicle->model,
                        'full_name' => $assignment->vehicle->make . ' ' . $assignment->vehicle->model,
                    ],
                    'driver' => [
                        'id' => $assignment->driver->id,
                        'name' => $assignment->driver->full_name,
                        'phone' => $assignment->driver->phone_number,
                        'email' => $assignment->driver->user->email ?? null,
                    ],
                    'assigned_at' => $assignment->assigned_at->toISOString(),
                ];
            });

        Log::info('Active vehicles retrieved', ['count' => $activeAssignments->count()]);

        return response()->json([
            'success' => true,
            'message' => 'Active vehicles retrieved successfully',
            'data' => $activeAssignments
        ]);
    }

    /**
     * Get available parts for mechanic's branch
     */
    public function getAvailableParts(Request $request)
    {
        $user = auth()->user();
        
        Log::info('=== GET AVAILABLE PARTS ===', ['user_id' => $user->id]);

        if ($user->role !== 'Mechanic') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $parts = Part::where(function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->get()
            ->map(function ($part) {
                return [
                    'id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku,
                    'description' => $part->description,
                    'cost' => (float) $part->cost,
                    'picture' => $part->picture ? asset('storage/' . $part->picture) : null,
                ];
            });

        Log::info('Parts retrieved', ['count' => $parts->count()]);

        return response()->json([
            'success' => true,
            'message' => 'Parts retrieved successfully',
            'data' => $parts
        ]);
    }

    /**
     * Create a maintenance request
     */
    public function createMaintenanceRequest(Request $request)
    {
        $user = auth()->user();
        
        Log::info('=== CREATE MAINTENANCE REQUEST ===', [
            'user_id' => $user->id,
            'driver_id' => $request->driver_id
        ]);

        if ($user->role !== 'Mechanic') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'issue_description' => 'nullable|string',
            'issue_photos' => 'nullable|array',
            'issue_photos.*' => 'nullable|string', // Base64 encoded images
            'parts' => 'required|array|min:1',
            'parts.*.part_id' => 'required|exists:parts,id',
            'parts.*.quantity' => 'required|integer|min:1',
        ]);

        // Verify driver is in mechanic's branch
        $driver = Driver::find($validated['driver_id']);
        if (!BranchAccessService::canAccessBranch($user, $driver->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not in your branch.'
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            // Handle photo uploads
            $photosPaths = [];
            if (!empty($validated['issue_photos'])) {
                foreach ($validated['issue_photos'] as $index => $base64Image) {
                    if (!empty($base64Image)) {
                        // Decode base64 image
                        $image = base64_decode($base64Image);
                        $filename = 'maintenance_' . time() . '_' . $index . '.jpg';
                        $path = 'maintenance_photos/' . $filename;
                        
                        Storage::disk('public')->put($path, $image);
                        $photosPaths[] = $path;
                    }
                }
            }

            // Create maintenance request
            $maintenanceRequest = MaintenanceRequest::create([
                'driver_id' => $validated['driver_id'],
                'mechanic_id' => $user->id,
                'issue_description' => $validated['issue_description'] ?? null,
                'issue_photos' => !empty($photosPaths) ? json_encode($photosPaths) : null,
                'status' => 'pending_manager_approval',
            ]);

            // Attach parts with costs
            foreach ($validated['parts'] as $partData) {
                $part = Part::find($partData['part_id']);
                $quantity = (int) $partData['quantity'];
                $unitCost = (float) $part->cost;
                $totalCost = $unitCost * $quantity;

                $maintenanceRequest->parts()->attach($partData['part_id'], [
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ]);

                Log::info('Part attached', [
                    'part_id' => $partData['part_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ]);
            }

            DB::commit();

            Log::info('Maintenance request created successfully', [
                'request_id' => $maintenanceRequest->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance request created successfully',
                'data' => [
                    'id' => $maintenanceRequest->id,
                    'status' => $maintenanceRequest->status,
                    'created_at' => $maintenanceRequest->created_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create maintenance request', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create maintenance request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get maintenance requests created by mechanic
     */
    public function getMyRequests(Request $request)
    {
        $user = auth()->user();
        
        Log::info('=== GET MY MAINTENANCE REQUESTS ===', ['user_id' => $user->id]);

        if ($user->role !== 'Mechanic') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $status = $request->get('status');
        $perPage = $request->get('per_page', 10);

        $query = MaintenanceRequest::with(['driver', 'parts'])
            ->where('mechanic_id', $user->id);

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->latest()
            ->paginate($perPage)
            ->through(function ($maintenanceRequest) {
                // Get vehicle through driver's current assignment
                $vehicle = $maintenanceRequest->driver->vehicleAssignments()
                    ->with('vehicle')
                    ->whereNull('returned_at')
                    ->latest()
                    ->first()?->vehicle;

                return [
                    'id' => $maintenanceRequest->id,
                    'status' => $maintenanceRequest->status,
                    'driver' => [
                        'id' => $maintenanceRequest->driver->id,
                        'name' => $maintenanceRequest->driver->full_name,
                        'phone' => $maintenanceRequest->driver->phone_number,
                    ],
                    'vehicle' => $vehicle ? [
                        'id' => $vehicle->id,
                        'plate_number' => $vehicle->plate_number,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                    ] : null,
                    'total_cost' => (float) $maintenanceRequest->total_cost,
                    'parts_count' => $maintenanceRequest->parts->count(),
                    'created_at' => $maintenanceRequest->created_at->toISOString(),
                    'updated_at' => $maintenanceRequest->updated_at->toISOString(),
                ];
            });

        Log::info('Maintenance requests retrieved', [
            'count' => $requests->count(),
            'total' => $requests->total()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance requests retrieved successfully',
            'data' => $requests->items(),
            'current_page' => $requests->currentPage(),
            'total_pages' => $requests->lastPage(),
            'total' => $requests->total(),
        ]);
    }

    /**
     * Get maintenance request details
     */
    public function getRequestDetails($id)
    {
        $user = auth()->user();
        
        Log::info('=== GET MAINTENANCE REQUEST DETAILS ===', [
            'request_id' => $id,
            'user_id' => $user->id
        ]);

        if ($user->role !== 'Mechanic') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $maintenanceRequest = MaintenanceRequest::with(['driver', 'mechanic', 'approver', 'parts'])
            ->where('mechanic_id', $user->id)
            ->find($id);

        if (!$maintenanceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance request not found.'
            ], 404);
        }

        // Get vehicle
        $vehicle = $maintenanceRequest->driver->vehicleAssignments()
            ->with('vehicle')
            ->whereNull('returned_at')
            ->latest()
            ->first()?->vehicle;

        // Parse issue photos
        $issuePhotos = [];
        if ($maintenanceRequest->issue_photos) {
            $photos = json_decode($maintenanceRequest->issue_photos, true);
            if (is_array($photos)) {
                $issuePhotos = array_map(function ($photo) {
                    return asset('storage/' . $photo);
                }, $photos);
            }
        }

        $data = [
            'id' => $maintenanceRequest->id,
            'status' => $maintenanceRequest->status,
            'issue_description' => $maintenanceRequest->issue_description,
            'issue_photos' => $issuePhotos,
            'manager_notes' => $maintenanceRequest->manager_notes,
            'driver' => [
                'id' => $maintenanceRequest->driver->id,
                'name' => $maintenanceRequest->driver->full_name,
                'phone' => $maintenanceRequest->driver->phone_number,
                'email' => $maintenanceRequest->driver->user->email ?? null,
            ],
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'full_name' => $vehicle->make . ' ' . $vehicle->model,
            ] : null,
            'mechanic' => [
                'id' => $maintenanceRequest->mechanic->id,
                'name' => $maintenanceRequest->mechanic->name,
            ],
            'approver' => $maintenanceRequest->approver ? [
                'id' => $maintenanceRequest->approver->id,
                'name' => $maintenanceRequest->approver->name,
            ] : null,
            'parts' => $maintenanceRequest->parts->map(function ($part) {
                return [
                    'id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku,
                    'quantity' => $part->pivot->quantity,
                    'unit_cost' => (float) $part->pivot->unit_cost,
                    'total_cost' => (float) $part->pivot->total_cost,
                    'picture' => $part->picture ? asset('storage/' . $part->picture) : null,
                ];
            }),
            'total_cost' => (float) $maintenanceRequest->total_cost,
            'created_at' => $maintenanceRequest->created_at->toISOString(),
            'updated_at' => $maintenanceRequest->updated_at->toISOString(),
            'completed_at' => $maintenanceRequest->completed_at?->toISOString(),
        ];

        Log::info('Request details retrieved', ['request_id' => $id]);

        return response()->json([
            'success' => true,
            'message' => 'Request details retrieved successfully',
            'data' => $data
        ]);
    }

    /**
     * Complete maintenance request (mechanic confirms parts received and work done)
     */
    public function completeMaintenanceRequest($id)
    {
        $user = auth()->user();
        
        Log::info('=== COMPLETE MAINTENANCE REQUEST ===', [
            'request_id' => $id,
            'user_id' => $user->id
        ]);

        if ($user->role !== 'Mechanic') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Mechanics can complete requests.'
            ], 403);
        }

        $maintenanceRequest = MaintenanceRequest::with(['driver', 'parts'])
            ->where('mechanic_id', $user->id)
            ->find($id);

        if (!$maintenanceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance request not found or you do not have permission.'
            ], 404);
        }

        // Check if request is in the correct status
        if ($maintenanceRequest->status !== 'pending_store_approval') {
            return response()->json([
                'success' => false,
                'message' => "Cannot complete request. Current status: {$maintenanceRequest->status}"
            ], 400);
        }

        // Update request status
        $maintenanceRequest->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Log::info('Maintenance request completed', [
            'request_id' => $id,
            'mechanic_id' => $user->id,
            'driver_id' => $maintenanceRequest->driver_id,
        ]);

        // Fire event to update inventory
        event(new \App\Events\MaintenanceCompleted($maintenanceRequest));

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request completed successfully!'
        ]);
    }
}
