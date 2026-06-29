<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways;

/**
 * Simulated credit-card gateway.
 *
 * In a real integration this would call an SDK/HTTP client using the
 * credentials injected via the constructor (see SimulatedGateway).
 */
class CreditCardGateway extends SimulatedGateway
{
    public function key(): string
    {
        return 'credit_card';
    }

    protected function referencePrefix(): string
    {
        return 'CC';
    }

    protected function successMessage(): string
    {
        return 'Credit card charged successfully.';
    }
}
