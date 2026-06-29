<?php

declare(strict_types=1);

namespace Modules\Payment;

use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Exceptions\UnsupportedGatewayException;

/**
 * Registry/factory that resolves a payment method key to its gateway strategy.
 *
 * This is the single place that knows the gateway map, populated from config
 * by the PaymentServiceProvider. Adding a gateway never touches this class.
 */
class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->key()] = $gateway;
    }

    /**
     * @throws UnsupportedGatewayException
     */
    public function resolve(string $method): PaymentGateway
    {
        return $this->gateways[$method]
            ?? throw new UnsupportedGatewayException($method);
    }

    public function has(string $method): bool
    {
        return isset($this->gateways[$method]);
    }

    /**
     * Keys of all registered gateways (handy for validation messages).
     *
     * @return array<int, string>
     */
    public function supported(): array
    {
        return array_keys($this->gateways);
    }
}
