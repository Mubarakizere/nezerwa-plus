@props(['active' => false, 'href'])

@php
$classes = $active
    ? 'flex items-center gap-2.5 px-3 py-2 rounded-lg bg-indigo-600 text-white font-medium text-sm shadow-sm transition-colors'
    : 'flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/50 font-medium text-sm transition-colors';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
