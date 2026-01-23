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
            'audit', // New Audit module permissions (audit.view, audit.create, etc.)
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

        // Granular view permissions for dashboard data filtering
        $granularViewPermissions = [
            'sales.view-own',          // View only own sales
            'sales.view-all',          // View all sales
            'transactions.view-own',   // View only own transactions
            'transactions.view-all',   // View all transactions
            'reports.view-own',        // View only own reports
            'reports.view-all',        // View all reports
        ];

        foreach ($granularViewPermissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
