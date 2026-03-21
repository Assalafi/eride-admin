<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingRequest;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ChargingOperatorController extends Controller
{
    /**
     * Get all approved charging requests for operator's branch
     */
    public function getApprovedRequests(Request $request)
    {
        Log::info('=== GET APPROVED REQUESTS START ===');
        
        $user = Auth::user();
        Log::info('User authenticated', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
        ]);

        // Check if user is Charging Station Operator
        if ($user->role !== 'Charging Station Operator') {
            Log::warning('Unauthorized access attempt', ['user_role' => $user->role]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Charging Station Operators can access this endpoint.'
            ], 403);
        }

        // Get approved and in_progress requests from operator's branch
        Log::info('Fetching charging requests for branch', ['branch_id' => $user->branch_id]);
        
        $requests = ChargingRequest::with(['driver.branch', 'vehicle', 'approver'])
            ->where(function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->whereIn('status', [ChargingRequest::STATUS_APPROVED, ChargingRequest::STATUS_IN_PROGRESS])
            ->orderBy('approved_at', 'desc')
            ->get()
            ->map(function ($charging) {
                return [
                    'id' => $charging->id,
                    'status' => $charging->status,
                    'driver' => [
                        'id' => $charging->driver->id,
                        'name' => $charging->driver->full_name,
                        'phone' => $charging->driver->phone_number,
                    ],
                    'vehicle' => [
                        'id' => $charging->vehicle->id,
                        'plate_number' => $charging->vehicle->plate_number,
                        'make' => $charging->vehicle->make,
                        'model' => $charging->vehicle->model,
                    ],
                    'location' => $charging->driver->branch->name ?? ($charging->location ?? 'N/A'),
                    'charging_cost' => (float) $charging->charging_cost,
                    'battery_level_before' => $charging->battery_level_before ? (float) $charging->battery_level_before : null,
                    'battery_level_after' => $charging->battery_level_after ? (float) $charging->battery_level_after : null,
                    'energy_consumed' => $charging->energy_consumed ? (float) $charging->energy_consumed : null,
                    'charging_start' => $charging->charging_start ? $charging->charging_start->toISOString() : null,
                    'charging_end' => $charging->charging_end ? $charging->charging_end->toISOString() : null,
                    'duration_minutes' => $charging->duration_minutes,
                    'approved_by' => $charging->approver ? $charging->approver->name : null,
                    'approved_at' => $charging->approved_at ? $charging->approved_at->toISOString() : null,
                    'created_at' => $charging->created_at->toISOString(),
                    'payment_receipt' => $charging->payment_receipt ? asset('storage/' . $charging->payment_receipt) : null,
                ];
            });

        Log::info('Charging requests retrieved', ['count' => $requests->count()]);
        
        return response()->json([
            'success' => true,
            'message' => 'Charging requests retrieved successfully',
            'data' => $requests
        ]);
    }

    /**
     * Start a charging session
     */
    public function startCharging(Request $request, $id)
    {
        Log::info('=== START CHARGING REQUEST ===', ['charging_request_id' => $id]);
        
        $user = Auth::user();
        Log::info('User attempting to start charging', [
            'user_id' => $user->id,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
        ]);

        // Check if user is Charging Station Operator
        if ($user->role !== 'Charging Station Operator') {
            Log::warning('Unauthorized start charging attempt', ['user_role' => $user->role]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Charging Station Operators can start charging.'
            ], 403);
        }

        $chargingRequest = ChargingRequest::with(['driver', 'vehicle'])->find($id);
        
        Log::info('Charging request lookup', [
            'found' => $chargingRequest ? 'yes' : 'no',
            'id' => $id
        ]);

        if (!$chargingRequest) {
            Log::error('Charging request not found', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Charging request not found.'
            ], 404);
        }

        // Check branch access
        Log::info('Checking branch access', [
            'driver_branch' => $chargingRequest->driver->branch_id,
            'user_branch' => $user->branch_id
        ]);
        
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            Log::warning('Branch mismatch', [
                'driver_branch' => $chargingRequest->driver->branch_id,
                'user_branch' => $user->branch_id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'You can only start charging for requests from your branch.'
            ], 403);
        }

        // Can only start if status is approved
        Log::info('Checking request status', ['status' => $chargingRequest->status]);
        
        if ($chargingRequest->status !== ChargingRequest::STATUS_APPROVED) {
            Log::error('Invalid status for starting', [
                'current_status' => $chargingRequest->status,
                'required_status' => ChargingRequest::STATUS_APPROVED
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Can only start charging for approved requests. Current status: ' . $chargingRequest->status
            ], 400);
        }

        $chargingRequest->update([
            'status' => ChargingRequest::STATUS_IN_PROGRESS,
            'charging_start' => now(),
        ]);
        
        Log::info('Charging session started successfully', [
            'charging_request_id' => $chargingRequest->id,
            'charging_start' => $chargingRequest->charging_start
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Charging session started successfully!',
            'data' => [
                'id' => $chargingRequest->id,
                'status' => $chargingRequest->status,
                'charging_start' => $chargingRequest->charging_start->toISOString(),
                'driver' => [
                    'name' => $chargingRequest->driver->full_name,
                ],
                'vehicle' => [
                    'plate_number' => $chargingRequest->vehicle->plate_number,
                ],
            ]
        ]);
    }

    /**
     * Complete/End a charging session
     */
    public function completeCharging(Request $request, $id)
    {
        Log::info('=== COMPLETE CHARGING REQUEST ===', [
            'charging_request_id' => $id,
            'input' => $request->all()
        ]);
        
        $user = Auth::user();
        Log::info('User attempting to complete charging', [
            'user_id' => $user->id,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
        ]);

        // Check if user is Charging Station Operator
        if ($user->role !== 'Charging Station Operator') {
            Log::warning('Unauthorized complete charging attempt', ['user_role' => $user->role]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Charging Station Operators can complete charging.'
            ], 403);
        }

        $chargingRequest = ChargingRequest::with(['driver', 'vehicle'])->find($id);
        
        Log::info('Charging request lookup for completion', [
            'found' => $chargingRequest ? 'yes' : 'no',
            'id' => $id
        ]);

        if (!$chargingRequest) {
            Log::error('Charging request not found for completion', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Charging request not found.'
            ], 404);
        }

        // Check branch access
        Log::info('Checking branch access for completion', [
            'driver_branch' => $chargingRequest->driver->branch_id,
            'user_branch' => $user->branch_id
        ]);
        
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            Log::warning('Branch mismatch for completion', [
                'driver_branch' => $chargingRequest->driver->branch_id,
                'user_branch' => $user->branch_id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'You can only complete charging for requests from your branch.'
            ], 403);
        }

        // Can only complete if status is in_progress
        Log::info('Checking status for completion', ['status' => $chargingRequest->status]);
        
        if ($chargingRequest->status !== ChargingRequest::STATUS_IN_PROGRESS) {
            Log::error('Invalid status for completion', [
                'current_status' => $chargingRequest->status,
                'required_status' => ChargingRequest::STATUS_IN_PROGRESS
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Can only complete charging requests that are in progress. Current status: ' . $chargingRequest->status
            ], 400);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'battery_level_after' => 'required|numeric|min:0|max:100',
            'energy_consumed' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Calculate duration
        $durationMinutes = $chargingRequest->charging_start 
            ? $chargingRequest->charging_start->diffInMinutes(now()) 
            : null;

        // Update charging request
        $chargingRequest->update([
            'status' => ChargingRequest::STATUS_COMPLETED,
            'charging_end' => now(),
            'battery_level_after' => $request->battery_level_after,
            'energy_consumed' => $request->energy_consumed,
            'duration_minutes' => $durationMinutes,
        ]);
        
        Log::info('Charging session completed successfully', [
            'charging_request_id' => $chargingRequest->id,
            'duration_minutes' => $durationMinutes,
            'battery_level_after' => $request->battery_level_after
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Charging session completed successfully!',
            'data' => [
                'id' => $chargingRequest->id,
                'status' => $chargingRequest->status,
                'charging_start' => $chargingRequest->charging_start->toISOString(),
                'charging_end' => $chargingRequest->charging_end->toISOString(),
                'duration_minutes' => $chargingRequest->duration_minutes,
                'battery_level_before' => $chargingRequest->battery_level_before ? (float) $chargingRequest->battery_level_before : null,
                'battery_level_after' => (float) $chargingRequest->battery_level_after,
                'energy_consumed' => $chargingRequest->energy_consumed ? (float) $chargingRequest->energy_consumed : null,
                'charging_cost' => (float) $chargingRequest->charging_cost,
                'driver' => [
                    'name' => $chargingRequest->driver->full_name,
                ],
                'vehicle' => [
                    'plate_number' => $chargingRequest->vehicle->plate_number,
                ],
            ]
        ]);
    }

    /**
     * Get single charging request details
     */
    public function getChargingDetails($id)
    {
        Log::info('=== GET CHARGING DETAILS ===', ['charging_request_id' => $id]);
        
        $user = Auth::user();
        Log::info('User requesting charging details', [
            'user_id' => $user->id,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
        ]);

        // Check if user is Charging Station Operator
        if ($user->role !== 'Charging Station Operator') {
            Log::warning('Unauthorized details access attempt', ['user_role' => $user->role]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Charging Station Operators can access this endpoint.'
            ], 403);
        }

        $chargingRequest = ChargingRequest::with(['driver', 'vehicle', 'approver'])->find($id);
        
        Log::info('Charging request details lookup', [
            'found' => $chargingRequest ? 'yes' : 'no',
            'id' => $id
        ]);

        if (!$chargingRequest) {
            Log::error('Charging request not found for details', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Charging request not found.'
            ], 404);
        }

        // Check branch access
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only view charging requests from your branch.'
            ], 403);
        }

        Log::info('Returning charging request details', ['id' => $chargingRequest->id]);
        
        return response()->json([
            'success' => true,
            'message' => 'Charging request details retrieved successfully',
            'data' => [
                'id' => $chargingRequest->id,
                'status' => $chargingRequest->status,
                'driver' => [
                    'id' => $chargingRequest->driver->id,
                    'name' => $chargingRequest->driver->full_name,
                    'phone' => $chargingRequest->driver->phone_number,
                    'email' => $chargingRequest->driver->email,
                ],
                'vehicle' => [
                    'id' => $chargingRequest->vehicle->id,
                    'plate_number' => $chargingRequest->vehicle->plate_number,
                    'make' => $chargingRequest->vehicle->make,
                    'model' => $chargingRequest->vehicle->model,
                    'year' => $chargingRequest->vehicle->year,
                ],
                'location' => $chargingRequest->driver->branch->name ?? ($chargingRequest->location ?? 'N/A'),
                'charging_cost' => (float) $chargingRequest->charging_cost,
                'battery_level_before' => $chargingRequest->battery_level_before ? (float) $chargingRequest->battery_level_before : null,
                'battery_level_after' => $chargingRequest->battery_level_after ? (float) $chargingRequest->battery_level_after : null,
                'energy_consumed' => $chargingRequest->energy_consumed ? (float) $chargingRequest->energy_consumed : null,
                'charging_start' => $chargingRequest->charging_start ? $chargingRequest->charging_start->toISOString() : null,
                'charging_end' => $chargingRequest->charging_end ? $chargingRequest->charging_end->toISOString() : null,
                'duration_minutes' => $chargingRequest->duration_minutes,
                'approved_by' => $chargingRequest->approver ? $chargingRequest->approver->name : null,
                'approved_at' => $chargingRequest->approved_at ? $chargingRequest->approved_at->toISOString() : null,
                'payment_receipt' => $chargingRequest->payment_receipt ? asset('storage/' . $chargingRequest->payment_receipt) : null,
                'notes' => $chargingRequest->notes,
                'created_at' => $chargingRequest->created_at->toISOString(),
                'updated_at' => $chargingRequest->updated_at->toISOString(),
            ]
        ]);
    }
    
    /**
     * Get charging history with pagination and filters
     */
    public function getHistory(Request $request)
    {
        Log::info('=== GET CHARGING HISTORY ===', [
            'filters' => $request->all()
        ]);
        
        $user = Auth::user();
        Log::info('User requesting history', [
            'user_id' => $user->id,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
        ]);

        // Check if user is Charging Station Operator
        if ($user->role !== 'Charging Station Operator') {
            Log::warning('Unauthorized history access attempt', ['user_role' => $user->role]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Charging Station Operators can access this endpoint.'
            ], 403);
        }

        $perPage = $request->input('per_page', 10);
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        Log::info('History filters', [
            'per_page' => $perPage,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        // Build query
        $query = ChargingRequest::with(['driver.branch', 'vehicle', 'approver'])
            ->where(function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->whereIn('status', [ChargingRequest::STATUS_COMPLETED, ChargingRequest::STATUS_CANCELLED]);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Paginate
        $requests = $query->orderBy('updated_at', 'desc')
            ->paginate($perPage)
            ->through(function ($charging) {
                return [
                    'id' => $charging->id,
                    'status' => $charging->status,
                    'driver' => [
                        'id' => $charging->driver->id,
                        'name' => $charging->driver->full_name,
                        'phone' => $charging->driver->phone_number,
                    ],
                    'vehicle' => [
                        'id' => $charging->vehicle->id,
                        'plate_number' => $charging->vehicle->plate_number,
                        'make' => $charging->vehicle->make,
                        'model' => $charging->vehicle->model,
                    ],
                    'location' => $charging->driver->branch->name ?? ($charging->location ?? 'N/A'),
                    'charging_cost' => (float) $charging->charging_cost,
                    'battery_level_before' => $charging->battery_level_before ? (float) $charging->battery_level_before : null,
                    'battery_level_after' => $charging->battery_level_after ? (float) $charging->battery_level_after : null,
                    'energy_consumed' => $charging->energy_consumed ? (float) $charging->energy_consumed : null,
                    'charging_start' => $charging->charging_start ? $charging->charging_start->toISOString() : null,
                    'charging_end' => $charging->charging_end ? $charging->charging_end->toISOString() : null,
                    'duration_minutes' => $charging->duration_minutes,
                    'approved_by' => $charging->approver ? $charging->approver->name : null,
                    'approved_at' => $charging->approved_at ? $charging->approved_at->toISOString() : null,
                    'created_at' => $charging->created_at->toISOString(),
                    'payment_receipt' => $charging->payment_receipt ? asset('storage/' . $charging->payment_receipt) : null,
                ];
            });

        Log::info('History retrieved', [
            'total' => $requests->total(),
            'per_page' => $requests->perPage(),
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Charging history retrieved successfully',
            'data' => $requests->items(),
            'current_page' => $requests->currentPage(),
            'total_pages' => $requests->lastPage(),
            'per_page' => $requests->perPage(),
            'total' => $requests->total(),
        ]);
    }
}
