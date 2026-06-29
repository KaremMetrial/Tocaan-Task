<?php

declare(strict_types=1);

namespace Modules\Order\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Order\Filters\OrderFilter;
use Modules\Order\Models\Order;
use Modules\Order\Repositories\OrderRepository;

/**
 * Encapsulates order use-cases:
 *  - server-side total calculation (never trusts a client-sent total)
 *
 * Model-lifecycle concerns (the "order created" event and the "orders with
 * payments cannot be deleted" rule) live in OrderObserver.
 */
class OrderService
{
    public function __construct(private readonly OrderRepository $orders) {}

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(OrderFilter $filter, int $perPage = 15, ?int $userId = null): LengthAwarePaginator
    {
        return $this->orders->paginate($filter, $perPage, $userId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $userId, array $data): Order
    {
        $items = $data['items'];

        $attributes = [
            'user_id' => $userId,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'status' => $data['status'] ?? 'pending',
            'total' => $this->calculateTotal($items),
        ];

        return $this->orders->create($attributes, $items);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data): Order
    {
        $attributes = array_filter([
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn ($value) => $value !== null);

        $items = $data['items'] ?? null;

        // Recalculate the total whenever the line items change.
        if ($items !== null) {
            $attributes['total'] = $this->calculateTotal($items);
        }

        return $this->orders->update($order, $attributes, $items);
    }

    /**
     * Delete an order. The "has payments" guard is enforced by OrderObserver
     * on the model's `deleting` event.
     */
    public function delete(Order $order): void
    {
        $this->orders->delete($order);
    }

    /**
     * Sum of (quantity * unit price) across all line items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function calculateTotal(array $items): string
    {
        $total = 0.0;

        foreach ($items as $item) {
            $total += (float) $item['price'] * (int) $item['quantity'];
        }

        return number_format($total, 2, '.', '');
    }
}
