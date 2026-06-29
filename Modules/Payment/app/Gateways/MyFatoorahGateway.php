<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways;

use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\MyFatoorahClient;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\PaymentResult;
use Modules\Payment\Contracts\WebhookResult;
use Modules\Payment\Enums\PaymentStatus;
use Throwable;

/**
 * MyFatoorah gateway backed by the official myfatoorah/laravel-package.
 *
 * Hosted/redirect flow:
 *  - charge()        -> create an invoice (library SendPayment) and return a
 *                       PENDING result carrying the hosted InvoiceURL. The
 *                       customer is redirected; the final status arrives via webhook.
 *  - parseWebhook()  -> resolve the InvoiceId from the callback and read the
 *                       authoritative status from MyFatoorah (never trusts the body).
 *
 * The library is reached through the injectable MyFatoorahClient seam so the
 * gateway is unit-testable. Config lives in config/myfatoorah.php.
 */
class MyFatoorahGateway implements PaymentGateway
{
    public function __construct(protected readonly array $config = []) {}

    public function key(): string
    {
        return 'myfatoorah';
    }

    public function charge(Order $order, array $payload): PaymentResult
    {
        try {
            $invoice = $this->client()->sendPayment($order);
        } catch (Throwable $e) {
            return PaymentResult::failed('MyFatoorah payment initiation failed: '.$e->getMessage());
        }

        return PaymentResult::pending(
            reference: $invoice['invoiceId'],
            redirectUrl: $invoice['invoiceUrl'],
            message: 'Redirect the customer to the MyFatoorah invoice URL to complete the payment.',
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) config('myfatoorah.webhook_secret_key', '');

        // No secret configured (local/dev) -> accept. In production set the
        // secret in your MyFatoorah account and here to enforce verification.
        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->header('MyFatoorah-Signature', '');

        return $signature !== '';
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $invoiceId = (string) (
            $request->input('Data.Invoice.Id')
            ?? $request->input('Data.InvoiceId')
            ?? $request->input('InvoiceId')
            ?? $request->input('reference', '')
        );

        $invoiceStatus = $this->client()->getInvoiceStatus($invoiceId);

        return new WebhookResult(
            reference: $invoiceId,
            status: $this->mapStatus($invoiceStatus),
            message: $invoiceStatus,
        );
    }

    /**
     * Map MyFatoorah's InvoiceStatus to our PaymentStatus.
     */
    private function mapStatus(string $invoiceStatus): PaymentStatus
    {
        return match (strtolower($invoiceStatus)) {
            'paid', 'duplicatepayment' => PaymentStatus::Successful,
            'failed', 'expired', 'canceled', 'cancelled' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }

    private function client(): MyFatoorahClient
    {
        return app(MyFatoorahClient::class);
    }
}
