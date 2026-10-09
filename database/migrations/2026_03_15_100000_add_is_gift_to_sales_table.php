<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_gift')->default(false)->after('status')->index();
            $table->string('gift_reason')->nullable()->after('is_gift');
        });

        // Ensure 'Boss Gift' exists in payment_channels
        if (Schema::hasTable('payment_channels')) {
            $exists = DB::table('payment_channels')->where('slug', 'boss_gift')->exists();
            if (!$exists) {
                DB::table('payment_channels')->insert([
                    'name'        => 'Boss Gift',
                    'slug'        => 'boss_gift',
                    'description' => 'Complimentary gift authorized by the boss (no loan, stock removed)',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['is_gift', 'gift_reason']);
        });

        if (Schema::hasTable('payment_channels')) {
            DB::table('payment_channels')->where('slug', 'boss_gift')->delete();
        }
    }
};
