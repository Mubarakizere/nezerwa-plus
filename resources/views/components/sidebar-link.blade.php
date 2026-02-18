@props(['active' => false, 'href'])

@php
$classes = $active
    ? 'block px-3 py-2 rounded-md bg-white/15 text-white font-medium'
    : 'block px-3 py-2 rounded-md text-indigo-100 hover:bg-white/10';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
