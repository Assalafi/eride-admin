<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        $permissionGroups = $permissions->groupBy(function ($permission) {
            return explode(' ', $permission->name)[0];
        });

        return view('admin.roles.create', compact('permissions', 'permissionGroups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
            ]);

            if ($request->has('permissions')) {
                $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
                $role->syncPermissions($permissionNames);
            }

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);
        
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        \Log::info('=== ROLE EDIT VIEW ===');
        \Log::info('Editing Role ID: ' . $role->id);
        \Log::info('Editing Role Name: ' . $role->name);
        
        $permissions = Permission::orderBy('name')->get();
        \Log::info('Total permissions available: ' . $permissions->count());
        
        $permissionGroups = $permissions->groupBy(function ($permission) {
            return explode(' ', $permission->name)[0];
        });
        \Log::info('Permission groups: ' . json_encode($permissionGroups->keys()->toArray()));

        $rolePermissions = $role->permissions->pluck('id')->toArray();
        \Log::info('Current role permissions count: ' . count($rolePermissions));
        \Log::info('Current role permission IDs: ' . json_encode($rolePermissions));

        return view('admin.roles.edit', compact('role', 'permissions', 'permissionGroups', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        \Log::info('=== ROLE UPDATE START ===');
        \Log::info('Role ID: ' . $role->id);
        \Log::info('Role Name: ' . $role->name);
        \Log::info('Request Data: ' . json_encode($request->all()));
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        \Log::info('Validation passed');

        // Prevent editing core system roles
        if (in_array($role->name, ['Super Admin'])) {
            \Log::info('Attempted to edit Super Admin role - blocked');
            return redirect()->back()
                ->with('error', 'Cannot edit Super Admin role for security reasons.');
        }

        DB::beginTransaction();
        try {
            \Log::info('Starting database transaction');
            
            $role->update([
                'name' => $request->name,
            ]);
            
            \Log::info('Role name updated to: ' . $request->name);

            if ($request->has('permissions')) {
                \Log::info('Permissions array exists: ' . json_encode($request->permissions));
                \Log::info('Permissions count: ' . count($request->permissions));
                
                $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
                \Log::info('Permission names to sync: ' . json_encode($permissionNames));
                
                $role->syncPermissions($permissionNames);
                \Log::info('Permissions synced successfully');
            } else {
                \Log::info('No permissions array - clearing all permissions');
                $role->syncPermissions([]);
                \Log::info('All permissions cleared');
            }

            DB::commit();
            \Log::info('Database transaction committed');

            \Log::info('=== ROLE UPDATE SUCCESS ===');
            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Role update failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error updating role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting core system roles
        if (in_array($role->name, ['Super Admin'])) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete Super Admin role for security reasons.');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete role with assigned users. Please reassign users first.');
        }

        try {
            $role->delete();
            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Error deleting role: ' . $e->getMessage());
        }
    }

    /**
     * Show users with this role
     */
    public function users(Role $role)
    {
        $users = $role->users()->with('branch')->paginate(20);
        
        return view('admin.roles.users', compact('role', 'users'));
    }

    /**
     * Get role statistics
     */
    public function stats()
    {
        $roles = Role::withCount('users')->get();
        $totalRoles = $roles->count();
        $totalUsers = $roles->sum('users_count');
        
        return response()->json([
            'total_roles' => $totalRoles,
            'total_users' => $totalUsers,
            'roles' => $roles,
        ]);
    }
}
