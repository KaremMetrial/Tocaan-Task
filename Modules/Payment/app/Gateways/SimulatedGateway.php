<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways;

use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\PaymentResult;
use Modules\Payment\Gateways\Concerns\HandlesWebhook;

/**
 * Base for simulated gateways that authorize + capture instantly and return a
 * successful result with a prefixed reference.
 *
 * A real integration (e.g. MyFatoorahGateway) implements PaymentGateway
 * directly instead of extending this. Subclasses only declare their identity:
 * key(), referencePrefix(), and successMessage().
 */
abstract class SimulatedGateway implements PaymentGateway
{
    use HandlesWebhook;

    /**
     * @param  array<string, mixed>  $config  Scoped credentials (e.g. webhook_secret).
     */
    public function __construct(protected readonly array $config = []) {}

    public function charge(Order $order, array $payload): PaymentResult
    {
        $reference = $this->referencePrefix().'-'.strtoupper(bin2hex(random_bytes(6)));

        return PaymentResult::success($reference, $this->successMessage());
    }

    /**
     * Short uppercase prefix for generated transaction references (e.g. "CC").
     */
    abstract protected function referencePrefix(): string;

    /**
     * Human-readable success message for the charge.
     */
    abstract protected function successMessage(): string;
}
