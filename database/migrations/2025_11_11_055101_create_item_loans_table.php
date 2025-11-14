<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partner_id')
                ->constrained('partner_companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // direction: 'given' (we lent out) | 'taken' (we borrowed)
            $table->string('direction', 10);

            $table->string('item_name');                          // required free text
            $table->string('unit', 20)->nullable();               // pcs, bottles, boxes...
            $table->decimal('quantity', 12, 2);                   // > 0
            $table->date('loan_date');                            // required
            $table->date('due_date')->nullable();                 // optional
            $table->decimal('quantity_returned', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');     // pending | partial | returned | overdue
            $table->text('notes')->nullable();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['direction', 'status']);
            $table->index(['loan_date', 'due_date']);
            $table->index('partner_id');
        });

        // Postgres-safe CHECK constraint for direction
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE item_loans
                ADD CONSTRAINT item_loans_direction_check
                CHECK (direction IN ('given','taken'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_loans');
    }
};
