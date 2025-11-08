<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Allow: pending | partial | completed | cancelled
            DB::statement("ALTER TABLE purchases DROP CONSTRAINT IF EXISTS purchases_status_check");
            DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_status_check CHECK (status IN ('pending','partial','completed','cancelled'))");
            DB::statement("ALTER TABLE purchases ALTER COLUMN status SET DEFAULT 'pending'");

            // Optional: coerce any NULL/empty to 'pending'
            DB::statement("UPDATE purchases SET status = 'pending' WHERE status IS NULL OR status = ''");
        } else {
            // MySQL fallback
            Schema::table('purchases', function ($table) {
                $table->enum('status', ['pending','partial','completed','cancelled'])->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE purchases DROP CONSTRAINT IF EXISTS purchases_status_check");
            DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_status_check CHECK (status IN ('pending','completed','cancelled'))");
            DB::statement("ALTER TABLE purchases ALTER COLUMN status SET DEFAULT 'completed'");
            DB::statement("UPDATE purchases SET status = 'pending' WHERE status = 'partial'");
        } else {
            Schema::table('purchases', function ($table) {
                $table->enum('status', ['pending','completed','cancelled'])->default('completed')->change();
            });
        }
    }
};
