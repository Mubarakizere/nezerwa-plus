<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = \App\Models\Sale::where('notes', 'Opening Balance Sale Import')->get();
echo "Imported Sales: " . $sales->count() . PHP_EOL;
foreach ($sales as $s) {
    echo "ID: {$s->id} | Date: {$s->sale_date->toDateString()} | Amount: {$s->total_amount} | Channel: {$s->payment_channel}" . PHP_EOL;
}

$debtors = \App\Models\Loan::where('type', 'given')->where('notes', 'Opening Balance - Debtor')->with('customer')->get();
echo "Imported Debtors: " . $debtors->count() . PHP_EOL;
foreach ($debtors as $d) {
    echo "Debtor: {$d->customer->name} | Amount: {$d->amount}" . PHP_EOL;
}

$creditors = \App\Models\Loan::where('type', 'taken')->where('notes', 'Opening Balance - Creditor')->with('supplier')->get();
echo "Imported Creditors: " . $creditors->count() . PHP_EOL;
foreach ($creditors as $c) {
    echo "Creditor: {$c->supplier->name} | Amount: {$c->amount}" . PHP_EOL;
}
