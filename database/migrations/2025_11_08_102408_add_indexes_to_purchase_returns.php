<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // POSTGRES-safe: create indexes only if missing
        DB::statement('CREATE INDEX IF NOT EXISTS pr_purchase_id_return_date_idx ON purchase_returns (purchase_id, return_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS pr_supplier_id_idx              ON purchase_returns (supplier_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS pr_payment_channel_idx          ON purchase_returns (payment_channel)');

        DB::statement('CREATE INDEX IF NOT EXISTS pri_purchase_return_id_idx      ON purchase_return_items (purchase_return_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS pri_product_id_idx              ON purchase_return_items (product_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS pri_purchase_item_id_idx        ON purchase_return_items (purchase_item_id)');

        // Do NOT add foreign keys here — they already exist.
    }

    public function down(): void
    {
        // Drop indexes if they exist (safe)
        DB::statement('DROP INDEX IF EXISTS pr_purchase_id_return_date_idx');
        DB::statement('DROP INDEX IF EXISTS pr_supplier_id_idx');
        DB::statement('DROP INDEX IF EXISTS pr_payment_channel_idx');

        DB::statement('DROP INDEX IF EXISTS pri_purchase_return_id_idx');
        DB::statement('DROP INDEX IF EXISTS pri_product_id_idx');
        DB::statement('DROP INDEX IF EXISTS pri_purchase_item_id_idx');
    }
};
