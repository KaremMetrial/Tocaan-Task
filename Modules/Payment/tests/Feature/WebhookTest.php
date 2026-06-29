<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_webhook_updates_the_matching_payment_status(): void
    {
        $order = Order::factory()->confirmed()->create();
        $payment = Payment::factory()->for($order)->create([
            'method' => 'credit_card',
            'status' => 'pending',
            'transaction_reference' => 'CC-ABC123',
        ]);

        // No auth header required — the endpoint is public.
        $this->postJson('/api/payments/webhook/credit_card', [
            'reference' => 'CC-ABC123',
            'status' => 'successful',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'successful');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'successful',
        ]);
    }

    public function test_a_webhook_for_an_unknown_gateway_is_rejected(): void
    {
        $this->postJson('/api/payments/webhook/bitcoin', [
            'reference' => 'X-1',
            'status' => 'successful',
        ])->assertStatus(422);
    }

    public function test_a_webhook_for_an_unknown_reference_is_rejected(): void
    {
        $this->postJson('/api/payments/webhook/paypal', [
            'reference' => 'PP-DOESNOTEXIST',
            'status' => 'successful',
        ])->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
