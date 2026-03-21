<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['drivers', 'vehicles', 'users'])
            ->paginate(15);

        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
            'location' => 'required|string|max:255',
        ]);

        Branch::create($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch created successfully!');
    }

    public function show(Branch $branch)
    {
        $branch->load(['drivers.wallet', 'vehicles.currentAssignment', 'users.roles']);

        return view('admin.branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'location' => 'required|string|max:255',
        ]);

        $branch->update($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch updated successfully!');
    }

    public function destroy(Branch $branch)
    {
        // Check if branch has any drivers or vehicles
        if ($branch->drivers()->count() > 0 || $branch->vehicles()->count() > 0) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'Cannot delete branch with existing drivers or vehicles!');
        }

        $branch->delete();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch deleted successfully!');
    }
}
