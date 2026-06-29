<?php

declare(strict_types=1);

namespace Modules\Payment\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Filters\PaymentFilter;
use Modules\Payment\Models\Payment;

interface PaymentRepository
{
    /**
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginate(PaymentFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * Payments scoped to orders owned by the given user.
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginateForUser(int $userId, PaymentFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginateForOrder(Order $order, PaymentFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Payment;

    /**
     * Look a payment up by its gateway transaction reference (for webhooks).
     */
    public function findByReference(string $reference): ?Payment;

    /**
     * Update a payment's status and return the fresh model.
     */
    public function updateStatus(Payment $payment, PaymentStatus $status): Payment;
}
