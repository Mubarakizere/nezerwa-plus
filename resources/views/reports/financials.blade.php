@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Financial Overview</h1>
        <div class="text-sm text-gray-500">
            Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white p-4 rounded-lg shadow mb-8">
        <form method="GET" action="{{ route('audit.financials') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Update Report</button>
        </form>
    </div>

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Sales Card -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <h3 class="text-lg font-medium text-gray-900">Total Sales</h3>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($totalSales) }} RWF</p>
            <div class="mt-2 text-sm text-gray-500">
                Received: <span class="font-bold text-gray-700">{{ number_format($totalReceived) }}</span>
            </div>
            <div class="mt-1 text-sm text-gray-500">
                Profit: <span class="font-bold text-green-700">+{{ number_format($totalProfit) }}</span>
            </div>
        </div>

        <!-- Purchases Card -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
            <h3 class="text-lg font-medium text-gray-900">Total Purchases</h3>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format($totalPurchases) }} RWF</p>
            <div class="mt-2 text-sm text-gray-500">
                Expenses recorded in this period.
            </div>
        </div>

        <!-- Loans Card -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <h3 class="text-lg font-medium text-gray-900">Loans Activity</h3>
            <div class="space-y-2 mt-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Given (Receivable):</span>
                    <span class="font-bold text-gray-900">{{ number_format($loansGiven) }} RWF</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Taken (Payable):</span>
                    <span class="font-bold text-gray-900">{{ number_format($loansTaken) }} RWF</span>
                </div>
                <div class="pt-2 border-t mt-2 flex justify-between font-bold">
                    <span>Net Impact:</span>
                    <span class="{{ ($loansGiven - $loansTaken) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($loansGiven - $loansTaken) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
