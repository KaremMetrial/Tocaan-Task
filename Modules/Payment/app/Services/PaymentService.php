<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Exceptions\BusinessValidationException;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\WebhookResult;
use Modules\Payment\Models\Payment;
use Modules\Payment\PaymentGatewayManager;
use Modules\Payment\Repositories\PaymentRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Orchestrates payment processing:
 *  - enforces "only confirmed orders may be paid"
 *  - resolves the correct gateway strategy
 *  - persists the payment with the gateway's normalized result
 *  - reconciles asynchronous gateway webhooks against stored payments
 *
 * The PaymentProcessed event is emitted by PaymentObserver on persistence.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentRepository $payments,
    ) {}

    /**
     * Process a payment for an order via the chosen gateway.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws BusinessValidationException When the order is not in a payable (confirmed) state.
     */
    public function process(Order $order, string $method, array $payload = []): Payment
    {
        $this->ensureOrderIsPayable($order);

        $gateway = $this->gateways->resolve($method);
        $result = $gateway->charge($order, $payload);

        $payment = $this->payments->create([
            'order_id' => $order->id,
            'method' => $gateway->key(),
            'status' => $result->status,
            'amount' => $order->total,
            'transaction_reference' => $result->reference,
        ]);

        // Redirect/hosted gateways return a URL the client must send the customer
        // to. It is not persisted (only relevant to this response), so attach it
        // transiently for the resource to surface.
        if ($result->redirectUrl !== null) {
            $payment->redirect_url = $result->redirectUrl;
        }

        return $payment;
    }

    /**
     * Apply a verified gateway webhook to the matching payment.
     *
     * @throws BusinessValidationException When no payment matches the reference.
     */
    public function applyWebhook(WebhookResult $result): Payment
    {
        $payment = $this->payments->findByReference($result->reference);

        if ($payment === null) {
            throw new BusinessValidationException(
                "No payment found for reference [{$result->reference}].",
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->payments->updateStatus($payment, $result->status);
    }

    /**
     * Business rule: payments can only be processed for confirmed orders.
     *
     * @throws BusinessValidationException
     */
    private function ensureOrderIsPayable(Order $order): void
    {
        if (! $order->status->isPayable()) {
            throw new BusinessValidationException(
                "Payments can only be processed for confirmed orders. This order is '{$order->status->value}'.",
            );
        }
    }
}
