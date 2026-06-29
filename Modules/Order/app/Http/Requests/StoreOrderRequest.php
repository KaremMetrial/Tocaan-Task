<?php

declare(strict_types=1);

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Http\Requests\Concerns\HasItemRules;

class StoreOrderRequest extends FormRequest
{
    use HasItemRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            ...self::itemRules('required'),
        ];
    }
}
