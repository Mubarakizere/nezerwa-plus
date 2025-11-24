<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('method', 50); // cash, bank, momo, mobile
            $table->decimal('amount', 15, 2);
            $table->string('reference', 100)->nullable(); // POS ref, Txn ID, cheque, etc.
            $table->string('phone', 30)->nullable();      // momo/mobile phone if relevant
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['method', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
