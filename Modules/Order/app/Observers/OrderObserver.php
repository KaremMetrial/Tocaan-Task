<?php

declare(strict_types=1);

namespace Modules\Order\Observers;

use Modules\Core\Exceptions\BusinessValidationException;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Models\Order;

/**
 * Model-lifecycle concerns for orders, registered via #[ObservedBy] on the
 * Order model:
 *  - emit the OrderCreated domain event after persistence
 *  - enforce the "an order with payments cannot be deleted" business rule
 */
class OrderObserver
{
    public function created(Order $order): void
    {
        OrderCreated::dispatch($order);
    }

    /**
     * @throws BusinessValidationException
     */
    public function deleting(Order $order): void
    {
        if ($order->hasPayments()) {
            $count = $order->payments()->count();

            throw new BusinessValidationException(
                "Order #{$order->id} cannot be deleted because it has {$count} associated payment(s).",
            );
        }
    }
}
