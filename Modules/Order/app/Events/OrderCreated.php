<?php

declare(strict_types=1);

namespace Modules\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Order\Models\Order;

/**
 * Fired after an order has been persisted (with its line items).
 *
 * Lets other parts of the system react to a new order — notifications,
 * analytics, fulfilment hooks — without coupling them to OrderService.
 */
class OrderCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}
}
