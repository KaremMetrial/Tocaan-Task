<?php

declare(strict_types=1);

namespace Modules\Order\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->withToken(auth('api')->login($user));

        return $user;
    }

    public function test_orders_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/orders')->assertStatus(401);
    }

    public function test_it_creates_an_order_and_calculates_the_total_server_side(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'buyer@acme.test',
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'price' => 10.50], // 21.00
                ['product_name' => 'Gadget', 'quantity' => 1, 'price' => 5.00],  //  5.00
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', '26.00')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', ['total' => '26.00']);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_it_validates_order_creation(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/orders', ['customer_name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'customer_email', 'items']);
    }

    public function test_it_lists_and_filters_orders_by_status(): void
    {
        $user = $this->actingAsUser();
        Order::factory()->count(2)->create(['user_id' => $user->id]);
        Order::factory()->confirmed()->count(3)->create(['user_id' => $user->id]);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 5);

        $this->getJson('/api/orders?status=confirmed')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_only_returns_own_orders(): void
    {
        $user = $this->actingAsUser();
        Order::factory()->count(2)->create(['user_id' => $user->id]);
        Order::factory()->count(3)->create(); // other users' orders

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_it_updates_an_order_and_recalculates_the_total(): void
    {
        $user = $this->actingAsUser();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $order->items()->create(['product_name' => 'Old', 'quantity' => 1, 'price' => 1]);

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
            'items' => [
                ['product_name' => 'New', 'quantity' => 3, 'price' => 4], // 12.00
            ],
        ])->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.total', '12.00');

        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_it_deletes_an_order_without_payments(): void
    {
        $user = $this->actingAsUser();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/orders/{$order->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_it_cannot_delete_an_order_that_has_payments(): void
    {
        $user = $this->actingAsUser();
        $order = Order::factory()->confirmed()->create(['user_id' => $user->id]);
        Payment::factory()->successful()->create(['order_id' => $order->id]);

        $this->deleteJson("/api/orders/{$order->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    // ---------------------------------------------------------------
    // Authorization: ownership enforcement
    // ---------------------------------------------------------------

    public function test_user_cannot_view_another_users_order(): void
    {
        $this->actingAsUser();
        $other = Order::factory()->create(); // belongs to a different user

        $this->getJson("/api/orders/{$other->id}")
            ->assertStatus(403);
    }

    public function test_user_cannot_update_another_users_order(): void
    {
        $this->actingAsUser();
        $other = Order::factory()->create();

        $this->putJson("/api/orders/{$other->id}", ['customer_name' => 'Hacked'])
            ->assertStatus(403);
    }

    public function test_user_cannot_delete_another_users_order(): void
    {
        $this->actingAsUser();
        $other = Order::factory()->create();

        $this->deleteJson("/api/orders/{$other->id}")
            ->assertStatus(403);
    }
}
