<?php

declare(strict_types=1);

namespace Modules\Order\Policies;

use App\Models\User;
use Modules\Order\Models\Order;

/**
 * Enforces object-level authorization for orders.
 *
 * Every authenticated user may create orders; only the owning user may
 * view, update, or delete their own orders.
 */
class OrderPolicy
{
    /**
     * Any authenticated user may view the list (the query is scoped
     * to their own orders in the repository layer).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}
