<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Illuminate\Http\Request;
use Modules\Order\Models\Order;

/**
 * Strategy contract every payment gateway implements.
 *
 * Adding a new gateway = implement this interface + register the class in
 * config/config.php. No existing code changes (Open/Closed Principle).
 */
interface PaymentGateway
{
    /**
     * Unique key matching the request's `method` field (e.g. "credit_card").
     */
    public function key(): string;

    /**
     * Attempt to charge the order. Implementations must be side-effect free
     * with respect to persistence — they only talk to the (simulated) gateway
     * and return a normalized PaymentResult.
     *
     * @param  array<string, mixed>  $payload
     */
    public function charge(Order $order, array $payload): PaymentResult;

    /**
     * Verify the authenticity of an incoming webhook request (e.g. signature
     * check). Return false to reject the callback.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Translate a verified webhook request into a normalized WebhookResult.
     */
    public function parseWebhook(Request $request): WebhookResult;
}
