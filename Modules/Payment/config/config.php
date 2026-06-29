<?php

use Modules\Payment\Gateways\CreditCardGateway;
use Modules\Payment\Gateways\MyFatoorahGateway;
use Modules\Payment\Gateways\PaypalGateway;

return [
    'name' => 'Payment',

    /*
    |--------------------------------------------------------------------------
    | Registered Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Each entry maps to a class implementing the PaymentGateway contract.
    | The PaymentServiceProvider instantiates and registers each one into the
    | PaymentGatewayManager, keyed by its key().
    |
    | To add a new gateway: create a class implementing PaymentGateway and add
    | it to this array. No other code changes are required (Open/Closed).
    |
    */
    'gateways' => [
        CreditCardGateway::class,
        PaypalGateway::class,
        MyFatoorahGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Credentials
    |--------------------------------------------------------------------------
    |
    | Per-gateway configuration (API keys, secrets) sourced from .env. Injected
    | into the gateway constructor keyed by the gateway's key().
    |
    */
    'credentials' => [
        'credit_card' => [
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'webhook_secret' => env('CREDIT_CARD_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'webhook_secret' => env('PAYPAL_WEBHOOK_SECRET'),
        ],
        // MyFatoorah credentials live in config/myfatoorah.php (official package).
    ],
];
