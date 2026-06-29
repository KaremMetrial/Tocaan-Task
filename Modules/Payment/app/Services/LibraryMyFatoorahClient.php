<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Order\Models\Order;
use Modules\Payment\Contracts\MyFatoorahClient;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;

/**
 * MyFatoorahClient backed by the official myfatoorah/laravel-package library.
 *
 * Reads credentials from config('myfatoorah.*') and maps them to the library's
 * expected constructor shape (the same mapping the package's own controller uses).
 */
class LibraryMyFatoorahClient implements MyFatoorahClient
{
    public function sendPayment(Order $order): array
    {
        $payment = new MyFatoorahPayment($this->libraryConfig());

        // gatewayId 'myfatoorah' -> SendPayment (hosted invoice link).
        $result = $payment->getInvoiceURL(
            [
                'InvoiceValue' => (float) $order->total,
                'CustomerName' => $order->customer_name,
                'CustomerEmail' => $order->customer_email,
                'DisplayCurrencyIso' => (string) config('myfatoorah.currency', 'KWD'),
                'CallBackUrl' => url('/api/payments/webhook/myfatoorah'),
                'ErrorUrl' => url('/api/payments/webhook/myfatoorah'),
            ],
            'myfatoorah',
            (string) $order->id,
        );

        return [
            'invoiceId' => (string) $result['invoiceId'],
            'invoiceUrl' => (string) $result['invoiceURL'],
        ];
    }

    public function getInvoiceStatus(string $invoiceId): string
    {
        $status = new MyFatoorahPaymentStatus($this->libraryConfig());
        $data = $status->getPaymentStatus($invoiceId, 'InvoiceId');

        return (string) ($data->InvoiceStatus ?? 'Pending');
    }

    /**
     * Map the published package config to the library's constructor config.
     *
     * @return array<string, mixed>
     */
    private function libraryConfig(): array
    {
        return [
            'apiKey' => (string) config('myfatoorah.api_key'),
            'isTest' => (bool) config('myfatoorah.is_test', true),
            'vcCode' => (string) config('myfatoorah.vc_code', 'KWT'),
            'loggerObj' => storage_path('logs/myfatoorah.log'),
        ];
    }
}
