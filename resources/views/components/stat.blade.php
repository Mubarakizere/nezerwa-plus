@props(['label' => 'Label', 'value' => 0, 'color' => null, 'icon' => null])

@php
    $iconMap = [
        'sales' => 'dollar-sign', 'profit' => 'trending-up', 'purchase' => 'shopping-bag',
        'credit' => 'arrow-up-circle', 'debit' => 'arrow-down-circle', 'balance' => 'scale',
        'loan' => 'banknote', 'payment' => 'wallet', 'stock' => 'package', 'value' => 'coins',
    ];

    $colorMap = [
        'sales' => 'text-indigo-600', 'profit' => 'text-green-600', 'purchase' => 'text-pink-600',
        'credit' => 'text-blue-600', 'debit' => 'text-red-600', 'balance' => 'text-emerald-600',
        'loan' => 'text-yellow-600', 'payment' => 'text-purple-600', 'stock' => 'text-amber-600',
        'value' => 'text-sky-600',
    ];

    $selectedIcon = $icon ?? 'bar-chart-2';
    $selectedColor = $color ?? 'text-gray-800 dark:text-gray-200';

    foreach ($iconMap as $key => $ic) {
        if (str_contains(strtolower($label), $key)) {
            $selectedIcon = $icon ?? $ic;
            $selectedColor = $color ?? ($colorMap[$key] ?? $selectedColor);
            break;
        }
    }

    $formatted = is_numeric($value)
        ? number_format($value, abs($value) >= 1000 ? 0 : 2)
        : $value;
@endphp

<div class="relative group p-4 sm:p-5 rounded-2xl
            border border-gray-300 dark:border-gray-700
            bg-white dark:bg-gray-800/80
            backdrop-blur-sm
            shadow-sm hover:shadow-lg
            transition-all duration-500 overflow-hidden
            flex flex-col justify-between min-w-[160px] sm:min-w-[180px]">

    {{-- Hover Gradient --}}
    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition duration-700
                bg-gradient-to-br from-indigo-100/40 via-transparent to-transparent dark:from-indigo-900/30"></div>

    {{-- Label & Icon --}}
    <div class="relative flex items-center justify-between gap-2 flex-wrap">
        <span class="text-[0.8rem] sm:text-sm font-medium text-gray-600 dark:text-gray-400 leading-tight">
            {{ $label }}
        </span>
        <i data-lucide="{{ $selectedIcon }}" class="w-4 h-4 sm:w-5 sm:h-5 {{ $selectedColor }}"></i>
    </div>

    {{-- Value --}}
    <div class="mt-2 sm:mt-3 flex items-end gap-1 flex-wrap">
        <span class="count-up text-2xl sm:text-3xl font-bold {{ $selectedColor }}"
              data-value="{{ $value }}">
            {{ $formatted }}
        </span>
        <span class="text-xs sm:text-sm text-gray-400 dark:text-gray-500 mb-0.5">RWF</span>
    </div>
</div>

{{-- Count-up Animation --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.count-up').forEach(el => {
        const target = parseFloat(el.dataset.value);
        if (isNaN(target)) return;
        let current = 0;
        const duration = 1200;
        const step = target / (duration / 16);
        const animate = () => {
            current += step;
            if ((step > 0 && current >= target) || (step < 0 && current <= target)) {
                el.textContent = target.toLocaleString('en-US', { maximumFractionDigits: 2 });
            } else {
                el.textContent = current.toLocaleString('en-US', { maximumFractionDigits: 2 });
                requestAnimationFrame(animate);
            }
        };
        requestAnimationFrame(animate);
    });
});
</script>
