<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit;

use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Gateways\CreditCardGateway;
use Modules\Payment\Gateways\PaypalGateway;
use PHPUnit\Framework\TestCase;

class GatewayChargeTest extends TestCase
{
    public function test_credit_card_gateway_returns_a_successful_result_with_reference(): void
    {
        $result = (new CreditCardGateway)->charge($this->fakeOrder(), []);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(PaymentStatus::Successful, $result->status);
        $this->assertStringStartsWith('CC-', (string) $result->reference);
    }

    public function test_paypal_gateway_returns_a_successful_result_with_reference(): void
    {
        $result = (new PaypalGateway)->charge($this->fakeOrder(), []);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringStartsWith('PP-', (string) $result->reference);
    }

    /**
     * A lightweight order stand-in — gateways don't touch persistence.
     */
    private function fakeOrder(): Order
    {
        $order = new Order;
        $order->forceFill(['id' => 1, 'total' => 50.00]);

        return $order;
    }
}
