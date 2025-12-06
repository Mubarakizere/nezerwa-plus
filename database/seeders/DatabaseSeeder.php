<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            ProductImportSeeder::class,
            // OpeningBalancesSeeder::class, // Run manually or uncomment to seed opening balances
            // UserSeeder::class, // Disabled per user request
        ]);
    }
}
