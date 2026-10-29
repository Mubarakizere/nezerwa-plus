<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminAuthService
{
    /**
     * Verify an admin password for protected operations (e.g., editing product stock).
     * Returns the authorizing User model if valid, or null if invalid.
     *
     * @param string|null $password
     * @return User|null
     */
    public static function verifyAdminPassword(?string $password): ?User
    {
        if (empty($password)) {
            return null;
        }

        // 1. If currently logged in user is already an admin, verify against their own password
        $currentUser = Auth::user();
        if ($currentUser && self::isUserAdmin($currentUser)) {
            if (Hash::check($password, $currentUser->password)) {
                return $currentUser;
            }
        }

        // 2. Otherwise (or if current user is not admin), check against any active admin/boss user
        $adminUsers = collect();

        try {
            // Spatie roles
            $spatieAdmins = User::role(['admin', 'boss'])->get();
            $adminUsers = $adminUsers->merge($spatieAdmins);
        } catch (\Throwable $e) {
            // Fallback if roles table is unavailable
        }

        // Check if legacy 'role' column exists on users table
        if (Schema::hasColumn('users', 'role')) {
            $columnAdmins = User::whereIn('role', ['admin', 'boss'])->get();
            $adminUsers = $adminUsers->merge($columnAdmins);
        }

        $adminUsers = $adminUsers->unique('id');

        foreach ($adminUsers as $admin) {
            if (Hash::check($password, $admin->password)) {
                return $admin;
            }
        }

        return null;
    }

    /**
     * Check if a user has admin or boss role.
     *
     * @param User|null $user
     * @return bool
     */
    public static function isUserAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'boss'])) {
            return true;
        }

        if (isset($user->role) && in_array($user->role, ['admin', 'boss'])) {
            return true;
        }

        return false;
    }
}
