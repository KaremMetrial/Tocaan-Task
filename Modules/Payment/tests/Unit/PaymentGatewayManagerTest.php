<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit;

use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\PaymentResult;
use Modules\Payment\Contracts\WebhookResult;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Exceptions\UnsupportedGatewayException;
use Modules\Payment\Gateways\CreditCardGateway;
use Modules\Payment\Gateways\PaypalGateway;
use Modules\Payment\PaymentGatewayManager;
use PHPUnit\Framework\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    private function manager(): PaymentGatewayManager
    {
        $manager = new PaymentGatewayManager;
        $manager->register(new CreditCardGateway);
        $manager->register(new PaypalGateway);

        return $manager;
    }

    public function test_it_resolves_a_registered_gateway_by_key(): void
    {
        $manager = $this->manager();

        $this->assertInstanceOf(CreditCardGateway::class, $manager->resolve('credit_card'));
        $this->assertInstanceOf(PaypalGateway::class, $manager->resolve('paypal'));
        $this->assertInstanceOf(PaymentGateway::class, $manager->resolve('paypal'));
    }

    public function test_it_reports_supported_gateways(): void
    {
        $this->assertSame(['credit_card', 'paypal'], $this->manager()->supported());
        $this->assertTrue($this->manager()->has('paypal'));
        $this->assertFalse($this->manager()->has('bitcoin'));
    }

    public function test_it_throws_for_an_unknown_gateway(): void
    {
        $this->expectException(UnsupportedGatewayException::class);

        $this->manager()->resolve('bitcoin');
    }

    public function test_a_newly_registered_gateway_is_resolvable_without_touching_the_manager(): void
    {
        // Demonstrates Open/Closed: a brand-new strategy plugs in via register().
        $custom = new class implements PaymentGateway
        {
            public function key(): string
            {
                return 'stripe';
            }

            public function charge($order, array $payload): PaymentResult
            {
                return PaymentResult::success('STRIPE-TEST');
            }

            public function verifyWebhook($request): bool
            {
                return true;
            }

            public function parseWebhook($request): WebhookResult
            {
                return new WebhookResult('STRIPE-TEST', PaymentStatus::Successful);
            }
        };

        $manager = $this->manager();
        $manager->register($custom);

        $this->assertSame($custom, $manager->resolve('stripe'));
    }
}
