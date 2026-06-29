<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->withToken(auth('api')->login($this->user));
    }

    public function test_it_processes_a_payment_for_a_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id, 'total' => 99.99]);

        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'credit_card'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.method', 'credit_card')
            ->assertJsonPath('data.amount', '99.99');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'successful',
        ]);
    }

    public function test_it_rejects_payment_for_a_non_confirmed_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]); // pending

        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'credit_card'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_it_rejects_an_unsupported_gateway(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'bitcoin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['method']);
    }

    public function test_it_lists_payments_for_an_order(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'paypal'])->assertCreated();

        $this->getJson("/api/orders/{$order->id}/payments")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.method', 'paypal');
    }

    public function test_it_lists_all_payments_scoped_to_authenticated_user(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'credit_card'])->assertCreated();

        // Another user's payment should NOT appear.
        $otherOrder = Order::factory()->confirmed()->create();
        Payment::factory()->successful()->create(['order_id' => $otherOrder->id]);

        $this->getJson('/api/payments')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    // ---------------------------------------------------------------
    // Authorization: ownership enforcement
    // ---------------------------------------------------------------

    public function test_user_cannot_pay_for_another_users_order(): void
    {
        $other = Order::factory()->confirmed()->create(); // belongs to a different user

        $this->postJson("/api/orders/{$other->id}/payments", ['method' => 'credit_card'])
            ->assertStatus(403);
    }

    public function test_user_cannot_view_payments_for_another_users_order(): void
    {
        $other = Order::factory()->confirmed()->create();

        $this->getJson("/api/orders/{$other->id}/payments")
            ->assertStatus(403);
    }
}
