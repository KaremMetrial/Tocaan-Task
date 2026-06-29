<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Enums\PaymentStatus;

/**
 * Immutable DTO describing the outcome of a gateway charge.
 *
 * Keeps the PaymentService decoupled from each gateway's native response shape.
 * For redirect/hosted gateways (e.g. MyFatoorah) the charge returns a Pending
 * result carrying the `redirectUrl` the client must send the customer to; the
 * final status arrives later via the gateway webhook.
 */
final readonly class PaymentResult
{
    public function __construct(
        public PaymentStatus $status,
        public ?string $reference = null,
        public ?string $message = null,
        public ?string $redirectUrl = null,
    ) {}

    public static function success(string $reference, ?string $message = null): self
    {
        return new self(PaymentStatus::Successful, $reference, $message);
    }

    public static function pending(string $reference, ?string $redirectUrl = null, ?string $message = null): self
    {
        return new self(PaymentStatus::Pending, $reference, $message, $redirectUrl);
    }

    public static function failed(?string $message = null): self
    {
        return new self(PaymentStatus::Failed, null, $message);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Successful;
    }
}
