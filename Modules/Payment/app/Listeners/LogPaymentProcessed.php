<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Payment\Events\PaymentProcessed;

/**
 * Records an audit trail entry whenever a payment is processed.
 *
 * Synchronous by design (QUEUE_CONNECTION=sync); implement ShouldQueue to move
 * this off the request cycle once a queue worker is available.
 */
class LogPaymentProcessed
{
    public function handle(PaymentProcessed $event): void
    {
        $payment = $event->payment;

        Log::info('Payment processed', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'method' => $payment->method,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'transaction_reference' => $payment->transaction_reference,
        ]);
    }
}
