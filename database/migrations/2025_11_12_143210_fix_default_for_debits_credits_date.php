<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE debits_credits ALTER COLUMN \"date\" SET DEFAULT CURRENT_DATE");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE debits_credits ALTER COLUMN \"date\" DROP DEFAULT");
    }
};
