<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $t->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $t->decimal('quantity', 15, 2);
            $t->decimal('unit_price', 15, 2);
            $t->decimal('line_total', 15, 2);

            $t->timestamps();

            $t->index(['sale_return_id']);
            $t->index(['sale_item_id']);
            $t->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
