<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\BusinessValidationException;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Payment\PaymentGatewayManager;
use Modules\Payment\Services\PaymentService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives asynchronous payment status callbacks from gateways.
 *
 * Unauthenticated by design (the caller is the gateway, not a logged-in user);
 * authenticity is established per-gateway via verifyWebhook() (signature check).
 */
class WebhookController extends ApiController
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentService $payments,
    ) {}

    /**
     * POST /api/payments/webhook/{gateway}
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        // resolve() throws UnsupportedGatewayException (422) for an unknown key.
        $strategy = $this->gateways->resolve($gateway);

        if (! $strategy->verifyWebhook($request)) {
            throw new BusinessValidationException(
                'Invalid webhook signature.',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $payment = $this->payments->applyWebhook($strategy->parseWebhook($request));

        return $this->successResponse(
            new PaymentResource($payment),
            'Webhook processed successfully.',
        );
    }
}
