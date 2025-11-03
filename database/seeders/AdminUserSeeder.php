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
        if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $roleId = DB::table('roles')->where('name', 'admin')->value('id');

            if ($roleId) {
                $pivotCols  = Schema::getColumnListing('role_user');
                $hasCreated = in_array('created_at', $pivotCols, true);
                $hasUpdated = in_array('updated_at', $pivotCols, true);

                $data = ['user_id' => $user->id, 'role_id' => $roleId];
                if (!DB::table('role_user')->where($data)->exists()) {
                    if ($hasCreated) $data['created_at'] = now();
                    if ($hasUpdated) $data['updated_at'] = now();
                    DB::table('role_user')->insert($data);
                }
            }
        }
        // Fallback to users.role string column
        elseif (Schema::hasColumn('users', 'role')) {
            if ($user->role !== 'admin') {
                $user->role = 'admin';
                $user->save();
            }
        }
    }
}
