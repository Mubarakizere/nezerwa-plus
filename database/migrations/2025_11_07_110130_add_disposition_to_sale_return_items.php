<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_return_items', function (Blueprint $t) {
            // where supported, use enum; otherwise use string
            if (Schema::hasColumn('sale_return_items', 'disposition') === false) {
                $t->string('disposition', 20)->default('restock');
            }
            if (Schema::hasColumn('sale_return_items', 'notes') === false) {
                $t->text('notes')->nullable();
            }
        });
    }
    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $t) {
            if (Schema::hasColumn('sale_return_items', 'disposition')) $t->dropColumn('disposition');
            if (Schema::hasColumn('sale_return_items', 'notes')) $t->dropColumn('notes');
        });
    }
};
