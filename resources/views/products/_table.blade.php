@php
    $fmt0 = fn($n) => number_format((float)($n ?? 0), 0);
    $fmt2 = fn($n) => number_format((float)($n ?? 0), 2);
    $threshold = (int)($threshold ?? 5);

    $coll = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products);

    $totalIn   = $coll->sum(fn($p) => (float)($p->qty_in ?? 0));
    $totalOut  = $coll->sum(fn($p) => (float)($p->qty_out ?? 0));
    $totalRet  = $coll->sum(fn($p) => (float)($p->qty_returned ?? 0));
    $totalStk  = $coll->sum(fn($p) => max(0, (int)($p->qty_in ?? 0) - (int)($p->qty_out ?? 0)));
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs">
        <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3 text-right">Price / Cost</th>
                <th class="px-4 py-3 text-right">Margin</th>
                <th class="px-4 py-3 text-center">Movement</th>
                <th class="px-4 py-3 text-right">Current Stock</th>
                <th class="px-4 py-3 text-left">Last Moved</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
            @forelse ($products as $p)
                @php
                    $in   = (float)($p->qty_in ?? 0);
                    $out  = (float)($p->qty_out ?? 0);
                    $ret  = (float)($p->qty_returned ?? 0);
                    $stk  = max(0, $in - $out);
                    $low  = $stk <= $threshold && $stk > 0;
                    $zero = $stk <= 0;

                    $cost   = (float)($p->cost_price ?? 0);
                    $price  = (float)($p->price ?? 0);
                    $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;

                    $last  = $p->last_moved_at ? \Carbon\Carbon::parse($p->last_moved_at)->diffForHumans() : '—';

                    $cat   = $p->category ?? null;
                    $catOk = $cat && (($cat->is_active ?? true) && in_array($cat->kind ?? 'product', ['product','both']));
                    $dot   = $cat->color ?? '#6366f1';
                @endphp

                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                    {{-- 1. Product Info --}}
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i data-lucide="package" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                @can('products.view')
                                    <a href="{{ route('products.show', $p) }}"
                                       class="font-semibold text-slate-900 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors line-clamp-1">
                                        {{ $p->name }}
                                    </a>
                                @else
                                    <span class="font-semibold text-slate-900 dark:text-slate-100 line-clamp-1">
                                        {{ $p->name }}
                                    </span>
                                @endcan

                                <div class="flex items-center gap-2 mt-0.5">
                                    @if(!empty($p->sku))
                                        <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500">
                                            SKU: {{ $p->sku }}
                                        </span>
                                    @else
                                        <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500">
                                            #{{ $p->id }}
                                        </span>
                                    @endif

                                    @if($ret > 0)
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                            <i data-lucide="rotate-ccw" class="w-2.5 h-2.5"></i>
                                            {{ $fmt0($ret) }} ret
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- 2. Category --}}
                    <td class="px-4 py-3">
                        @if($cat)
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $dot }}"></span>
                                <span class="truncate max-w-[120px]">{{ $cat->name }}</span>
                                @if(!empty($cat->code))
                                    <span class="text-[10px] font-mono text-slate-400">({{ $cat->code }})</span>
                                @endif
                            </div>
                            @unless($catOk)
                                <div class="text-[10px] text-amber-600 dark:text-amber-400 font-medium mt-0.5">Archived category</div>
                            @endunless
                        @else
                            <span class="text-slate-400 text-xs italic">Uncategorized</span>
                        @endif
                    </td>

                    {{-- 3. Price & Cost --}}
                    <td class="px-4 py-3 text-right">
                        <div class="font-bold text-slate-900 dark:text-slate-100 font-mono">
                            {{ $fmt2($price) }} <span class="text-[10px] text-slate-400 font-normal">RWF</span>
                        </div>
                        <div class="text-[11px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                            Cost: {{ $fmt2($cost) }}
                        </div>
                    </td>

                    {{-- 4. Margin --}}
                    <td class="px-4 py-3 text-right">
                        @if($price > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold font-mono {{ $margin >= 0 ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20' }}">
                                {{ $margin >= 0 ? '+' : '' }}{{ $fmt2($margin) }}%
                            </span>
                        @else
                            <span class="text-slate-400 font-mono">—</span>
                        @endif
                    </td>

                    {{-- 5. Movement (In / Out) --}}
                    <td class="px-4 py-3 text-center">
                        <div class="inline-flex items-center gap-2 px-2 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-800 text-[11px] font-mono">
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold" title="Total In">
                                ↓ {{ $fmt0($in) }}
                            </span>
                            <span class="text-slate-300 dark:text-slate-600">/</span>
                            <span class="text-slate-600 dark:text-slate-400 font-semibold" title="Total Out">
                                ↑ {{ $fmt0($out) }}
                            </span>
                        </div>
                    </td>

                    {{-- 6. Current Stock --}}
                    <td class="px-4 py-3 text-right">
                        @if($zero)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Out of Stock (0)
                            </span>
                        @elseif($low)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                {{ $fmt0($stk) }} left (Low)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ $fmt0($stk) }} in stock
                            </span>
                        @endif
                    </td>

                    {{-- 7. Last Moved --}}
                    <td class="px-4 py-3 text-left">
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $last }}
                        </span>
                    </td>

                    {{-- 8. Actions --}}
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            @can('products.view')
                                <a href="{{ route('products.show', $p) }}"
                                   title="View Product Details"
                                   class="p-1.5 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg transition-colors">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            @endcan

                            @can('products.edit')
                                <a href="{{ route('products.edit', $p) }}"
                                   title="Edit Product"
                                   class="p-1.5 text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>
                            @endcan

                            @can('stock.view')
                                <a href="{{ route('stock.history', ['product_id' => $p->id]) }}"
                                   title="Stock Movement History"
                                   class="p-1.5 text-slate-500 hover:text-sky-600 dark:text-slate-400 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 rounded-lg transition-colors">
                                    <i data-lucide="history" class="w-4 h-4"></i>
                                </a>
                            @endcan

                            @can('products.delete')
                                <form action="{{ route('products.destroy', $p) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        title="Delete Product"
                                        class="p-1.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors"
                                        @click="$store.confirm.openWith($el.closest('form'))">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                <i data-lucide="package-search" class="w-6 h-6"></i>
                            </div>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">No products found</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Try adjusting your search query, selecting another category, or resetting active filters.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>

        {{-- Table Summary Footer --}}
        @if($coll->isNotEmpty())
            <tfoot class="bg-slate-50/80 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right uppercase tracking-wider text-[11px] text-slate-500 dark:text-slate-400">
                        Page Totals ({{ $coll->count() }} items):
                    </td>
                    <td class="px-4 py-3 text-center font-mono">
                        <div class="inline-flex items-center gap-2 px-2 py-0.5 rounded bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700/60 text-[11px]">
                            <span class="text-emerald-600 dark:text-emerald-400">↓ {{ $fmt0($totalIn) }}</span>
                            <span class="text-slate-300 dark:text-slate-600">/</span>
                            <span class="text-slate-600 dark:text-slate-400">↑ {{ $fmt0($totalOut) }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                        {{ $fmt0($totalStk) }} units
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
