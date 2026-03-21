<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'branch'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'Branch Manager', 'Accountant', 'Driver', 'Mechanic', 'Storekeeper', 'Charging Station Operator']);
            })
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::whereIn('name', ['Super Admin', 'Branch Manager', 'Accountant', 'Driver', 'Mechanic', 'Storekeeper', 'Charging Station Operator'])->get();
        $branches = Branch::all();
        
        return view('admin.users.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ];

        // Branch is NOT required for Super Admin and Accountant (company-level roles)
        if (!in_array($request->role, ['Super Admin', 'Accountant'])) {
            if ($request->role === 'Branch Manager') {
                $validationRules['branches'] = 'required|array|min:1';
                $validationRules['branches.*'] = 'exists:branches,id';
                $validationRules['primary_branch'] = 'required|exists:branches,id';
            } else {
                $validationRules['branch_id'] = 'required|exists:branches,id';
            }
        }

        $request->validate($validationRules);

        // Create user
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'branch_id' => in_array($request->role, ['Super Admin', 'Accountant']) ? null : 
                           ($request->role === 'Branch Manager' ? $request->primary_branch : $request->branch_id),
        ];

        $user = User::create($userData);
        $user->assignRole($request->role);

        // Handle branch assignments for Branch Manager
        if ($request->role === 'Branch Manager' && isset($request->branches)) {
            $branchAssignments = [];
            foreach ($request->branches as $branchId) {
                $branchAssignments[$branchId] = [
                    'is_primary' => $branchId == $request->primary_branch,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $user->branches()->attach($branchAssignments);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        $user->load(['roles', 'branch', 'branches']);
        
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::whereIn('name', ['Super Admin', 'Branch Manager', 'Accountant', 'Driver', 'Mechanic', 'Storekeeper', 'Charging Station Operator'])->get();
        $branches = Branch::all();
        
        return view('admin.users.edit', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name',
        ];

        // Branch is NOT required for Super Admin and Accountant (company-level roles)
        if (!in_array($request->role, ['Super Admin', 'Accountant'])) {
            $validationRules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($validationRules);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'branch_id' => in_array($request->role, ['Super Admin', 'Accountant']) ? null : $request->branch_id,
        ]);

        // Update password if provided
        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // Sync role
        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account!');
        }

        // Prevent deleting if user has driver record
        if ($user->driver) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot delete user with a driver record!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }
}
