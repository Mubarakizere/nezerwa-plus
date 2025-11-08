<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('date')->index();

            // Amount being returned/credited (positive number)
            $table->decimal('amount', 14, 2);

            // How the refund/credit is processed (optional)
            $table->string('method', 10)->nullable()->index(); // cash|bank|momo

            $table->string('reference', 100)->nullable(); // receipt / txn id
            $table->text('reason')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['sale_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
