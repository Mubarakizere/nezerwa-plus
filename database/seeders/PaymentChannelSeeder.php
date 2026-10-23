<?php

namespace Database\Seeders;

use App\Models\PaymentChannel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            'Cash',
            'Bank',
            'Momo',
            'Mobile',
            'Mobile Money',
            'Supplier Debt Offset',
        ];

        foreach ($channels as $channel) {
            PaymentChannel::firstOrCreate(
                ['slug' => Str::slug($channel)],
                [
                    'name' => $channel,
                    'is_active' => true,
                ]
            );
        }
    }
}
