<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // 🔗 Relations
            $table->foreignId('product_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // 📦 Movement Info
            $table->enum('type', ['in', 'out'])
                ->comment('in = purchase/return, out = sale/loss');

            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 14, 2)->nullable();

            // 🧩 Polymorphic Source
            $table->string('source_type'); // e.g. App\Models\Purchase
            $table->unsignedBigInteger('source_id');

            // 🕒 Timestamps
            $table->timestamps();
            // optional: $table->softDeletes(); // if you want audit logs

            // ⚡ Indexes for performance
            $table->index(['product_id']);
            $table->index(['source_type', 'source_id']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
