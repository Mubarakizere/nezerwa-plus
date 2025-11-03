@props(['title', 'headers', 'items', 'columns', 'color' => 'text-indigo-600'])

<div class="bg-white rounded-xl shadow p-6 border border-gray-100">
    <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 {{ $color }}" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 3v18h18M9 9l3 3-3 3m4-6l3 3-3 3"/>
        </svg>
        {{ $title }}
    </h4>

    <table class="w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-700">
        <tr>
            @foreach($headers as $header)
                <th class="px-4 py-2">{{ $header }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr class="border-t hover:bg-gray-50">
                @foreach($columns as $col)
                    @php
                        $value = data_get($item, $col, 'N/A');
                    @endphp
                    <td class="px-4 py-2 {{ $loop->last ? 'text-right' : '' }}">
                        {{ is_numeric($value) ? number_format($value, 2) : $value }}
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($headers) }}" class="text-center py-3 text-gray-500">No data</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
