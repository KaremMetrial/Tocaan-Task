<?php

declare(strict_types=1);

namespace Modules\Order\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'status' => OrderStatus::Pending,
            'total' => 0,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Cancelled]);
    }
}
