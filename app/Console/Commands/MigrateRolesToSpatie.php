<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class MigrateRolesToSpatie extends Command
{
    protected $signature = 'roles:migrate-to-spatie';
    protected $description = 'Migrate old role_user pivot to Spatie model_has_roles structure.';

    public function handle()
    {
        $this->info('🚀 Starting migration from role_user → model_has_roles...');

        // Step 1: Ensure guard_name = 'web' for all roles
        $updated = Role::whereNull('guard_name')->orWhere('guard_name', '!=', 'web')
            ->update(['guard_name' => 'web']);
        $this->info("✅ Updated {$updated} roles with guard_name = 'web'.");

        // Step 2: Check if old table exists
        if (!Schema::hasTable('role_user')) {
            $this->error('❌ Table role_user not found. Aborting.');
            return Command::FAILURE;
        }

        $oldPivots = DB::table('role_user')->get();

        if ($oldPivots->isEmpty()) {
            $this->warn('⚠️ No entries found in role_user.');
        }

        $count = 0;

        foreach ($oldPivots as $pivot) {
            $exists = DB::table('model_has_roles')
                ->where('role_id', $pivot->role_id)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $pivot->user_id)
                ->exists();

            if (!$exists) {
                DB::table('model_has_roles')->insert([
                    'role_id'    => $pivot->role_id,
                    'model_type' => 'App\\Models\\User',
                    'model_id'   => $pivot->user_id,
                ]);
                $count++;
            }
        }

        $this->info("✅ Migrated {$count} records into model_has_roles.");

        // Step 3: Ask to drop old pivot
        if ($this->confirm('🗑️  Drop old role_user table?')) {
            Schema::dropIfExists('role_user');
            $this->info('✅ Dropped role_user table.');
        }

        $this->info('🎉 Migration complete! Your system now uses only Spatie roles.');
        return Command::SUCCESS;
    }
}
