<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways\Concerns;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\WebhookResult;
use Modules\Payment\Enums\PaymentStatus;

/**
 * Default webhook verification + parsing shared by the simulated gateways.
 *
 * Verification compares the `X-Webhook-Signature` header against the gateway's
 * configured `webhook_secret`. When no secret is configured (e.g. local/dev),
 * callbacks are accepted so the flow is testable without provider credentials.
 *
 * Expects the using class to declare `private readonly array $config`.
 */
trait HandlesWebhook
{
    public function verifyWebhook(Request $request): bool
    {
        $secret = $this->config['webhook_secret'] ?? null;

        if ($secret === null || $secret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Webhook-Signature', '');

        return $signature !== '' && hash_equals((string) $secret, $signature);
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $status = PaymentStatus::tryFrom((string) $request->input('status'))
            ?? PaymentStatus::Failed;

        return new WebhookResult(
            reference: (string) $request->input('reference', ''),
            status: $status,
            message: $request->input('message'),
        );
    }
}
