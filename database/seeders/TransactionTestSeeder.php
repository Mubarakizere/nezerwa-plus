<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Purchase;

class TransactionTestSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure at least one test user exists
        $user = User::first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create a fake customer (from factory)
        $customer = Customer::factory()->create();

        // Create a fake supplier (from factory)
        $supplier = Supplier::factory()->create();

        // Create a Sale -> auto-generates a CREDIT transaction via observer
        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total_amount' => 5000,
            'sale_date' => now(), // required (NOT NULL)
        ]);

        // Create a Purchase -> auto-generates a DEBIT transaction via observer
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'total_amount' => 3000,
            'purchase_date' => now(), // required (NOT NULL)
        ]);

        $this->command->info('✅ TransactionTestSeeder completed: Sale & Purchase created, Transactions auto-generated!');
    }
}
