@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Audit Logs</h1>
        <div class="text-sm text-gray-500">Tracking all system changes</div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('audit.logs') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Action</label>
                <select name="action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Actions</option>
                    <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                    <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Update</option>
                    <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">Filter</button>
                <a href="{{ route('audit.logs') }}" class="text-gray-600 ml-2 hover:underline">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changes</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($logs as $log)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $log->user->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            @if($log->action == 'create') bg-green-100 text-green-800 
                            @elseif($log->action == 'update') bg-blue-100 text-blue-800 
                            @elseif($log->action == 'delete') bg-red-100 text-red-800 
                            @endif">
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                        @if($log->action == 'update')
                            @php $changes = $log->new_values ?? []; @endphp
                            @foreach($changes as $key => $val)
                                <span class="block"><strong>{{ $key }}:</strong> {{ is_array($val) ? json_encode($val) : $val }}</span>
                            @endforeach
                        @else
                           -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
