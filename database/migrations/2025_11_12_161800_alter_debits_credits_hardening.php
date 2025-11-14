<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ---- 0) Clean duplicates on transaction_id (keep the newest id)
        DB::transaction(function () {
            $dups = DB::table('debits_credits')
                ->select('transaction_id', DB::raw('COUNT(*) as c'))
                ->whereNotNull('transaction_id')
                ->groupBy('transaction_id')
                ->having('c', '>', 1)
                ->get();

            foreach ($dups as $dup) {
                $ids = DB::table('debits_credits')
                    ->where('transaction_id', $dup->transaction_id)
                    ->orderByDesc('id') // keep newest
                    ->pluck('id')
                    ->all();

                $toDelete = array_slice($ids, 1); // everything except first
                if (!empty($toDelete)) {
                    DB::table('debits_credits')->whereIn('id', $toDelete)->delete();
                }
            }
        });

        // ---- 1) Indexes & unique
        Schema::table('debits_credits', function (Blueprint $table) {
            // composite filter index
            if (! $this->indexExists('debits_credits', 'dc_type_date_idx')) {
                $table->index(['type', 'date'], 'dc_type_date_idx');
            }
            if (! $this->indexExists('debits_credits', 'debits_credits_user_id_index')) {
                $table->index('user_id');
            }
            if (! $this->indexExists('debits_credits', 'debits_credits_customer_id_index')) {
                $table->index('customer_id');
            }
            if (! $this->indexExists('debits_credits', 'debits_credits_supplier_id_index')) {
                $table->index('supplier_id');
            }
        });

        // unique on transaction_id (allows many NULLs)
        if (! $this->indexExists('debits_credits', 'debits_credits_transaction_id_unique')) {
            Schema::table('debits_credits', function (Blueprint $table) {
                $table->unique('transaction_id', 'debits_credits_transaction_id_unique');
            });
        }

        // ---- 2) Drop the frozen default on `date`
        $driver = DB::getDriverName();
        try {
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE debits_credits ALTER COLUMN date DROP DEFAULT");
            } elseif ($driver === 'mysql') {
                // MySQL 8.0.13+: ALTER .. ALTER col DROP DEFAULT
                DB::statement("ALTER TABLE debits_credits ALTER date DROP DEFAULT");
            }
        } catch (\Throwable $e) {
            // ignore if server/version doesn't support; your controller already sets date.
        }

        // ---- 3) Party guard (both customer & supplier cannot be set together)
        try {
            $check = 'debits_credits_party_check';
            if ($driver === 'pgsql') {
                DB::statement("
                    DO $$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM pg_constraint
                            WHERE conname = '{$check}'
                        ) THEN
                            ALTER TABLE debits_credits
                            ADD CONSTRAINT {$check}
                            CHECK (NOT (customer_id IS NOT NULL AND supplier_id IS NOT NULL));
                        END IF;
                    END$$;
                ");
            } elseif ($driver === 'mysql') {
                // MySQL 8.0.16+ supports CHECK; no IF EXISTS syntax, so try/catch
                DB::statement("
                    ALTER TABLE debits_credits
                    ADD CONSTRAINT {$check}
                    CHECK (NOT (customer_id IS NOT NULL AND supplier_id IS NOT NULL))
                ");
            }
        } catch (\Throwable $e) {
            // Older MySQL silently ignores CHECKs; that's okay.
        }
    }

    public function down(): void
    {
        // Drop unique & indexes (safe if missing)
        try {
            Schema::table('debits_credits', function (Blueprint $table) {
                if ($this->indexExists('debits_credits', 'debits_credits_transaction_id_unique')) {
                    $table->dropUnique('debits_credits_transaction_id_unique');
                }
                if ($this->indexExists('debits_credits', 'dc_type_date_idx')) {
                    $table->dropIndex('dc_type_date_idx');
                }
                if ($this->indexExists('debits_credits', 'debits_credits_user_id_index')) {
                    $table->dropIndex('debits_credits_user_id_index');
                }
                if ($this->indexExists('debits_credits', 'debits_credits_customer_id_index')) {
                    $table->dropIndex('debits_credits_customer_id_index');
                }
                if ($this->indexExists('debits_credits', 'debits_credits_supplier_id_index')) {
                    $table->dropIndex('debits_credits_supplier_id_index');
                }
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // Drop the check constraint if it exists (Postgres)
        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE debits_credits DROP CONSTRAINT IF EXISTS debits_credits_party_check");
            } elseif (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE debits_credits DROP CHECK debits_credits_party_check");
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // (We won't restore the old frozen date default.)
    }

    // ---- helpers
    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return (bool) DB::selectOne("
                SELECT 1
                FROM   pg_indexes
                WHERE  tablename = ? AND indexname = ?
                LIMIT  1
            ", [$table, $index]);
        }

        if ($driver === 'mysql') {
            $schema = DB::getDatabaseName();
            return (bool) DB::selectOne("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = ? AND table_name = ? AND index_name = ?
                LIMIT 1
            ", [$schema, $table, $index]);
        }

        return false;
    }
};
