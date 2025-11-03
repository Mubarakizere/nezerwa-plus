@props(['title' => '', 'value' => '', 'color' => 'indigo'])

@php
    $colors = [
        'indigo' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-300'],
        'green'  => ['bg' => 'bg-green-50 dark:bg-green-900/30',  'text' => 'text-green-700 dark:text-green-300'],
        'red'    => ['bg' => 'bg-red-50 dark:bg-red-900/30',      'text' => 'text-red-700 dark:text-red-300'],
        'yellow' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/30','text' => 'text-yellow-700 dark:text-yellow-300'],
        'blue'   => ['bg' => 'bg-blue-50 dark:bg-blue-900/30',    'text' => 'text-blue-700 dark:text-blue-300'],
        'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30','text' => 'text-purple-700 dark:text-purple-300'],
        'gray'   => ['bg' => 'bg-gray-50 dark:bg-gray-800/50',    'text' => 'text-gray-700 dark:text-gray-300'],
    ];

    $bgClass = $colors[$color]['bg'] ?? $colors['indigo']['bg'];
    $textClass = $colors[$color]['text'] ?? $colors['indigo']['text'];
@endphp

<div class="p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
            {{ $bgClass }} transition-colors text-center">
    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">{{ $title }}</p>
    <p class="text-xl font-bold mt-1 {{ $textClass }}">{{ $value }}</p>
</div>
