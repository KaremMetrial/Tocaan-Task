<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Order\Models\Order;
use Modules\Payment\Filters\PaymentFilter;
use Modules\Payment\Http\Requests\ProcessPaymentRequest;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Payment\Repositories\PaymentRepository;
use Modules\Payment\Services\PaymentService;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentRepository $repository,
    ) {}

    /**
     * GET /api/payments — payments for the authenticated user's orders,
     * paginated. Supports ?status=, ?method=, ?order_id= filters
     * (see PaymentFilter).
     */
    public function index(Request $request, PaymentFilter $filter): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        return $this->successResponse(
            PaymentResource::collection(
                $this->repository->paginateForUser($request->user()->id, $filter, $perPage),
            ),
            'Payments retrieved successfully.',
        );
    }

    /**
     * GET /api/orders/{order}/payments — payments for one order (owner only).
     */
    public function forOrder(Request $request, Order $order, PaymentFilter $filter): JsonResponse
    {
        $this->authorize('view', $order);

        $perPage = (int) $request->integer('per_page', 15);

        return $this->successResponse(
            PaymentResource::collection($this->repository->paginateForOrder($order, $filter, $perPage)),
            'Order payments retrieved successfully.',
        );
    }

    /**
     * POST /api/orders/{order}/payments — process a payment (owner only,
     * confirmed orders only).
     */
    public function store(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $payment = $this->payments->process(
            $order,
            $request->validated('method'),
            $request->except('method'),
        );

        return $this->createdResponse(
            new PaymentResource($payment),
            'Payment processed successfully.',
        );
    }
}
