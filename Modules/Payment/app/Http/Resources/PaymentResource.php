<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payment\Models\Payment;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'method' => $this->method,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'transaction_reference' => $this->transaction_reference,
            // Present only for redirect/hosted gateways on the initiating response.
            'redirect_url' => $this->whenNotNull($this->redirect_url ?? null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
