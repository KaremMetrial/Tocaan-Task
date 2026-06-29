<?php

declare(strict_types=1);

namespace Modules\Order\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Order\Filters\OrderFilter;
use Modules\Order\Models\Order;

/**
 * Persistence contract for orders (Dependency Inversion):
 * services depend on this interface, never on Eloquent directly.
 */
interface OrderRepository
{
    /**
     * Paginate orders, constrained by the given query filter.
     *
     * When $userId is provided, results are scoped to that user's orders.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(OrderFilter $filter, int $perPage = 15, ?int $userId = null): LengthAwarePaginator;

    public function find(int $id): ?Order;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $attributes, array $items): Order;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>|null  $items  Null = leave items untouched.
     */
    public function update(Order $order, array $attributes, ?array $items = null): Order;

    public function delete(Order $order): void;
}
