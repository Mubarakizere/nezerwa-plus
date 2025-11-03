@props(['active' => false, 'href'])

@php
$classes = $active
    ? 'block px-3 py-2 rounded-md bg-indigo-100 text-indigo-700 font-medium'
    : 'block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
