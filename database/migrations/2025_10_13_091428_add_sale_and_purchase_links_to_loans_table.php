<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('loans', function (Blueprint $table) {
        $table->foreignId('sale_id')->nullable()
            ->constrained('sales')->nullOnDelete();
        $table->foreignId('purchase_id')->nullable()
            ->constrained('purchases')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('loans', function (Blueprint $table) {
        $table->dropForeign(['sale_id']);
        $table->dropForeign(['purchase_id']);
        $table->dropColumn(['sale_id', 'purchase_id']);
    });
}

};
