<?php

declare(strict_types=1);

namespace Modules\Payment\Filters;

use Modules\Core\Filters\QueryFilter;

/**
 * Query filters for the Payment model: ?status=successful, ?method=paypal,
 * ?order_id=5.
 */
class PaymentFilter extends QueryFilter
{
    public function status(string $value): void
    {
        $this->builder->where('status', $value);
    }

    public function method(string $value): void
    {
        $this->builder->where('method', $value);
    }

    public function orderId(int|string $value): void
    {
        $this->builder->where('order_id', (int) $value);
    }
}
