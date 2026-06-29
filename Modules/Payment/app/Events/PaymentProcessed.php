<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Models\Payment;

/**
 * Fired after a payment has been processed by a gateway and persisted.
 *
 * Carries the resulting Payment (whose status may be successful or failed,
 * per the gateway result) so listeners can send receipts, notify fulfilment,
 * or emit metrics without touching PaymentService.
 */
class PaymentProcessed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Payment $payment) {}
}
