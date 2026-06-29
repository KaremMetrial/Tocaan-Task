<?php

declare(strict_types=1);

namespace Modules\Order\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Order\Filters\OrderFilter;
use Modules\Order\Models\Order;

/**
 * Eloquent-backed implementation of the OrderRepository contract.
 * Centralizes all order persistence + query logic (DRY).
 */
class EloquentOrderRepository implements OrderRepository
{
    public function paginate(OrderFilter $filter, int $perPage = 15, ?int $userId = null): LengthAwarePaginator
    {
        return Order::query()
            ->with('items')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->filter($filter)
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): ?Order
    {
        return Order::query()->with(['items', 'payments'])->find($id);
    }

    public function create(array $attributes, array $items): Order
    {
        return DB::transaction(function () use ($attributes, $items): Order {
            /** @var Order $order */
            $order = Order::query()->create($attributes);
            $order->items()->createMany($items);

            return $order->load('items');
        });
    }

    public function update(Order $order, array $attributes, ?array $items = null): Order
    {
        return DB::transaction(function () use ($order, $attributes, $items): Order {
            $order->update($attributes);

            if ($items !== null) {
                $order->items()->delete();
                $order->items()->createMany($items);
            }

            return $order->load('items');
        });
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }
}
