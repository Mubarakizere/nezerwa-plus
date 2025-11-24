<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function ($table) {
            // Standard way to change column type/default
            // Note: 'enum' in Laravel migrations maps to VARCHAR on some drivers or native ENUM on others.
            // For maximum compatibility, using string with application-level validation is safer,
            // but if we must stick to enum:
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
