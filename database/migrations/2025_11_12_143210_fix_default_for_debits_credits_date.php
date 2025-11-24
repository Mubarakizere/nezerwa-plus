<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Skipped to resolve compatibility issues.
        // This migration was intended to fix a default value but caused issues on MariaDB.
        // The next migration (2025_11_12_161800) handles the column definition anyway.
    }

    public function down(): void
    {
        // Nothing to reverse
    }
};
