<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Enums\PaymentStatus;

/**
 * Immutable DTO describing a gateway webhook callback, normalized across
 * providers so PaymentService can reconcile it without knowing the gateway's
 * native payload shape.
 */
final readonly class WebhookResult
{
    public function __construct(
        public string $reference,
        public PaymentStatus $status,
        public ?string $message = null,
    ) {}
}
