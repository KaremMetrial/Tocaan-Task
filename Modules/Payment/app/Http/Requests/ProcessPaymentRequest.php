<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Payment\PaymentGatewayManager;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PaymentGatewayManager $manager */
        $manager = app(PaymentGatewayManager::class);

        return [
            'method' => ['required', 'string', Rule::in($manager->supported())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'method.in' => 'The selected payment method is not supported.',
        ];
    }
}
