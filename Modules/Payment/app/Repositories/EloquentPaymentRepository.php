<?php

declare(strict_types=1);

namespace Modules\Payment\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Filters\PaymentFilter;
use Modules\Payment\Models\Payment;

class EloquentPaymentRepository implements PaymentRepository
{
    public function paginate(PaymentFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()->filter($filter)->latest('id')->paginate($perPage);
    }

    public function paginateForUser(int $userId, PaymentFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId))
            ->filter($filter)
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateForOrder(Order $order, PaymentFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $order->payments()->filter($filter)->latest('id')->paginate($perPage);
    }

    public function create(array $attributes): Payment
    {
        return Payment::query()->create($attributes);
    }

    public function findByReference(string $reference): ?Payment
    {
        return Payment::query()->where('transaction_reference', $reference)->first();
    }

    public function updateStatus(Payment $payment, PaymentStatus $status): Payment
    {
        $payment->update(['status' => $status]);

        return $payment->refresh();
    }
}
