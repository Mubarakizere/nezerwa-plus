<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_loan_returns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_loan_id')
                ->constrained('item_loans')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->decimal('returned_qty', 12, 2);    // > 0
            $table->date('return_date');               // required
            $table->text('note')->nullable();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['item_loan_id', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_loan_returns');
    }
};
