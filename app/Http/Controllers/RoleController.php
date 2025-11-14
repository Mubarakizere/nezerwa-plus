<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        // Eager-load users and permissions for drawer preview
        $rolesQuery = Role::with(['users', 'permissions'])
            ->withCount('users');

        if ($query !== '') {
            $rolesQuery->where(function ($qB) use ($query) {
                $qB->where('name', 'like', "%{$query}%")
                    ->orWhereHas('permissions', function ($p) use ($query) {
                        $p->where('name', 'like', "%{$query}%");
                    })
                    ->orWhereHas('users', function ($u) use ($query) {
                        $u->where('name', 'like', "%{$query}%");
                    });
            });
        }

        $roles = $rolesQuery
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('roles.index', [
            'roles' => $roles,
            'query' => $query,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = $this->groupPermissions();

        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create([
                'name'       => strtolower(trim($request->name)),
                'guard_name' => 'web',
            ]);

            if ($request->filled('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $permissions      = $this->groupPermissions();
        $rolePermissions  = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', [
            'role'            => $role,
            'permissions'     => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $role) {
            $role->update([
                'name' => strtolower(trim($request->name)),
            ]);

            $role->syncPermissions($request->permissions ?? []);
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        if (in_array($role->name, ['admin', 'manager'])) {
            return redirect()
                ->back()
                ->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Helper: Group permissions by their first prefix (module name).
     *
     * Example: "users.create", "users.edit" → group "Users"
     */
    private function groupPermissions()
    {
        $permissions = Permission::orderBy('name')->get();

        return $permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return ucfirst($parts[0] ?? 'General');
        });
    }
}
