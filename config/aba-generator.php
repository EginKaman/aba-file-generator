<?php

declare(strict_types=1);

return [
    'bsb' => env('ABA_BSB'),
    'account_number' => env('ABA_ACCOUNT_NUMBER'),
    'bank_name' => env('ABA_BANK_NAME'),
    'user_name' => env('ABA_USER_NAME'),
    'remitter' => env('ABA_REMITTER'),
    'direct_entry_id' => env('ABA_DIRECT_ENTRY_ID'),
];
