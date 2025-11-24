<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE debits_credits ALTER COLUMN "date" SET DEFAULT CURRENT_DATE');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE debits_credits MODIFY COLUMN `date` DATE DEFAULT CURRENT_DATE");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE debits_credits ALTER COLUMN "date" DROP DEFAULT');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE debits_credits MODIFY COLUMN `date` DATE DEFAULT NULL");
        }
    }
};
