<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\MyFatoorahClient;
use Modules\Payment\Models\Payment;
use Tests\TestCase;

class MyFatoorahTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->withToken(auth('api')->login($this->user));

        // Swap the official-package-backed client for a fake (the real library
        // talks to MyFatoorah over cURL, so it is never hit in tests).
        $this->app->instance(MyFatoorahClient::class, new class implements MyFatoorahClient
        {
            public string $invoiceStatus = 'Paid';

            public function sendPayment(Order $order): array
            {
                return [
                    'invoiceId' => '123456',
                    'invoiceUrl' => 'https://apitest.myfatoorah.com/KW/ie/abc123',
                ];
            }

            public function getInvoiceStatus(string $invoiceId): string
            {
                return $this->invoiceStatus;
            }
        });
    }

    public function test_it_initiates_a_pending_myfatoorah_payment_with_a_redirect_url(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id, 'total' => 50.00]);

        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'myfatoorah'])
            ->assertCreated()
            ->assertJsonPath('data.method', 'myfatoorah')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.transaction_reference', '123456')
            ->assertJsonPath('data.redirect_url', 'https://apitest.myfatoorah.com/KW/ie/abc123');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'myfatoorah',
            'status' => 'pending',
            'transaction_reference' => '123456',
        ]);
    }

    public function test_the_webhook_confirms_the_payment_via_a_status_lookup(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->for($order)->create([
            'method' => 'myfatoorah',
            'status' => 'pending',
            'transaction_reference' => '123456',
        ]);

        // MyFatoorah v2 callback shape; the status is verified server-side.
        $this->postJson('/api/payments/webhook/myfatoorah', [
            'Data' => ['Invoice' => ['Id' => '123456']],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'successful');

        $this->assertDatabaseHas('payments', [
            'transaction_reference' => '123456',
            'status' => 'successful',
        ]);
    }
}
