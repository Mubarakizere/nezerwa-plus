<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $t) {
            // keep it simple: string column with default
            $t->string('payment_channel', 20)->default('cash')->after('status');
            $t->string('method')->nullable()->after('payment_channel'); // reference / txn id
            $t->index('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $t) {
            $t->dropColumn(['payment_channel','method']);
            $t->dropIndex(['purchase_date']);
        });
    }
};
