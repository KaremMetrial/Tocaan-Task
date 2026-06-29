<?php

declare(strict_types=1);

namespace Modules\Payment\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Payment\Events\PaymentProcessed;
use Modules\Payment\Models\Payment;

/**
 * Model-lifecycle concerns for payments, registered via #[ObservedBy] on the
 * Payment model:
 *  - emit the PaymentProcessed domain event after a payment is persisted
 *  - record an audit line when a payment's status changes (e.g. via webhook)
 */
class PaymentObserver
{
    public function created(Payment $payment): void
    {
        PaymentProcessed::dispatch($payment);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('status')) {
            Log::info('Payment status changed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'from' => $payment->getOriginal('status'),
                'to' => $payment->status->value,
            ]);
        }
    }
}
