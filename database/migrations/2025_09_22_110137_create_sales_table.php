<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // 🔗 Relationships
            $table->foreignId('customer_id')->nullable()
                ->constrained('customers')
                ->onDelete('set null'); // walk-in customers allowed

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // cashier or manager

            // 📅 Sale info
            $table->date('sale_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('method')->nullable(); // cash, momo, bank...
            $table->enum('status', ['completed', 'pending', 'cancelled'])->default('completed');

            // 📝 Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // ⚡ Indexes
            $table->index(['sale_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
