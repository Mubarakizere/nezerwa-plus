<?php

return [
    'name'          => env('COMPANY_NAME', config('app.name')),
    'address_line1' => env('COMPANY_ADDRESS_LINE1', ''),
    'address_line2' => env('COMPANY_ADDRESS_LINE2', ''),
    'phone'         => env('COMPANY_PHONE', ''),
    'email'         => env('COMPANY_EMAIL', ''),
    'tax_id'        => env('COMPANY_TAX_ID', ''),
];
