<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways;

/**
 * Simulated PayPal gateway.
 */
class PaypalGateway extends SimulatedGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    protected function referencePrefix(): string
    {
        return 'PP';
    }

    protected function successMessage(): string
    {
        return 'PayPal payment completed successfully.';
    }
}
