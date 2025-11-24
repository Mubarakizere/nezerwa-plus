<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name     = env('ADMIN_NAME', 'System Admin');
        $email    = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        // Prefer pivot table role_user if present
        // Assign Spatie Role
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        
        // Sync all permissions to the admin role
        $permissions = \Spatie\Permission\Models\Permission::all();
        $role->syncPermissions($permissions);

        // Assign role to user
        $user->assignRole($role);

        // Fallback/Legacy: Update 'role' column if it exists
        if (Schema::hasColumn('users', 'role')) {
            if ($user->role !== 'admin') {
                $user->role = 'admin';
                $user->save();
            }
        }
    }
}
