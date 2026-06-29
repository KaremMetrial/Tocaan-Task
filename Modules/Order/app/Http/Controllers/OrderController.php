<?php

declare(strict_types=1);

namespace Modules\Order\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Order\Filters\OrderFilter;
use Modules\Order\Http\Requests\StoreOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderRequest;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;

class OrderController extends ApiController
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * GET /api/orders — list the authenticated user's orders, paginated.
     * Supports ?status=, ?customer=, ?email= filters (see OrderFilter).
     */
    public function index(Request $request, OrderFilter $filter): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = $this->orders->paginate(
            $filter,
            (int) ($validated['per_page'] ?? 15),
            $request->user()->id,
        );

        return $this->successResponse(
            OrderResource::collection($orders),
            'Orders retrieved successfully.',
        );
    }

    /**
     * POST /api/orders — create an order; total is computed server-side.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->create($request->user()->id, $request->validated());

        return $this->createdResponse(
            new OrderResource($order),
            'Order created successfully.',
        );
    }

    /**
     * GET /api/orders/{order} — show a single order (owner only).
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['items', 'payments']);

        return $this->successResponse(
            new OrderResource($order),
            'Order retrieved successfully.',
        );
    }

    /**
     * PUT/PATCH /api/orders/{order} — update an order (owner only).
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $order = $this->orders->update($order, $request->validated());

        return $this->successResponse(
            new OrderResource($order),
            'Order updated successfully.',
        );
    }

    /**
     * DELETE /api/orders/{order} — delete (owner only, blocked if payments exist).
     */
    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $this->orders->delete($order);

        return $this->noContentResponse();
    }
}
