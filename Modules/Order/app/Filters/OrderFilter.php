<?php

declare(strict_types=1);

namespace Modules\Order\Filters;

use Modules\Core\Filters\QueryFilter;

/**
 * Query filters for the Order model. Each public method maps to a query-string
 * key: ?status=confirmed, ?customer=acme, ?email=buyer@acme.test.
 */
class OrderFilter extends QueryFilter
{
    public function status(string $value): void
    {
        $this->builder->where('status', $value);
    }

    public function customer(string $value): void
    {
        $this->builder->where('customer_name', 'like', "%{$value}%");
    }

    public function email(string $value): void
    {
        $this->builder->where('customer_email', 'like', "%{$value}%");
    }
}
