<?php

/*
|--------------------------------------------------------------------------
| MyFatoorah (official myfatoorah/laravel-package) configuration
|--------------------------------------------------------------------------
|
| Consumed by the package's library classes (MyFatoorahPayment,
| MyFatoorahPaymentStatus) via config('myfatoorah.*'). See:
| https://github.com/MyFatoorah/laravel-package
|
*/

return [
    // API token — test: https://myfatoorah.readme.io/docs/test-token
    'api_key' => env('MYFATOORAH_API_KEY', ''),

    // true = test environment, false = live.
    'is_test' => (bool) env('MYFATOORAH_IS_TEST', true),

    // Vendor country ISO: KWT, SAU, ARE, QAT, BHR, OMN, JOD, or EGY.
    'vc_code' => env('MYFATOORAH_COUNTRY', 'KWT'),

    // Display currency for the created invoice (e.g. KWD, SAR, AED, EGP).
    'currency' => env('MYFATOORAH_CURRENCY', 'KWD'),

    // Webhook signing secret from your MyFatoorah account settings.
    'webhook_secret_key' => env('MYFATOORAH_WEBHOOK_SECRET', ''),

    'save_card' => (bool) env('MYFATOORAH_SAVE_CARD', false),
    'register_apple_pay' => false,
    'supplier_code' => null,
];
