<?php

declare(strict_types=1);

namespace Modules\Order\Filters;

use Modules\Core\Filters\QueryFilter;

/**
 * Query filters for the OrderItem model. Ready for any future item-listing
 * endpoint: ?product=widget, ?order_id=5.
 */
class OrderItemFilter extends QueryFilter
{
    public function product(string $value): void
    {
        $this->builder->where('product_name', 'like', "%{$value}%");
    }

    public function orderId(int|string $value): void
    {
        $this->builder->where('order_id', (int) $value);
    }
}
