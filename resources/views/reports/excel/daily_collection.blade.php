<table>
    {{-- Header --}}
    <tr>
        <td colspan="5" style="font-weight: bold; font-size: 14pt;">M BEVERAGE AND SERVICES</td>
    </tr>
    <tr>
        <td colspan="5" style="font-weight: bold; font-size: 12pt;">DAILY REPORT FORMAT</td>
    </tr>
    <tr>
        <td colspan="5" style="font-weight: bold; font-size: 11pt;">DAILY SALES REPORT SUMMARY</td>
    </tr>
    <tr>
        <td colspan="5" style="font-style: italic; color: #555555;">Date: {{ $startDateInput }} @if($mode === 'range') to {{ $endDateInput }} @endif</td>
    </tr>
    <tr><td></td></tr>

    {{-- Sales Recorded Table --}}
    <tr>
        <td colspan="5" style="font-weight: bold; font-size: 11pt;">Sales recorded</td>
    </tr>
    <tr style="background-color: #f3f4f6; font-weight: bold;">
        <td style="border: 1px solid #000000; width: 45px;">Category</td>
        <td style="border: 1px solid #000000; text-align: right; width: 20px;">Amount (RWF)</td>
        <td colspan="3" style="border: 1px solid #000000;">Notes / Formula</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000;">Ledger Cash Sales</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $ledgerCashSales }}</td>
        <td colspan="3" style="border: 1px solid #000000;">System cash sales</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000; color: #dc2626;">Ledger MoMo Sales</td>
        <td style="border: 1px solid #000000; text-align: right; color: #dc2626;">{{ $ledgerMoMoSales ?? $ledgerMomoSales }}</td>
        <td colspan="3" style="border: 1px solid #000000;">System Mobile Money sales</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000; color: #dc2626;">Ledger Credit Sales</td>
        <td style="border: 1px solid #000000; text-align: right; color: #dc2626;">{{ $ledgerCreditSales }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Unpaid sales balances</td>
    </tr>
    <tr style="font-weight: bold; background-color: #e5e7eb;">
        <td style="border: 1px solid #000000;">Total Ledger Sales</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $totalLedgerSales }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Cash + MoMo + Credit</td>
    </tr>
    <tr><td></td></tr>

    {{-- DAILY COLLECTION BREAKDOWN HEADER --}}
    <tr>
        <td colspan="5" style="font-weight: bold; font-size: 13pt; text-align: center;">DAILY COLLECTION BREAKDOWN</td>
    </tr>
    <tr><td></td></tr>

    {{-- A. Cash on hand report --}}
    <tr style="background-color: #000000; color: #ffffff; font-weight: bold;">
        <td colspan="5" style="border: 2px solid #000000;">Cash on hand report</td>
    </tr>
    <tr style="font-weight: bold; background-color: #f3f4f6;">
        <td style="border: 1px solid #000000;">Item</td>
        <td style="border: 1px solid #000000; text-align: right;">Amount (RWF)</td>
        <td colspan="3" style="border: 1px solid #000000;">Notes</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000; font-weight: bold;">opening balance</td>
        <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $cashOpeningBalance }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Previous day closing balance</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000;">cash sales by system</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $cashSalesSystem }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Recorded cash sales</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000;">Cash Received from Previous Credit Customers</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $totalCashDebtReceived }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Debt collections in cash</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000;">Other Cash Received</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $otherCashReceived }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Other cash in</td>
    </tr>
    <tr style="font-weight: bold; background-color: #e5e7eb;">
        <td style="border: 1px solid #000000;">Total Cash available</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $totalCashAvailable }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Sum of inflows</td>
    </tr>
    <tr style="font-weight: bold; background-color: #d1d5db;">
        <td colspan="5" style="border: 1px solid #000000;">PAYMENT PAYMENT by CASH</td>
    </tr>
    @foreach($cashExpenseItems as $label => $amt)
    <tr>
        <td style="border: 1px solid #000000;">{{ $label }}</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $amt }}</td>
        <td colspan="3" style="border: 1px solid #000000;"></td>
    </tr>
    @endforeach
    <tr>
        <td style="border: 1px solid #000000; font-weight: bold;">CASH DEPOSIT</td>
        <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $cashDeposit }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Bank cash deposits</td>
    </tr>
    <tr style="font-weight: bold; background-color: #000000; color: #ffffff;">
        <td style="border: 2px solid #000000;">CLOSING BALANCE</td>
        <td style="border: 2px solid #000000; text-align: right;">{{ $cashClosingBalance }}</td>
        <td colspan="3" style="border: 2px solid #000000;">Total Available - Total Outflows</td>
    </tr>
    <tr><td></td></tr>

    {{-- B. Mobile Money Collections --}}
    <tr style="background-color: #000000; color: #ffffff; font-weight: bold;">
        <td colspan="5" style="border: 2px solid #000000;">B. Mobile Money Collections</td>
    </tr>
    <tr style="font-weight: bold; background-color: #f3f4f6;">
        <td style="border: 1px solid #000000;">Item</td>
        <td style="border: 1px solid #000000; text-align: right;">Amount (RWF)</td>
        <td colspan="3" style="border: 1px solid #000000;">Notes</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000; font-weight: bold;">OPEING BALANCE</td>
        <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $momoOpeningBalance }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Previous day closing MoMo balance</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000;">MoMo Sales BY system</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $momoSalesSystem }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Recorded MoMo sales</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000;">MoMo Received from Previous Credit Customers</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $totalMomoDebtReceived }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Debt collections in MoMo</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000;">Other MoMo Received</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $otherMomoReceived }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Other MoMo in</td>
    </tr>
    <tr style="font-weight: bold; background-color: #e5e7eb;">
        <td style="border: 1px solid #000000;">Total MoMo AVALABLES</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $totalMomoAvailable }}</td>
        <td colspan="3" style="border: 1px solid #000000;">Sum of MoMo inflows</td>
    </tr>
    <tr style="font-weight: bold; background-color: #d1d5db;">
        <td colspan="5" style="border: 1px solid #000000;">PAYMENT by MOMO</td>
    </tr>
    @foreach($momoExpenseItems as $label => $amt)
    <tr>
        <td style="border: 1px solid #000000;">{{ $label }}</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $amt }}</td>
        <td colspan="3" style="border: 1px solid #000000;"></td>
    </tr>
    @endforeach
    <tr>
        <td style="border: 1px solid #000000; font-weight: bold;">Total transfer</td>
        <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ $totalMomoTransfers }}</td>
        <td colspan="3" style="border: 1px solid #000000;">MoMo transfers out</td>
    </tr>
    <tr style="font-weight: bold; background-color: #000000; color: #ffffff;">
        <td style="border: 2px solid #000000;">CLOSING BALANCE ON MOMO</td>
        <td style="border: 2px solid #000000; text-align: right;">{{ $momoClosingBalance }}</td>
        <td colspan="3" style="border: 2px solid #000000;">Total MoMo Available - Total MoMo Outflows</td>
    </tr>
    <tr><td></td></tr>

    {{-- C. BANK DEPOSITS --}}
    <tr style="font-weight: bold; background-color: #f3f4f6;">
        <td style="border: 1px solid #000000;">BANK DEPOSITS</td>
        <td style="border: 1px solid #000000;">Amount (RWF)</td>
        <td style="border: 1px solid #000000;">Mode (Cash/MoMo)</td>
        <td style="border: 1px solid #000000;">Bank</td>
        <td style="border: 1px solid #000000;">Deposit Slip No.</td>
    </tr>
    @forelse($bankDeposits as $deposit)
    <tr>
        <td style="border: 1px solid #000000;">{{ $deposit->notes ?? 'Bank Deposit' }}</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $deposit->amount }}</td>
        <td style="border: 1px solid #000000;">{{ strtoupper($deposit->method ?? 'CSH') }}</td>
        <td style="border: 1px solid #000000;">BK / I&M / Equity</td>
        <td style="border: 1px solid #000000;">{{ $deposit->id }}</td>
    </tr>
    @empty
    <tr>
        <td style="border: 1px solid #000000;">No recorded deposits</td>
        <td style="border: 1px solid #000000; text-align: right;">0</td>
        <td style="border: 1px solid #000000;">CSH</td>
        <td style="border: 1px solid #000000;"></td>
        <td style="border: 1px solid #000000;"></td>
    </tr>
    @endforelse
    <tr style="font-weight: bold; background-color: #e5e7eb;">
        <td style="border: 1px solid #000000;">Total Deposits</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $totalBankDeposits }}</td>
        <td colspan="3" style="border: 1px solid #000000;"></td>
    </tr>
    <tr><td></td></tr>

    {{-- UNTRADED SUMMARY --}}
    <tr>
        <td style="border: 1px solid #000000; font-weight: bold;">MOMO NOT TRADED</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $momoNotTraded }}</td>
        <td colspan="3"></td>
    </tr>
    <tr>
        <td style="border: 1px solid #000000; font-weight: bold;">CASH NOT TRADED</td>
        <td style="border: 1px solid #000000; text-align: right;">{{ $cashNotTraded }}</td>
        <td colspan="3"></td>
    </tr>
</table>
