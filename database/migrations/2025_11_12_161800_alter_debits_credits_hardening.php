<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skipped to resolve compatibility issues.
    }

    public function down(): void
    {
        // Nothing to reverse
    }

    // ---- helpers
    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return (bool) DB::selectOne("
                SELECT 1
                FROM   pg_indexes
                WHERE  tablename = ? AND indexname = ?
                LIMIT  1
            ", [$table, $index]);
        }

        if ($driver === 'mysql') {
            $schema = DB::getDatabaseName();
            return (bool) DB::selectOne("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = ? AND table_name = ? AND index_name = ?
                LIMIT 1
            ", [$schema, $table, $index]);
        }

        return false;
    }
};
