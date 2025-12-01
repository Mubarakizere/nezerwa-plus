<table class="min-w-[1200px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs font-medium">
        <tr>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Category</th>
            <th class="px-4 py-3 text-left">SKU</th>
            <th class="px-4 py-3 text-right">Cost (WAC)</th>
            <th class="px-4 py-3 text-right">Price</th>
            <th class="px-4 py-3 text-right">Margin</th>
            <th class="px-4 py-3 text-right">In</th>
            <th class="px-4 py-3 text-right">Out</th>
            <th class="px-4 py-3 text-right">Returned</th>
            <th class="px-4 py-3 text-right">Stock</th>
            <th class="px-4 py-3 text-left">Last Moved</th>
            <th class="px-4 py-3 text-right">Actions</th>
        </tr>
    </thead>

    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
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
                $catOk = $cat && (($cat->is_active ?? true) && in_array($cat->kind ?? 'product',['product','both']));
                $dot   = $cat->color ?? '#6b7280';
            @endphp

            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                {{-- Name --}}
                <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                    @can('products.view')
                        <a href="{{ route('products.show', $p) }}"
                           class="hover:text-indigo-600 dark:hover:text-indigo-400">
                            {{ $p->name }}
                        </a>
                    @else
                        {{ $p->name }}
                    @endcan

                    @if($ret > 0)
                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            <i data-lucide="u-turn-left" class="w-3 h-3"></i>
                            Returns
                        </span>
                    @endif
                </td>

                {{-- Category --}}
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                    @if($cat)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $dot }}"></span>
                            <span>{{ $cat->name }}</span>

                            @if(!empty($cat->code))
                                <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                    {{ $cat->code }}
                                </span>
                            @endif

                            @unless($catOk)
                                <span class="ml-2 text-[11px] px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                    Not usable
                                </span>
                            @endunless
                        </span>
                    @else
                        <span class="text-rose-600 dark:text-rose-300">—</span>
                    @endif
                </td>

                {{-- SKU --}}
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                    {{ $p->sku ?? '—' }}
                </td>

                {{-- Cost --}}
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                    RWF {{ number_format($cost, 2) }}
                </td>

                {{-- Price --}}
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                    RWF {{ number_format($price, 2) }}
                </td>

                {{-- Margin --}}
                <td class="px-4 py-3 text-right">
                    <span class="font-medium {{ $margin >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ number_format($margin, 2) }}%
                    </span>
                </td>

                {{-- In --}}
                <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-400 font-semibold">
                    {{ number_format($in) }}
                </td>

                {{-- Out --}}
                <td class="px-4 py-3 text-right text-rose-700 dark:text-rose-400 font-semibold">
                    {{ number_format($out) }}
                </td>

                {{-- Returned --}}
                <td class="px-4 py-3 text-right text-amber-700 dark:text-amber-400">
                    {{ number_format($ret) }}
                </td>

                {{-- Stock --}}
                <td class="px-4 py-3 text-right font-semibold
                    {{ $zero ? 'text-rose-600 dark:text-rose-400'
                             : ($low ? 'text-amber-700 dark:text-amber-400'
                                     : 'text-gray-900 dark:text-gray-100') }}">
                    {{ number_format($stk) }}
                    @if($zero)
                        <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                            Out
                        </span>
                    @elseif($low)
                        <span class="ml-2 px-2 py-0.5 text-[11px] rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                            Low
                        </span>
                    @endif
                </td>

                {{-- Last moved --}}
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                    {{ $last }}
                </td>

                {{-- Actions --}}
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                    @can('products.view')
                        <a href="{{ route('products.show', $p) }}"
                           class="btn btn-secondary text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            View
                        </a>
                    @endcan

                    @can('products.edit')
                        <a href="{{ route('products.edit', $p) }}"
                           class="btn btn-outline text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            Edit
                        </a>
                    @endcan

                    @can('stock.view')
                        <a href="{{ route('stock.history', ['product_id' => $p->id]) }}"
                           class="btn btn-outline text-xs inline-flex items-center gap-1 px-2.5 py-1.5">
                            <i data-lucide="history" class="w-3.5 h-3.5"></i>
                            Moves
                        </a>
                    @endcan

                    @can('products.delete')
                        <form action="{{ route('products.destroy', $p) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                class="btn btn-danger text-xs inline-flex items-center gap-1 px-2.5 py-1.5"
                                @click="$store.confirm.openWith($el.closest('form'))">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                Delete
                            </button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                    No products found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
