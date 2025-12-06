<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'users',
            'roles',
            'categories',
            'products',
            'suppliers',
            'customers',
            'purchases',
            'sales',
            'transactions',
            'loans',
            'debits-credits',
            'stock',
            'reports',
            'expenses',
            'item-loans',
            'partner-companies',
            'payment-channels',
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }
    }
}
