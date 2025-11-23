@extends('layouts.pdf')

@section('title', 'Loan Summary Report')

@section('header-meta')
    <table class="meta-table">
        <tr>
            <td class="text-gray-500">Report Date:</td>
            <td class="font-bold">{{ now()->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="text-gray-500">Total Loans:</td>
            <td>{{ $loans->count() }}</td>
        </tr>
    </table>
@endsection

@section('content')
    <div class="section">
        <h3>Summary</h3>
        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="flex: 1; background: #f9fafb; padding: 10px; border-radius: 4px;">
                <div class="text-xs text-gray-500">Total Given</div>
                <div class="font-bold text-success">{{ number_format($summary['totalGiven'], 2) }}</div>
            </div>
            <div style="flex: 1; background: #f9fafb; padding: 10px; border-radius: 4px;">
                <div class="text-xs text-gray-500">Total Taken</div>
                <div class="font-bold text-danger">{{ number_format($summary['totalTaken'], 2) }}</div>
            </div>
            <div style="flex: 1; background: #f9fafb; padding: 10px; border-radius: 4px;">
                <div class="text-xs text-gray-500">Pending Amount</div>
                <div class="font-bold text-primary">{{ number_format($summary['totalPending'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Party</th>
                    <th class="num">Amount</th>
                    <th class="num">Paid</th>
                    <th class="num">Remaining</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loans as $loan)
                    @php
                        $paid = $loan->payments_sum_amount ?? 0;
                        $rem = max(($loan->amount ?? 0) - $paid, 0);
                        $party = $loan->type === 'given' ? optional($loan->customer)->name : optional($loan->supplier)->name;
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($loan->loan_date)->format('d M Y') }}</td>
                        <td>
                            <span class="{{ $loan->type === 'given' ? 'text-success' : 'text-danger' }}">
                                {{ ucfirst($loan->type) }}
                            </span>
                        </td>
                        <td>{{ $party ?? '—' }}</td>
                        <td class="num">{{ number_format($loan->amount, 2) }}</td>
                        <td class="num text-gray-500">{{ number_format($paid, 2) }}</td>
                        <td class="num font-bold">{{ number_format($rem, 2) }}</td>
                        <td>
                            <span class="{{ $loan->status === 'paid' ? 'text-success' : 'text-danger' }} text-xs uppercase font-bold">
                                {{ $loan->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
