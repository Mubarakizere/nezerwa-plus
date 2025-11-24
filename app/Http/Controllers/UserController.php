<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles')->latest('id');

        //  Search by name or email
        if ($search = $request->input('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        // Filter by role
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::pluck('name')->toArray();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::pluck('name')->toArray();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|min:6|confirmed',
            'role'           => 'required|string|exists:roles,name',
            'profile_image'  => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'email']);
        $data['password'] = Hash::make($request->password);

        //  Upload profile image if provided
        if ($request->hasFile('profile_image')) {
            $data['photo'] = $request->file('profile_image')->store('profile-photos', 'public');
        }

        $user = User::create($data);

        //  Assign role using Spatie
        $user->syncRoles([$request->role]);

        // Send credentials email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\UserCredentialsMail($user, $request->password));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send user credentials email: ' . $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = Role::pluck('name')->toArray();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'password'       => 'nullable|min:6|confirmed',
            'role'           => 'required|string|exists:roles,name',
            'profile_image'  => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        //  Replace existing profile image if new one is uploaded
        if ($request->hasFile('profile_image')) {
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            $data['photo'] = $request->file('profile_image')->store('profile-photos', 'public');
        }

        $user->update($data);

        //  Prevent removing admin role from main super admin (optional safety)
        if ($user->email === 'admin@stockmanagement.com' && $request->role !== 'admin') {
            return redirect()->route('users.edit', $user)
                ->with('error', 'You cannot remove the Admin role from the main system account.');
        }

        //  Sync new role cleanly
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        //  Protect system admin from deletion
        if ($user->email === 'admin@stockmanagement.com') {
            return redirect()->route('users.index')->with('error', 'You cannot delete the main admin account.');
        }

        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
