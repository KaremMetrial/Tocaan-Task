<?php

declare(strict_types=1);

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => fake()->randomElement(['credit_card', 'paypal']),
            'status' => PaymentStatus::Pending,
            'amount' => fake()->randomFloat(2, 10, 1000),
            'transaction_reference' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Successful,
            'transaction_reference' => strtoupper(fake()->bothify('TXN-########')),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::Failed]);
    }
}
