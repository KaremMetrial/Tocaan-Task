<?php

declare(strict_types=1);

namespace Modules\Core\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Base class for per-model query filters.
 *
 * Each query-string key is mapped to a method on the concrete filter: a request
 * like `?status=confirmed&method=paypal` calls `status('confirmed')` and
 * `method('paypal')` when those methods exist. Unknown keys are ignored, so the
 * public surface is exactly the methods the subclass chooses to expose.
 *
 * Subclass per model (OrderFilter, PaymentFilter, ...) and apply via the
 * Filterable trait's `scopeFilter()`.
 */
abstract class QueryFilter
{
    /**
     * The query builder currently being decorated.
     *
     * @var Builder<Model>
     */
    protected Builder $builder;

    public function __construct(protected readonly Request $request) {}

    /**
     * Apply every matching, non-empty filter to the builder.
     *
     * @param  Builder<Model>  $builder
     * @return Builder<Model>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->activeFilters() as $name => $value) {
            $method = Str::camel($name);

            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this->builder;
    }

    /**
     * Query-string parameters that carry a usable (non-empty) value.
     *
     * @return array<string, mixed>
     */
    protected function activeFilters(): array
    {
        return array_filter(
            $this->request->query(),
            static fn ($value) => $value !== null && $value !== '',
        );
    }
}
