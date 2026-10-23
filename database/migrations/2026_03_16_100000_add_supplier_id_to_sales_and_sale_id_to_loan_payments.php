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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->index('supplier_id');
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->foreignId('sale_id')
                ->nullable()
                ->after('loan_id')
                ->constrained('sales')
                ->nullOnDelete();

            $table->index('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });
    }
};
