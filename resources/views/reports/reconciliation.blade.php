@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Mistake Detection & Reconciliation</h1>
            <p class="text-sm text-gray-500">System automatically flags potential data mismatches.</p>
        </div>
        <div class="text-right">
             <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">Auto-Scan Active</span>
        </div>
    </div>

    @if(count($issues) > 0)
        <div class="space-y-4">
            @foreach($issues as $issue)
                <div class="bg-white border-l-4 {{ $issue['severity'] == 'high' ? 'border-red-500' : 'border-yellow-500' }} shadow-sm rounded-r-lg p-4 flex justify-between items-center">
                    <div>
                        <div class="flex items-center">
                            <h3 class="text-lg font-medium text-gray-900">{{ $issue['type'] }}</h3>
                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $issue['severity'] == 'high' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($issue['severity']) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $issue['message'] }}</p>
                    </div>
                    @if($issue['link'] && $issue['link'] != '#')
                        <a href="{{ $issue['link'] }}" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Review &rarr;</a>
                    @else
                        <span class="text-gray-400 text-sm">No direct link</span>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <!-- Heroicon name: solid/check-circle -->
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">All systems operational</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p>No blatant discrepancies found in Sales, Loans, or Payments.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
