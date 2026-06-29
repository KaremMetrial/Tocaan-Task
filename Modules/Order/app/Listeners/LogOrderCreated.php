<?php

declare(strict_types=1);

namespace Modules\Order\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Order\Events\OrderCreated;

/**
 * Records an audit trail entry whenever a new order is created.
 *
 * Synchronous by design (QUEUE_CONNECTION=sync); implement ShouldQueue to move
 * this off the request cycle once a queue worker is available.
 */
class LogOrderCreated
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        Log::info('Order created', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'status' => $order->status->value,
            'total' => $order->total,
        ]);
    }
}
