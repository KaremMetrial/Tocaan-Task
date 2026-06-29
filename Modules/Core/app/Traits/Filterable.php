<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Filters\QueryFilter;

/**
 * Gives a model a `filter()` query scope that delegates to a QueryFilter.
 *
 * Usage:
 *   Order::query()->filter($orderFilter)->paginate();
 */
trait Filterable
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFilter(Builder $query, QueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
