<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->date('return_date')->index();
            $table->string('payment_channel')->nullable(); // cash/bank/momo when refund > 0
            $table->string('method')->nullable();          // reference / txn id
            $table->text('notes')->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);  // sum of item totals
            $table->decimal('refund_amount', 15, 2)->default(0); // cash refunded now (<= total_amount)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
