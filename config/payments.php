<?php

return [
    'transfer' => [
        'bank_name' => env('PAYMENT_TRANSFER_BANK_NAME'),
        'account_number' => env('PAYMENT_TRANSFER_ACCOUNT_NUMBER'),
        'account_name' => env('PAYMENT_TRANSFER_ACCOUNT_NAME'),
    ],

    'qris' => [
        'merchant_name' => env('PAYMENT_QRIS_MERCHANT_NAME', 'Forkis'),
        'merchant_city' => env('PAYMENT_QRIS_MERCHANT_CITY', 'Bekasi'),

        /*
         * Static merchant QRIS EMV payload from the acquirer/provider.
         * When present, the app builds a dynamic amount/reference payload.
         */
        'payload' => env('PAYMENT_QRIS_PAYLOAD'),

        /*
         * Optional pre-rendered QRIS image URL from the provider.
         * Used when no payload is available.
         */
        'image_url' => env('PAYMENT_QRIS_IMAGE_URL'),

        /*
         * QR image rendering endpoint for configured payloads.
         * The endpoint must accept size and data query parameters.
         */
        'generator_url' => env('PAYMENT_QRIS_GENERATOR_URL', 'https://api.qrserver.com/v1/create-qr-code/'),
        'generator_size' => (int) env('PAYMENT_QRIS_GENERATOR_SIZE', 320),
    ],
];
