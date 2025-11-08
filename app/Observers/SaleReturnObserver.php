<?php

namespace App\Observers;

use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class SaleReturnObserver
{
    /**
     * Toggle recompute-from-observer by config.
     * Leave this false so the controller is the single source of truth.
     * If you ever want the observer to handle recompute (e.g., for imports),
     * set in config/app.php: 'recompute_sale_cache_via_observer' => true
     * or in .env via a config wrapper.
     */
    private function enabled(): bool
    {
        return (bool) config('app.recompute_sale_cache_via_observer', false);
    }

    public function created(SaleReturn $ret): void
    {
        $this->recompute($ret);
    }

    public function deleting(SaleReturn $ret): void
    {
        $this->recompute($ret);
    }

    /**
     * Recompute sale cached totals/status AFTER COMMIT (optional).
     * Does nothing unless enabled() returns true.
     */
    private function recompute(SaleReturn $ret): void
    {
        if (!$this->enabled()) {
            // Controller already handles recomputation; keep this a no-op.
            return;
        }

        DB::afterCommit(function () use ($ret) {
            $sale = $ret->sale()->first();
            if (!$sale) {
                return;
            }

            $returns = (float) $sale->returns()->sum('amount');
            $sale->returns_total = round($returns, 2);

            $net  = max(0, (float)$sale->total_amount - $sale->returns_total);
            $paid = (float)($sale->amount_paid ?? 0);
            $sale->status = ($paid + 0.009 >= $net) ? 'completed' : ($paid > 0 ? 'pending' : 'pending');

            $sale->save();
        });
    }
}
