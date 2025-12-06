<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Loan;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;

class OpeningBalancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first() ?? User::factory()->create(['name' => 'Admin']);

        $this->seedDebtors($admin);
        $this->seedSales($admin);
        $this->seedCreditors($admin);
    }

    private function seedDebtors($user)
    {
        $debtors = [
            ['name' => 'Marriot', 'amount' => 2520000],
            ['name' => 'Senior office', 'amount' => 2242000],
            ['name' => 'Heaven hotel', 'amount' => 2110000],
            ['name' => 'Edigar', 'amount' => 373000],
            ['name' => 'Kayibanda', 'amount' => 417000],
            ['name' => 'Nyirabuyare', 'amount' => 234000], 
            ['name' => 'Lavana', 'amount' => 3081000],
            ['name' => 'Betty gisirikare', 'amount' => 45000],
            ['name' => 'Keneth', 'amount' => 103500],
            ['name' => 'Rongine', 'amount' => 60000],
            ['name' => 'Sweed', 'amount' => 40000], // 'Sweed' or 'Sweet'? Image says 'Sweed' or 'Seved'
            ['name' => 'Ruhigira', 'amount' => 170000],
            ['name' => 'Kultura', 'amount' => 240000],
        ];

        /*
            Debtors = Loans GIVEN (assets).
            We check if customer exists, else create.
            Create Loan (type=given, status=pending).
        */

        $this->command->info('Seeding Debtors...');

        foreach ($debtors as $d) {
            $customer = Customer::firstOrCreate(
                ['name' => $d['name']],
                ['email' => strtolower(str_replace(' ', '.', $d['name'])) . '@example.com'] // Dummy email
            );

            // Check if loan already exists to avoid duplicates if run twice
            $exists = Loan::where('customer_id', $customer->id)
                ->where('amount', $d['amount'])
                ->where('type', 'given')
                ->where('notes', 'Opening Balance - Debtor')
                ->exists();

            if (!$exists) {
                Loan::create([
                    'type'        => 'given',
                    'customer_id' => $customer->id,
                    'amount'      => $d['amount'],
                    'loan_date'   => Carbon::now()->subDays(1), // Assume yesterday or just generic opening date
                    'due_date'    => Carbon::now()->addDays(30),
                    'status'      => 'pending',
                    'notes'       => 'Opening Balance - Debtor',
                ]);
            }
        }
    }

    private function seedCreditors($user)
    {
        $creditors = [
            ['name' => 'Labdoga', 'amount' => 1920000],
            ['name' => 'Erinward', 'amount' => 7103700], // 6,832,800 + 270,900
            ['name' => 'New West gate', 'amount' => 2574000],
            ['name' => 'Umurindi leff', 'amount' => 341000], 
            ['name' => 'West gate', 'amount' => 220000],
            ['name' => 'M&G supermarket', 'amount' => 360000],
            ['name' => 'Paramount', 'amount' => 369000],
            ['name' => 'Gitare', 'amount' => 2188500],
        ];

        /*
            Creditors = Loans TAKEN (liabilities).
            We check if supplier exists, else create.
            Create Loan (type=taken, status=pending).
        */

        $this->command->info('Seeding Creditors...');

        foreach ($creditors as $c) {
            $supplier = Supplier::firstOrCreate(
                ['name' => $c['name']],
                ['email' => strtolower(str_replace(' ', '.', $c['name'])) . '@supplier.com']
            );

            $exists = Loan::where('supplier_id', $supplier->id)
                ->where('amount', $c['amount'])
                ->where('type', 'taken')
                ->where('notes', 'Opening Balance - Creditor')
                ->exists();

            if (!$exists) {
                Loan::create([
                    'type'        => 'taken',
                    'supplier_id' => $supplier->id,
                    'amount'      => $c['amount'],
                    'loan_date'   => Carbon::now()->subDays(1),
                    'due_date'    => Carbon::now()->addDays(30),
                    'status'      => 'pending',
                    'notes'       => 'Opening Balance - Creditor',
                ]);
            }
        }
    }

    private function seedSales($user)
    {
        /*
            Sales data:
            4/12/2025:
                m (momo) = 398,500
                c (cash) = 68,500
            5/12/2025:
                c (cash) = 125,000
                m (momo) = 2,595,000
        */

        $salesData = [
            [
                'date' => '2025-12-04',
                'items' => [
                    ['channel' => 'momo', 'amount' => 398500],
                    ['channel' => 'cash', 'amount' => 68500],
                ]
            ],
            [
                'date' => '2025-12-05',
                'items' => [
                    ['channel' => 'cash', 'amount' => 125000],
                    ['channel' => 'momo', 'amount' => 2595000],
                ]
            ]
        ];

        // Use a generic "Walk-in Customer"
        $customer = Customer::firstOrCreate(['name' => 'Walk-in Customer']);

        $this->command->info('Seeding Sales...');

        foreach ($salesData as $day) {
            foreach ($day['items'] as $saleEntry) {
                
                // Check for duplicates (same date, same amount, same channel)
                $exists = Sale::where('sale_date', $day['date'])
                    ->where('total_amount', $saleEntry['amount'])
                    ->where('payment_channel', $saleEntry['channel'])
                    ->exists();

                if (!$exists) {
                    Sale::create([
                        'customer_id'     => $customer->id,
                        'user_id'         => $user->id,
                        'sale_date'       => $day['date'],
                        'total_amount'    => $saleEntry['amount'],
                        'amount_paid'     => $saleEntry['amount'], // Fully paid
                        'payment_channel' => $saleEntry['channel'],
                        'method'          => $saleEntry['channel'], // repeated for reference
                        'status'          => 'completed',
                        'notes'           => 'Opening Balance Sale Import',
                    ]);
                }
            }
        }
    }
}
