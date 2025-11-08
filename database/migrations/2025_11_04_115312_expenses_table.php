<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Core
            $table->date('date')->index();
            $table->decimal('amount', 14, 2);

            // Links
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // prevent deleting used categories

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Payment & meta
            $table->string('method', 10)->default('cash')->index(); // cash|bank|momo
            $table->string('reference', 100)->nullable();            // receipt/txn id
            $table->text('note')->nullable();

            $table->timestamps();

            // Helpful composite index
            $table->index(['category_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
