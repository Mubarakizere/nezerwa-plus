<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@stockmanagement.com'],
            [
                'name' => 'Store Manager',
                'password' => Hash::make('manager123'),
                'email_verified_at' => now(),
            ]
        );

        // Cashier
        $cashier = User::updateOrCreate(
            ['email' => 'cashier@stockmanagement.com'],
            [
                'name' => 'Cashier User',
                'password' => Hash::make('cashier123'),
                'email_verified_at' => now(),
            ]
        );

        // Attach roles if tables exist
        if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $managerRole = DB::table('roles')->where('name', 'manager')->value('id');
            $cashierRole = DB::table('roles')->where('name', 'cashier')->value('id');

            if ($managerRole) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $manager->id, 'role_id' => $managerRole],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            if ($cashierRole) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $cashier->id, 'role_id' => $cashierRole],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
        // Fallback for `users.role` column
        elseif (Schema::hasColumn('users', 'role')) {
            $manager->role = 'manager';
            $cashier->role = 'cashier';
            $manager->save();
            $cashier->save();
        }
    }
}
