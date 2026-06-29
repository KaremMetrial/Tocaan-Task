<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Order\Models\Order;

/**
 * Thin seam over the official myfatoorah/laravel-package library.
 *
 * Wrapping the library (which talks to MyFatoorah over raw cURL) behind this
 * interface keeps MyFatoorahGateway decoupled and lets tests bind a fake.
 */
interface MyFatoorahClient
{
    /**
     * Create a MyFatoorah invoice for the order and return its identifiers.
     *
     * @return array{invoiceId: string, invoiceUrl: string}
     */
    public function sendPayment(Order $order): array;

    /**
     * Fetch MyFatoorah's authoritative InvoiceStatus (e.g. "Paid", "Failed").
     */
    public function getInvoiceStatus(string $invoiceId): string;
}
